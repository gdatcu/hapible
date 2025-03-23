<?php
require __DIR__ . '/../../config/config.php';


class AuthController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            self::login();
        }
    }

    public static function login() {
        global $conn;
        $username = $_POST['username'];
        $password = $_POST['password'];

        $result = $conn->query("SELECT * FROM users WHERE username = '$username' AND password = '$password'");
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $token = base64_encode($user["id"] . ":" . $user["role"]);
            echo json_encode(["token" => $token, "api_key" => $user["api_key"]]); // API key leaked!
        } else {
            echo json_encode(["error" => "Invalid credentials"]);
        }
    }
}
?>
