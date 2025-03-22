<?php
require __DIR__ . '/../../config/config.php';

class JobController {
    public static function handle() {
        switch ($_SERVER["REQUEST_METHOD"]) {
            case "GET":
                self::listJobs();
                break;
            case "POST":
                $action = $_POST['action'] ?? 'create';
                if ($action === 'updateStatus') {
                    self::updateJobStatus();
                } elseif ($action === 'update') {
                    self::updateJob();
                } elseif ($action === 'delete') {
                    self::deleteJob(); // Optional fallback if using POST for delete
                } elseif ($action === 'renew') {
                    self::renewJob();
                } else {
                    self::createJob();
                }
                break;
            case "DELETE":
                self::deleteJob(); // Main DELETE route
                break;
        }
    }

    public static function createJob() {
        global $conn;
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $employer_id = $_POST['employer_id'];
        $expires_at = $_POST['expires_at'] ?? null;
    
        // Auto set expiry to 21 days from now if not provided
        if (!$expires_at) {
            $expires_at = date('Y-m-d', strtotime('+21 days'));
        }
    
        $conn->query("INSERT INTO jobs (title, description, employer_id, expires_at, created_at)
                      VALUES ('$title', '$desc', $employer_id, '$expires_at', NOW())");
    
        echo json_encode(["success" => "Job created"]);
    }
    

    public static function updateJob() {
        global $conn;
        $id = $_POST['id'];
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $expires_at = $_POST['expires_at'] ?? null;
    
        $conn->query("UPDATE jobs SET title='$title', description='$desc', expires_at='$expires_at' WHERE id=$id");
    
        echo json_encode(["success" => "Job updated"]);
    }
    

    public static function updateJobStatus() {
        global $conn;
        $id = $_POST['id'];
        $status = $_POST['status'];

        $conn->query("UPDATE jobs SET status='$status' WHERE id=$id");
        echo json_encode(["success" => "Job status updated to $status"]);
    }

    public static function deleteJob() {
        global $conn;
    
        // 🔐 Read DELETE body safely
        parse_str(file_get_contents("php://input"), $data);
        $id = $data['id'] ?? null;
    
        if (!$id) {
            http_response_code(400);
            echo json_encode(["error" => "Job ID is required"]);
            return;
        }
    
        // 🔍 Check if the job has any applications
        $res = $conn->query("SELECT COUNT(*) as total FROM applications WHERE job_id = $id");
        $count = $res->fetch_assoc()['total'];
    
        if ($count > 0) {
            http_response_code(403);
            echo json_encode(["error" => "Cannot delete this job because applications exist."]);
            return;
        }
    
        // ✅ Proceed with deletion
        $conn->query("DELETE FROM jobs WHERE id=$id");
        echo json_encode(["success" => "Job deleted"]);
    }
    

    public static function listJobs() {
        global $conn;
        $result = $conn->query("SELECT jobs.*, users.company_name 
  FROM jobs 
  JOIN users ON users.id = jobs.employer_id");
        $jobs = [];

        while ($row = $result->fetch_assoc()) {
            $jobs[] = $row;
        }

        echo json_encode($jobs);
    }

    public static function renewJob() {
        global $conn;
        $id = $_POST['id'];
    
        // New expiry = 21 days from today
        $new_expiry = date('Y-m-d', strtotime('+21 days'));
    
        $conn->query("UPDATE jobs SET status='active', expires_at='$new_expiry' WHERE id=$id");
    
        echo json_encode(["success" => "Job renewed"]);
    }

}
