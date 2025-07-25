<?php
// Afișează erorile pentru a ajuta la depanare
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Antete (Headers) pentru CORS ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// --- GESTIONAREA CERERILOR PREFLIGHT (OPTIONS) ---
// Aceasta este secțiunea cheie care rezolvă eroarea.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200); // Răspundem cu "OK"
    exit(); // Oprim execuția scriptului
}

header("Content-Type: application/json");

// Include all controllers
require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/UserController.php';
require __DIR__ . '/controllers/JobController.php';
require __DIR__ . '/controllers/ApplicationController.php';
require __DIR__ . '/controllers/AdminController.php';
require __DIR__ . '/controllers/RegisterController.php';

// Get path (e.g. /api/auth)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode("/", trim($uri, "/"));

// Find the last segment (e.g. "auth", "users")
$endpoint = end($segments); // This works regardless of depth

switch ($endpoint) {
    case 'auth': AuthController::handle(); break;
    case 'users': UserController::handle(); break;
    case 'jobs': JobController::handle(); break;
    case 'apply': ApplicationController::handle(); break;
    case 'admin': AdminController::handle(); break;
    case 'register': RegisterController::handle(); break;
    // Add the route for /hapible/api/apply/getJobs
    case 'getJobs': ApplicationController::getJobs(); break;
    default:
    echo json_encode([
        "error" => "Invalid API endpoint. Try /auth, /jobs, /users, /apply, or /admin"
    ]);

}
?>
