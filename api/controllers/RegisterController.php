<?php
// Afișează erorile pentru a ajuta la depanare
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

        // --- LOGICĂ ROBUSTĂ PENTRU A CITI DATELE DE INTRARE ---
        $input = [];
        if (!empty($_POST)) {
            $input = $_POST;
        } else {
            $json_input = file_get_contents('php://input');
            $decoded_input = json_decode($json_input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded_input;
            }
        }

        // --- VALIDARE ȘI MAPARE CÂMPURI ---
        $name = $input['name'] ?? trim(($input['first_name'] ?? '') . ' ' . ($input['last_name'] ?? ''));
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;
        $role = $input['role'] ?? null;
        $username = $input['username'] ?? $email;
        $company_name = $input['company_name'] ?? '';

        if (empty($name) || empty($username) || empty($email) || empty($password) || empty($role)) {
            http_response_code(400);
            echo json_encode(["error" => "All required fields (name/username, email, password, role) must be provided."]);
            return;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Verificăm dacă email-ul sau username-ul există deja
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                http_response_code(409); // Conflict
                echo json_encode(["error" => "An account with this email or username already exists."]);
                $stmt->close();
                return;
            }
            $stmt->close();

            // Inserăm noul utilizator
            $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role, company_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $username, $email, $hashed_password, $role, $company_name);
            
            if ($stmt->execute()) {
                http_response_code(201); // Created
                // CORECTAT: Trimitem un răspuns cu cheia "success" pentru compatibilitate cu aplicația web
                echo json_encode(["success" => "User registered successfully!"]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to create account: " . $stmt->error]);
            }
            $stmt->close();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "An internal server error occurred.", "details" => $e->getMessage()]);
        }
    }
}
?>
