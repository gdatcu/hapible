<?php
require __DIR__ . '/../../config/config.php'; // Database connection

class RegisterController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            self::register();
        } else {
            echo json_encode(["error" => "Invalid request"]);
        }
    }

    public static function register() {
        global $conn;

		$name = $_POST['name'];
        $username = $_POST['username'];
        $password = $_POST['password'];  // ❌ Plaintext, no hashing
        $email = $_POST['email'];
        $role = $_POST['role'];  // ❌ User can select ANY role, including "admin"
        $company = $_POST['company_name'] ?? '';

        // ❌ No input validation or sanitization (SQLi risk if used unsafely)
        $sql = "INSERT INTO users (name, username, password, email, role, company_name) 
                VALUES ('$name', '$username', '$password', '$email', '$role','$company')";

        if ($conn->query($sql) === TRUE) {
            echo json_encode(["success" => "User registered"]);
        } else {
            echo json_encode(["error" => "Failed to register"]);
        }
    }
}
?>
