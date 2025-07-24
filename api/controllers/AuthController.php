<?php
// Asigură-te că aceste căi sunt corecte pentru structura ta
require_once __DIR__ . '/../../config/config.php';
// CORECTAT: Calea către vendor/autoload.php a fost ajustată.
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

class AuthController {
    public static function handle() {
        // --- LOGICĂ ROBUSTĂ PENTRU A CITI DATELE DE INTRARE ---
        $data = [];
        // Verificăm dacă request-ul este JSON (de la aplicația mobilă)
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $json_data = file_get_contents('php://input');
            $data = json_decode($json_data, true);
        } else {
            // Altfel, presupunem că este un formular standard (de la aplicația web)
            $data = $_POST;
        }

        // Verificăm dacă email-ul și parola au fost furnizate
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Email and password are required.']);
            return;
        }
        
        $email = $data['email'];
        $password = $data['password'];

        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Datele sunt corecte, generăm token-ul JWT
                
                // ATENȚIE: Aceste valori trebuie să fie definite în config.php
                $secret_key = defined('JWT_SECRET') ? JWT_SECRET : "your_default_secret_key";
                $issuer_claim = "YOUR_ISSUER"; // ex: "hapible.ro"
                $audience_claim = "YOUR_AUDIENCE"; // ex: "hapible.ro"
                $issuedat_claim = time();
                $expire_claim = $issuedat_claim + (3600 * 24); // Token valabil 24 de ore

                $token_payload = [
                    "iss" => $issuer_claim,
                    "aud" => $audience_claim,
                    "iat" => $issuedat_claim,
                    "exp" => $expire_claim,
                    "data" => [
                        "id" => $user['id'],
                        "email" => $user['email'],
                        "role" => $user['role']
                    ]
                ];

                $jwt = JWT::encode($token_payload, $secret_key, 'HS256');

                // Eliminăm parola din răspuns pentru securitate
                unset($user['password']);

                http_response_code(200);
                echo json_encode([
                    "message" => "Successful login.",
                    "token" => $jwt,
                    "user" => $user
                ]);

            } else {
                // Credențiale invalide
                http_response_code(401);
                echo json_encode(["error" => "Invalid credentials."]);
            }
        } catch (Exception $e) {
            // Orice altă eroare (ex: problemă cu baza de date)
            http_response_code(500);
            echo json_encode([
                "error" => "An internal server error occurred.",
                "details" => $e->getMessage() // Afișează detalii doar în modul de dezvoltare
            ]);
        }
    }
}
