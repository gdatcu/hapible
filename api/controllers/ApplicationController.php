<?php
require __DIR__ . '/../../config/config.php';

class ApplicationController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['logDownload'])) {
            self::logDownload();
        } elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_GET['updateStatus'])) {
            self::updateApplicationStatus();
        } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
            self::apply();
        } elseif ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['days'])) {
            // Handle the request for statistics
            self::getStatistics();
        } elseif ($_SERVER["REQUEST_METHOD"] == "GET") {
            // Default GET behavior (e.g., get all applications)
            self::getApplications();
        } elseif ($_SERVER["REQUEST_METHOD"] == "DELETE") {
            self::deleteApplication();
        } elseif ($_SERVER["REQUEST_METHOD"] == "PUT") {
            self::updateApplicationStatus(); // optional fallback
        }
    }
    
    
    
    

    public static function apply() {
        global $conn;

        $job_id = $_POST['job_id'];
        $user_id = $_POST['user_id']; // 🔓 No auth validation
        $message = $_POST['message'];
        $resumePath = null;

        $status = 'pending'; // default value
        $stmt = $conn->prepare("INSERT INTO applications (job_id, user_id, message, resume, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $job_id, $user_id, $message, $resumePath, $status);
        

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
                $job_id = intval($_GET['job_id']);
                $result = $conn->query("
                    SELECT applications.*, jobs.title AS job_title 
                    FROM applications
                    LEFT JOIN jobs ON applications.job_id = jobs.id
                    WHERE applications.job_id = $job_id
                ");
            } elseif (isset($_GET['all'])) {
                $result = $conn->query("
                    SELECT applications.*, jobs.title AS job_title 
                    FROM applications
                    LEFT JOIN jobs ON applications.job_id = jobs.id
                ");
            } else {
                $user_id = intval($_GET['user_id'] ?? 0);
                $result = $conn->query("
                    SELECT applications.*, jobs.title AS job_title 
                    FROM applications
                    LEFT JOIN jobs ON applications.job_id = jobs.id
                    WHERE applications.user_id = $user_id
                ");
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
    
    public static function getFilteredApplications() {
        global $conn;
    
        $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : null;
        $job = isset($_GET['job']) ? "%" . $conn->real_escape_string($_GET['job']) . "%" : null;
        $user = isset($_GET['user']) ? "%" . $conn->real_escape_string($_GET['user']) . "%" : null;
    
        $query = "SELECT applications.*, jobs.title AS job_title, users.name AS user_name
                  FROM applications
                  LEFT JOIN jobs ON applications.job_id = jobs.id
                  LEFT JOIN users ON applications.user_id = users.id
                  WHERE 1";
    
        if ($status) $query .= " AND LOWER(applications.status) = '$status'";
        if ($job) $query .= " AND jobs.title LIKE '$job'";
        if ($user) $query .= " AND users.name LIKE '$user'";
    
        $result = $conn->query($query);
        $apps = [];
        while ($row = $result->fetch_assoc()) {
            $apps[] = $row;
        }
    
        echo json_encode($apps);
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

    public static function logDownload() {
        global $conn;

        $input = json_decode(file_get_contents("php://input"), true);
        $user_id = $input['user_id'] ?? null;
        $app_id = $input['application_id'] ?? null;

        if (!$user_id || !$app_id) {
            http_response_code(400);
            echo json_encode(["error" => "Missing user or application ID"]);
            return;
        }

        $stmt = $conn->prepare("INSERT INTO resume_download_logs (user_id, application_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $app_id);
        $stmt->execute();

        echo json_encode(["success" => "Download logged"]);
    }

    
    
    
    public static function getStatistics() {
        global $conn;
    
        // Get the number of applications for the last X days
        $days = isset($_GET['days']) ? intval($_GET['days']) : 7; // Default is the last 7 days
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-$days days"));
    
        $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : null;
        $job = isset($_GET['job']) ? "%" . $conn->real_escape_string($_GET['job']) . "%" : null;
        $user = isset($_GET['user']) ? "%" . $conn->real_escape_string($_GET['user']) . "%" : null;
    
        // Corrected query
        $query = "SELECT COUNT(applications.id) AS total, DATE(applications.created_at) AS date
                  FROM applications
                  LEFT JOIN jobs ON applications.job_id = jobs.id
                  LEFT JOIN users ON applications.user_id = users.id
                  WHERE applications.created_at BETWEEN '$startDate' AND '$endDate'";
    
        // Add filters to the query if they exist
        if ($status) {
            $query .= " AND LOWER(applications.status) = '$status'";
        }
        if ($job) {
            $query .= " AND jobs.title LIKE '$job'";
        }
        if ($user) {
            $query .= " AND users.name LIKE '$user'";
        }
    
        // Proper GROUP BY and ORDER BY
        $query .= " GROUP BY DATE(applications.created_at)
                    ORDER BY DATE(applications.created_at)";
    
        // Execute the query
        $result = $conn->query($query);
    
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
    
        // Return the statistics as a JSON response
        echo json_encode($stats);
    }
    
    
    
    
    
    
    public static function updateApplicationStatus() {
        global $conn;
        $input = json_decode(file_get_contents("php://input"), true);
    
        $id = $input['application_id'] ?? null;
        $status = strtolower(trim($input['status'] ?? ''));
    
        if (!$id || !$status) {
            http_response_code(400);
            echo json_encode(["error" => "Missing application ID or status"]);
            return;
        }
    
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => "Application status updated"]);
        } else {
            echo json_encode(["error" => "No application found with the provided ID"]);
        }
    }
    
    
    
      
    
}
?>
