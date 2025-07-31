<?php
$servername = "localhost";
// CORECTAT: Am redenumit variabilele pentru a fi consistente
$username = "gbrmlvka_hapibleadm"; 
$password = "qazXSW@1393"; 
$dbname = "gbrmlvka_hapible";

// Create connection
// CORECTAT: Acum folosește variabilele corecte
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
    // CORECTAT: Folosim `global` cu variabilele corecte
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
