<?php
require __DIR__ . '/../../config/config.php';

class ApplicationController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            self::apply();
        } elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
            self::getApplications();
        } elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {
            self::deleteApplication();
        }
    }

    public static function apply() {
        global $conn;
    
        $job_id = $_POST['job_id'];
        $user_id = $_POST['user_id']; // 🔓 No auth validation
        $message = $_POST['message'];
        $resumePath = null;
    
        // 🔥 Vulnerable file upload (no type/size check, no sanitization)
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === 0) {
            $uploadDir = __DIR__ . '/../../uploads/';
            $originalName = $_FILES['resume']['name']; // No sanitization!
            $tempPath = $_FILES['resume']['tmp_name'];
            $targetPath = $uploadDir . $originalName;
    
            if (move_uploaded_file($tempPath, $targetPath)) {
                $resumePath = $targetPath;
            }
        }
    
        $stmt = $conn->prepare("INSERT INTO applications (job_id, user_id, message, resume) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $job_id, $user_id, $message, $resumePath);
        $stmt->execute();
    
        echo json_encode(["success" => "Application submitted", "resume" => $resumePath]);
    }
    

public static function getApplications() {
    global $conn;

    try {
        if (isset($_GET['job_id'])) {
            $job_id = $_GET['job_id'];
            $result = $conn->query("SELECT * FROM applications WHERE job_id = $job_id");
        } elseif (isset($_GET['all'])) {
            $result = $conn->query("SELECT * FROM applications");
        } else {
            $user_id = $_GET['user_id'] ?? 0;
            $result = $conn->query("SELECT * FROM applications WHERE user_id = $user_id");
        }

        $apps = [];
        while ($row = $result->fetch_assoc()) {
            $apps[] = $row;
        }

        echo json_encode($apps);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["error" => "Server error", "details" => $e->getMessage()]);
    }
}

public static function deleteApplication() {
    global $conn;

    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(["error" => "Missing application ID"]);
        return;
    }

    $conn->query("DELETE FROM applications WHERE id = $id");
    echo json_encode(["success" => "Application deleted"]);
}



}
?>
