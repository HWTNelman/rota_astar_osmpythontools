<?php
require_once __DIR__ . '/config_loader.php'; 
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "log_baa";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  die("Erro na conexão: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>