<?php
// Folosim calea corectă, cu forward slashes
require_once __DIR__ . '/../../config/config.php';
// Includem middleware-ul de autentificare, esențial pentru a prelua aplicațiile utilizatorului
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class ApplicationController {
    public static function handle() {
        // Modificăm logica pentru a gestiona atât POST, cât și GET
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            self::apply();
        } elseif ($_SERVER["REQUEST_METHOD"] === "GET") {
            self::getUserApplications();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Invalid request method."]);
        }
    }

    public static function apply() {
        global $conn;

        if (!isset($_POST['user_id']) || !isset($_POST['job_id']) || !isset($_FILES['resume'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing user_id, job_id, or resume file."]);
            return;
        }

        $user_id = $_POST['user_id'];
        $job_id = $_POST['job_id'];
        $resume = $_FILES['resume'];

        if ($resume['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["error" => "Error uploading file. Code: " . $resume['error']]);
            return;
        }

        $upload_dir = __DIR__ . '/../../uploads/';
        $file_extension = pathinfo($resume['name'], PATHINFO_EXTENSION);
        $safe_filename = uniqid('resume_', true) . '.' . $file_extension;
        $resume_path = $upload_dir . $safe_filename;

        if (!move_uploaded_file($resume['tmp_name'], $resume_path)) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to save the uploaded file."]);
            return;
        }

        $db_resume_path = 'uploads/' . $safe_filename;

        try {
            $stmt = $conn->prepare("INSERT INTO applications (user_id, job_id, resume) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $job_id, $db_resume_path);

            if ($stmt->execute()) {
                http_response_code(201); // Created
                echo json_encode([
                    "success" => "Application submitted",
                    "resume" => $db_resume_path
                ]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Failed to apply: " . $stmt->error]);
            }
            $stmt->close();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "An internal server error occurred.", "details" => $e->getMessage()]);
        }
    }

    // --- Funcția pentru a prelua aplicațiile unui utilizator ---
    public static function getUserApplications() {
        global $conn;

        $user_id = AuthMiddleware::getUserId();
        
        if (!$user_id) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized. Invalid or missing token."]);
            return;
        }

        try {
            // --- CORECTAT: Am modificat condiția de JOIN ---
            // Acum folosim `j.employer_id = u.id` pentru a lega corect job-ul de angajator.
            $stmt = $conn->prepare("
                SELECT 
                    j.title, 
                    u.company_name, 
                    a.status, 
                    a.applied_at,
                    a.id
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                JOIN users u ON j.employer_id = u.id
                WHERE a.user_id = ?
                ORDER BY a.applied_at DESC
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $applications = [];
            while ($row = $result->fetch_assoc()) {
                $applications[] = $row;
            }

            http_response_code(200);
            echo json_encode($applications);
            $stmt->close();

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "An internal server error occurred.", "details" => $e->getMessage()]);
        }
    }
}
