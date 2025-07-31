<?php
$servername = "localhost";
$user = "gbrmlvka_hapibleadm";
$password = "qazXSW@1393";
$database = "gbrmlvka_hapible";

// --- CHEIA SECRETĂ PENTRU TOKEN-URI (ESENȚIALĂ) ---
// Aceasta este necesară pentru securitatea autentificării.
// Trebuie să fie un text lung, complex și aleatoriu.
// Poți genera o cheie nouă și sigură aici: https://www.random.org/strings/
define('JWT_SECRET', '7688708138502173644117211172484676738591556127003185564655761202!');


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Oprim execuția și trimitem un răspuns JSON, nu HTML, pentru a evita erorile
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Funcția getDB, păstrată pentru compatibilitate
function getDB() {
    global $servername, $username, $password, $dbname;
    try {
        $dbConnection = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbConnection;
    }
    catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
        exit();
    }
}
?>
