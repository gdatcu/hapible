<?php
// --- Permite cereri CORS (Cross-Origin Resource Sharing) ---
header("Access-Control-Allow-Origin: *"); // Permite cereri de la orice origine. Pentru producție, poți înlocui * cu http://localhost:5173
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestionează cererile de tip OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}
// --- Sfârșitul blocului CORS ---

class AuthMiddleware {
    public static function validateToken($token) {
        list($user_id, $role) = explode(":", base64_decode($token));
        return ["user_id" => $user_id, "role" => $role];
    }
}
?>
