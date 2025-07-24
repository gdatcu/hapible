<?php
// Afișează erorile pentru a ajuta la depanare (poate fi eliminat în producție)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Permite cereri CORS (Cross-Origin Resource Sharing) ---
// Permite cereri de la orice origine. Pentru producție, poți înlocui * cu adresa aplicației tale.
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Browserele trimit o cerere de tip OPTIONS (preflight) înainte de POST/PUT etc.
// Trebuie să răspundem cu succes la aceasta.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- LOGICĂ DE RUTARE SIMPLĂ ȘI ROBUSTĂ ---

// Eliminăm orice query string (ex: ?id=123) din URI
$request_path = strtok($_SERVER['REQUEST_URI'], '?');

// Extragem ultimul segment din cale, care este endpoint-ul nostru (ex: 'auth', 'jobs')
$endpoint = basename($request_path);

// Rutăm cererea către controller-ul corespunzător
switch ($endpoint) {
    case 'auth':
        require __DIR__ . '/controllers/AuthController.php';
        break;
    case 'register':
        require __DIR__ . '/controllers/RegisterController.php';
        break;
    case 'jobs':
        require __DIR__ . '/controllers/JobController.php';
        break;
    case 'users':
        require __DIR__ . '/controllers/UserController.php';
        break;
    case 'apply':
        require __DIR__ . '/controllers/ApplicationController.php';
        break;
    case 'admin':
        require __DIR__ . '/controllers/AdminController.php';
        break;
    // Dacă endpoint-ul nu este găsit, returnăm o eroare 404
    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint not found. Last part of URL was: '" . htmlspecialchars($endpoint) . "'"]);
        break;
}

?>
