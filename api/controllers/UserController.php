<?php
require __DIR__ . '/../../config/config.php';

class UserController {
    public static function handle() {
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                self::getUser(); break;
            case "POST":
                self::updateUser(); break;
        }
    }

    public static function getUser() {
        global $conn;
        $id = $_GET['id'] ?? 1;
        $result = $conn->query("SELECT id, username, name, email, role FROM users WHERE id = $id");

        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(["error" => "User not found"]);
        }
    }

    public static function updateUser() {
        global $conn;
        $id = $_POST['id'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $company = $_POST['company_name'] ?? '';
    
        $conn->query("UPDATE users SET username='$username', email='$email', company_name='$company' WHERE id=$id");
        echo json_encode(["success" => "User updated"]);
    }
    
}
?>
