<?php
require 'conexao.php';

$id = $_GET['id'];
$conn->query("DELETE FROM contas WHERE id=$id");

header("Location: index.php");
exit;