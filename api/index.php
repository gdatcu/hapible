<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Permite cereri CORS (Cross-Origin Resource Sharing) ---
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestionează cererile de tip OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// --- LOGICĂ DE RUTARE ROBUSTĂ ---

// Get the request URI and split it into parts
$request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));

// Find the position of "api" in the URI. This makes the code robust.
$api_pos = array_search('api', $request_uri);

// The endpoint is the part of the URI that comes *after* "api".
$endpoint = '';
if ($api_pos !== false && isset($request_uri[$api_pos + 1])) {
    // We also remove any potential query strings like ?param=1
    $endpoint = strtok($request_uri[$api_pos + 1], '?');
}

// Route the request to the appropriate controller based on the dynamically found endpoint.
switch ($endpoint) {
    case 'auth':
        require 'controllers/AuthController.php';
        break;
    case 'register':
        require 'controllers/RegisterController.php';
        break;
    case 'jobs':
        require 'controllers/JobController.php';
        break;
    case 'users':
        require 'controllers/UserController.php';
        break;
    case 'apply':
        require 'controllers/ApplicationController.php';
        break;
    case 'admin':
        require 'controllers/AdminController.php';
        break;
    default:
        // Invalid endpoint
        http_response_code(404);
        echo json_encode(array("error" => "Invalid API endpoint. Try /auth, /jobs, /users, /apply, or /admin"));
        break;
}


?>
