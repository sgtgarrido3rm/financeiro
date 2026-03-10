<?php
require 'conexao.php';

$id = $_POST['id'] ?? null;
$descricao = $_POST['descricao'];
$tipo = $_POST['tipo'];
$valor = $_POST['valor'];
$data = $_POST['data_vencimento'];
$pago = isset($_POST['pago']) ? 1 : 0;

if ($id) {
    $sql = "UPDATE contas SET 
            descricao='$descricao',
            tipo='$tipo',
            valor='$valor',
            data_vencimento='$data',
            pago='$pago'
            WHERE id=$id";
} else {
    $sql = "INSERT INTO contas 
            (descricao,tipo,valor,data_vencimento,pago)
            VALUES 
            ('$descricao','$tipo','$valor','$data','$pago')";
}

$conn->query($sql);

header("Location: index.php");
exit;