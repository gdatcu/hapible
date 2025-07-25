<?php
// Afișează erorile pentru a ajuta la depanare
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Folosim calea corectă, cu forward slashes
require_once __DIR__ . '/../../config/config.php';

class AuthController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            self::login();
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(["error" => "Only POST method is accepted."]);
        }
    }

    public static function login() {
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

        // Verificăm dacă am primit datele necesare
        if ((!isset($input['username']) && !isset($input['email'])) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(["error" => "Username/Email and password are required."]);
            return;
        }

        $password_from_user = $input['password'];

        // --- INTEROGARE SECURIZATĂ CU PREPARED STATEMENTS ---
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
            $password_from_db = $user['password'];
            $is_password_correct = false;

            // --- LOGICĂ DE VERIFICARE DUBLĂ PENTRU PAROLĂ ---
            // Verificăm dacă parola din DB este hash-uită (începe cu '$')
            if (preg_match('/^\$2y\$/', $password_from_db)) {
                // Dacă este hash-uită, folosim password_verify (pentru utilizatorii noi)
                $is_password_correct = password_verify($password_from_user, $password_from_db);
            } else {
                // Dacă nu este hash-uită, o comparăm direct (pentru utilizatorii vechi)
                $is_password_correct = ($password_from_user === $password_from_db);
            }

            if ($is_password_correct) {
                // Eliminăm parola din răspuns pentru securitate
                unset($user['password']);

                // Generăm token-ul simplu, la fel ca în codul tău original
                $token = base64_encode($user["id"] . ":" . $user["role"]);
                
                http_response_code(200);
                echo json_encode([
                    "message" => "Successful login.",
                    "token" => $token,
                    "user" => $user
                ]);

            } else {
                // Parola este incorectă
                http_response_code(401);
                echo json_encode(["error" => "Invalid credentials"]);
            }
        } else {
            // Utilizatorul nu a fost găsit
            http_response_code(401);
            echo json_encode(["error" => "Invalid credentials"]);
        }
        $stmt->close();
    }
}
?>
