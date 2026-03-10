<?php
$host = "localhost";
$user = "root";
$pass = "T3ste1$3";
$db   = "contabilidade";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Define para o fuso horário de São Paulo (Brasília)
date_default_timezone_set('America/Sao_Paulo');

?>