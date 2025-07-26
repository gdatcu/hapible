<?php
// Folosim calea corectă, cu forward slashes, pentru compatibilitate
require_once __DIR__ . '/../../config/config.php';

class AuthController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            self::login();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Only POST method is accepted."]);
        }
    }

    public static function login() {
        global $conn;

        // --- PASUL 1: Citim datele, indiferent de sursă (Web sau Mobil) ---
        $input = [];
        if (!empty($_POST)) {
            $input = $_POST; // Pentru aplicația web
        } else {
            $json_input = file_get_contents('php://input'); // Pentru aplicația mobilă
            $decoded_input = json_decode($json_input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $decoded_input;
            }
        }

        // --- PASUL 2: Validăm datele de intrare ---
        $password_from_user = $input['password'] ?? null;
        
        if (isset($input['username'])) {
            $login_identifier = $input['username'];
            $sql_column = "username";
        } elseif (isset($input['email'])) {
            $login_identifier = $input['email'];
            $sql_column = "email";
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Username or email is required."]);
            return;
        }

        if (empty($password_from_user)) {
            http_response_code(400);
            echo json_encode(["error" => "Password is required."]);
            return;
        }

        try {
            // --- PASUL 3: Interogare securizată (Prepared Statement) ---
            $stmt = $conn->prepare("SELECT * FROM users WHERE $sql_column = ?");
            $stmt->bind_param("s", $login_identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $password_from_db = $user['password'];
                $is_password_correct = false;

                // --- PASUL 4: Verificare dublă a parolei (pentru utilizatori vechi și noi) ---
                if (preg_match('/^\$2y\$/', $password_from_db)) {
                    $is_password_correct = password_verify($password_from_user, $password_from_db);
                } else {
                    $is_password_correct = ($password_from_user === $password_from_db);
                }

                if ($is_password_correct) {
                    // Eliminăm parola din răspuns pentru securitate
                    unset($user['password']);

                    // Generăm token-ul simplu, la fel ca în codul tău
                    $token = base64_encode($user["id"] . ":" . $user["role"]);
                    
                    http_response_code(200);
                    // --- CORECTAT: Trimitem un răspuns complet, care include OBIECTUL USER ---
                    echo json_encode([
                        "message" => "Successful login.",
                        "token" => $token,
                        "user" => $user, // Această linie este esențială pentru aplicația mobilă
                        "api_key" => $user["api_key"] ?? null
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

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "An internal server error occurred.", "details" => $e->getMessage()]);
        }
    }
}

?>