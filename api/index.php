<?php
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
    case 'getJobs': 
        ApplicationController::getJobs(); 
        break;

    default:
    echo json_encode([
        "error" => "Invalid API endpoint. Try /auth, /jobs, /users, /apply, or /admin"
    ]);

}
?>
