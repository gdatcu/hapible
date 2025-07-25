<?php
// Folosim calea corectă, cu forward slashes
require_once __DIR__ . '/../../config/config.php';

class AuthController {
    public static function handle() {
        // Verificăm dacă metoda este POST, la fel ca în codul tău original
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            self::login();
        } else {
            // Dacă nu este POST, trimitem o eroare
            http_response_code(405); // Method Not Allowed
            echo json_encode(["error" => "Only POST method is accepted."]);
        }
    }

    public static function login() {
        // Folosim conexiunea globală, la fel ca în codul tău
        global $conn; 

        // --- LOGICĂ ROBUSTĂ PENTRU A CITI DATELE DE INTRARE ---
        $input = [];
        // Prioritizăm $_POST (pentru aplicația web)
        if (!empty($_POST)) {
            $input = $_POST;
        } else {
            // Dacă $_POST este gol, citim JSON (pentru aplicația mobilă)
            $json_input = file_get_contents('php://input');
            $decoded_input = json_decode($json_input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded_input;
            }
        }

        // Verificăm dacă am primit datele necesare
        if ((!isset($input['username']) && !isset($input['email'])) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(["error" => "Username/Email and password are required."]);
            return;
        }

        $password = $input['password'];

        // --- INTEROGARE SECURIZATĂ CU PREPARED STATEMENTS ---
        // Aceasta previne atacurile de tip SQL Injection.
        
        // Stabilim dacă login-ul se face cu username sau email
        if (isset($input['username'])) {
            $login_identifier = $input['username'];
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        } else {
            $login_identifier = $input['email'];
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        }

        if ($stmt === false) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to prepare statement: " . $conn->error]);
            return;
        }

        $stmt->bind_param("s", $login_identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // !!! AVERTISMENT IMPORTANT DE SECURITATE !!!
            // Codul tău original compară parolele direct. Asta înseamnă că parolele
            // sunt stocate în baza de date ca text clar, ceea ce este extrem de periculos.
            // Pentru a menține compatibilitatea, am păstrat temporar această logică.
            // Este VITAL să treci la `password_hash()` și `password_verify()` cât mai curând posibil.
            
            if ($password === $user['password']) { // Această linie este INSECURĂ!
                // Eliminăm parola din răspuns pentru securitate
                unset($user['password']);

                // Generăm token-ul simplu, la fel ca în codul tău
                $token = base64_encode($user["id"] . ":" . $user["role"]);
                
                http_response_code(200);
                echo json_encode([
                    "message" => "Successful login.",
                    "token" => $token,
                    "user" => $user // Trimitem și datele utilizatorului
                ]);

            } else {
                http_response_code(401);
                echo json_encode(["error" => "Invalid credentials"]);
            }
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Invalid credentials"]);
        }
        $stmt->close();
    }
}
