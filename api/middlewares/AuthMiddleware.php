<?php
class AuthMiddleware {
    public static function validateToken($token) {
        list($user_id, $role) = explode(":", base64_decode($token));
        return ["user_id" => $user_id, "role" => $role];
    }
}
?>
