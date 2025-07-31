<?php
// Nu mai avem nevoie de biblioteca JWT aici, deoarece token-ul este unul simplu, base64.

class AuthMiddleware {
    // Funcția pentru a prelua header-ul de autorizare rămâne la fel.
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
    
    // Funcția pentru a extrage token-ul din header rămâne la fel.
    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * CORECTAT: Această funcție decodează acum token-ul simplu (base64)
     * pe care îl generează AuthController.php.
     */
    public static function getUserId() {
        $token = self::getBearerToken();

        if ($token) {
            // Decodificăm token-ul din base64.
            $decoded_string = base64_decode($token, true);

            // Verificăm dacă decodificarea a reușit și dacă formatul este corect (ex: "123:candidate")
            if ($decoded_string !== false && strpos($decoded_string, ':') !== false) {
                // Spargem string-ul în părți, folosind ":" ca separator.
                $parts = explode(':', $decoded_string);
                // ID-ul utilizatorului este prima parte.
                $user_id = $parts[0];
                // Returnăm ID-ul ca un număr întreg.
                return (int)$user_id;
            }
        }

        // Dacă token-ul lipsește sau este invalid, returnăm null.
        return null;
    }
}
