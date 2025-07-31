<?php
require_once __DIR__ . '/../../vendor/autoload.php';
// Includem fișierul de configurare pentru a avea acces la JWT_SECRET
require_once __DIR__ . '/../../config/config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware {
    public static function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    
    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    public static function getUserId() {
        // Verificăm dacă cheia secretă este definită, pentru a preveni erorile fatale.
        if (!defined('JWT_SECRET') || JWT_SECRET === 'inlocuieste-cu-un-text-secret-foarte-lung-si-complex!') {
            // Nu trimitem eroare JSON aici pentru a nu expune detalii de configurare,
            // dar returnăm null pentru ca controller-ul să știe că autentificarea a eșuat.
            return null;
        }

        $token = self::getBearerToken();
        if ($token) {
            try {
                $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
                // Verificăm dacă structura token-ului este cea așteptată
                if (isset($decoded->data) && isset($decoded->data->id)) {
                    return $decoded->data->id;
                }
                return null; // Structură invalidă a token-ului
            } catch (Exception $e) {
                // Token invalid (expirat, semnatura gresita etc.)
                return null;
            }
        }
        return null; // Nu a fost furnizat niciun token
    }
}
?>
