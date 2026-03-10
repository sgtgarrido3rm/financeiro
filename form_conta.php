<?php
require 'conexao.php';

$id = $_GET['id'] ?? null;
$conta = null;

if ($id) {
    $result = $conn->query("SELECT * FROM contas WHERE id = $id");
    $conta = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Conta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
<h3><?= $id ? "Editar Conta" : "Nova Conta" ?></h3>

<form action="salvar_conta.php" method="POST">
<input type="hidden" name="id" value="<?= $conta['id'] ?? '' ?>">

<div class="mb-3">
<label>Descrição</label>
<input type="text" name="descricao" class="form-control"
value="<?= $conta['descricao'] ?? '' ?>" required>
</div>

<div class="mb-3">
<label>Tipo</label>
<select name="tipo" class="form-control">
<option value="recorrente">Recorrente</option>
<option value="cartao">Cartão de Crédito</option>
</select>
</div>

<div class="mb-3">
<label>Valor</label>
<input type="number" step="0.01" name="valor" class="form-control"
value="<?= $conta['valor'] ?? '' ?>" required>
</div>

<div class="mb-3">
<label>Data de Vencimento</label>
<input type="date" name="data_vencimento" class="form-control"
value="<?= $conta['data_vencimento'] ?? '' ?>" required>
</div>

<div class="form-check mb-3">
<input type="checkbox" name="pago" class="form-check-input"
<?= isset($conta['pago']) && $conta['pago'] ? 'checked' : '' ?>>
<label class="form-check-label">Pago</label>
</div>

<button type="submit" class="btn btn-success">Salvar</button>
<a href="index.php" class="btn btn-secondary">Voltar</a>

</form>
</div>
</body>
</html>