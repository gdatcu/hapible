<?php
$host = "localhost";
$user = "root";
$password = "qazXSW@1393";
$database = "gbrmlvka_hapible";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
