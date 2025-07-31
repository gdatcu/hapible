<?php
// Folosim calea corectă, cu forward slashes
require_once __DIR__ . '/../../config/config.php';

class ApplicationController {
    public static function handle() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            self::apply();
        } else {
            http_response_code(405);
            echo json_encode(["error" => "Invalid request method."]);
        }
    }

    public static function apply() {
        global $conn;

        // --- PASUL 1: Validăm datele primite ---
        if (!isset($_POST['user_id']) || !isset($_POST['job_id']) || !isset($_FILES['resume'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing user_id, job_id, or resume file."]);
            return;
        }

        $user_id = $_POST['user_id'];
        $job_id = $_POST['job_id'];
        $resume = $_FILES['resume'];

        // Verificăm dacă a existat vreo eroare la încărcare
        if ($resume['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["error" => "Error uploading file. Code: " . $resume['error']]);
            return;
        }

        // --- PASUL 2: Procesăm fișierul încărcat în siguranță ---
        $upload_dir = __DIR__ . '/../../uploads/';
        // Creăm un nume unic pentru fișier pentru a preveni suprascrierea și conflictele
        $file_extension = pathinfo($resume['name'], PATHINFO_EXTENSION);
        $safe_filename = uniqid('resume_', true) . '.' . $file_extension;
        $resume_path = $upload_dir . $safe_filename;

        // Mutăm fișierul din locația temporară în cea finală
        if (!move_uploaded_file($resume['tmp_name'], $resume_path)) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to save the uploaded file."]);
            return;
        }

        // Salvăm calea relativă la rădăcina proiectului în baza de date
        $db_resume_path = 'uploads/' . $safe_filename;

        try {
            // --- PASUL 3: Inserăm datele în baza de date în mod securizat ---
            $stmt = $conn->prepare("INSERT INTO applications (user_id, job_id, resume_path) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $job_id, $db_resume_path);

            if ($stmt->execute()) {
                http_response_code(201); // Created
                // Păstrăm răspunsul original pentru compatibilitate
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

    public static function getJobs() {
        // Această funcție poate fi implementată ulterior
        echo json_encode(["message" => "Endpoint for getJobs is available but not implemented."]);
    }
}
