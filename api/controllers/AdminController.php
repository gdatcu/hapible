<?php
require __DIR__ . '/../../config/config.php';

class AdminController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            self::updateRole();
        }
    }

    public static function updateRole() {
        global $conn;
        $id = $_POST['id'];  // No authentication check
        $role = $_POST['role'];

        $conn->query("UPDATE users SET role='$role' WHERE id=$id");
        echo json_encode(["success" => "User role updated"]);
    }
}
?>
