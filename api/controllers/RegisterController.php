<?php
// Folosim calea corectă, cu forward slashes
require_once __DIR__ . '/../../config/config.php';

class RegisterController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            self::register();
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(["error" => "Only POST method is accepted."]);
        }
    }

    public static function register() {
        global $conn;

        // --- PASUL 1: Citim datele, indiferent de sursă (Web sau Mobil) ---
        $input = [];
        if (!empty($_POST)) {
            // Dacă vin de la aplicația web, folosim $_POST
            $input = $_POST;
        } else {
            // Dacă vin de la aplicația mobilă, citim JSON
            $json_input = file_get_contents('php://input');
            $decoded_input = json_decode($json_input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded_input;
            }
        }

        // --- PASUL 2: Adaptăm câmpurile pentru ambele aplicații ---
        // Web trimite 'name', Mobil trimite 'first_name' și 'last_name'
        $name = $input['name'] ?? trim(($input['first_name'] ?? '') . ' ' . ($input['last_name'] ?? ''));
        
        // Web trimite 'username', Mobil folosește email-ul ca username
        $username = $input['username'] ?? ($input['email'] ?? null);
        
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;
        $role = $input['role'] ?? null;
        $company_name = $input['company_name'] ?? '';

        // Verificăm dacă datele esențiale există
        if (empty($name) || empty($username) || empty($email) || empty($password) || empty($role)) {
            http_response_code(400);
            echo json_encode(["error" => "All required fields must be provided."]);
            return;
        }

        // --- PASUL 3: ÎMBUNĂTĂȚIRE DE SECURITATE (HASH-UIREA PAROLEI) ---
        // În loc să salvăm parola ca text, o salvăm într-un format securizat.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // --- PASUL 4: ÎMBUNĂTĂȚIRE DE SECURITATE (PREPARED STATEMENTS) ---
            // Prevenim atacurile de tip SQL Injection.
            $stmt = $conn->prepare("INSERT INTO users (name, username, password, email, role, company_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $username, $hashed_password, $email, $role, $company_name);
            
            if ($stmt->execute()) {
                http_response_code(201); // Created
                // Păstrăm răspunsul original pentru compatibilitate cu aplicația web
                echo json_encode(["success" => "User registered"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to register: " . $stmt->error]);
            }
            $stmt->close();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "An internal server error occurred.", "details" => $e->getMessage()]);
        }
    }
}
?>