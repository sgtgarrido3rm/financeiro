<?php
require 'conexao.php';

$mesAtual = date('Y-m');
$mesAnterior = date('Y-m', strtotime('-1 month'));

function totaisPorMes($conn, $mes){
    $sql = "
        SELECT 
            SUM(valor) AS total_geral,
            SUM(CASE WHEN pago = 1 THEN valor ELSE 0 END) AS total_pago,
            SUM(CASE WHEN pago = 0 THEN valor ELSE 0 END) AS total_aberto
        FROM contas
        WHERE DATE_FORMAT(data_vencimento, '%Y-%m') = '$mes'
    ";
    return $conn->query($sql)->fetch_assoc();
}

$atual = totaisPorMes($conn, $mesAtual);
$anterior = totaisPorMes($conn, $mesAnterior);

function variacao($atual, $anterior){
    if($anterior == 0) return 0;
    return (($atual - $anterior) / $anterior) * 100;
}

$varGeral  = variacao($atual['total_geral'] ?? 0, $anterior['total_geral'] ?? 0);
$varPago   = variacao($atual['total_pago'] ?? 0, $anterior['total_pago'] ?? 0);
$varAberto = variacao($atual['total_aberto'] ?? 0, $anterior['total_aberto'] ?? 0);

/* Histórico últimos 6 meses */
$historico = [];
for($i=5;$i>=0;$i--){
    $mes = date('Y-m', strtotime("-$i month"));
    $dados = totaisPorMes($conn, $mes);
    $historico[] = $dados['total_geral'] ?? 0;
}

/* Alertas 5 dias */
$hoje = date('Y-m-d');
$alertaData = date('Y-m-d', strtotime('+5 days'));
$alertas = $conn->query("
    SELECT * FROM contas 
    WHERE pago = 0 
    AND data_vencimento BETWEEN '$hoje' AND '$alertaData'
");

/* Listagem geral */
$result = $conn->query("SELECT * FROM contas ORDER BY data_vencimento ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Painel Financeiro</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.card {
    transition: transform .2s ease, box-shadow .2s ease;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.sparkline {
    height:50px !important;
}
</style>

</head>
<body class="bg-light">

<div class="container mt-4">

<h2 class="mb-4">Painel Financeiro</h2>

<!-- CARDS -->
<div class="row mb-4">

<?php
function cardComparativo($titulo, $valorAtual, $valorAnterior, $variacao, $corBase, $iconeBase){

    $subiu = $variacao >= 0;
    $icone = $subiu ? "bi-arrow-up" : "bi-arrow-down";
    $corTexto = $subiu ? "text-danger" : "text-success";
    //$bgCircle = $subiu ? "bg-success" : "bg-success";

    echo "
    <div class='col-md-4'>
        <div class='card shadow border-0 $corBase text-white h-100'>
            <div class='card-body'>
                <div class='d-flex justify-content-between align-items-center'>
                    <div>
                        <h6>$titulo</h6>
                        <h4>R$ ".number_format($valorAtual,2,',','.')."</h4>
                        <small>Mês anterior: R$ ".number_format($valorAnterior,2,',','.')."</small><br>

                        <span class='badge bg-white $corTexto fw-bold mt-1'>
                            <i class='bi $icone'></i> 
                            ".number_format(abs($variacao),1,',','.')."% 
                        </span>
                    </div>

                    <div class='rounded-circle p-3 $bgCircle'>
                        <i class='bi $iconeBase text-white fs-4'></i>
                    </div>
                </div>

                <canvas class='sparkline mt-3'></canvas>
            </div>
        </div>
    </div>";
}

cardComparativo("Total Geral",
    $atual['total_geral'] ?? 0,
    $anterior['total_geral'] ?? 0,
    $varGeral,
    "bg-dark",
    "bi-cash-stack"
);

cardComparativo("Total Pago",
    $atual['total_pago'] ?? 0,
    $anterior['total_pago'] ?? 0,
    $varPago,
    "bg-success",
    "bi-check-circle"
);

cardComparativo("Total em Aberto",
    $atual['total_aberto'] ?? 0,
    $anterior['total_aberto'] ?? 0,
    $varAberto,
    "bg-danger",
    "bi-exclamation-circle"
);
?>

</div>

<!-- ALERTAS -->
<div class="row mb-4">
<?php while($a = $alertas->fetch_assoc()): ?>
<div class="col-md-4">
    <div class="card border-warning shadow-sm">
        <div class="card-body">
            <h6 class="text-warning">
                <i class="bi bi-clock-history"></i> Vencendo em breve
            </h6>
            <strong><?= $a['descricao'] ?></strong><br>
            Vence em <?= date('d/m/Y', strtotime($a['data_vencimento'])) ?><br>
            <strong>R$ <?= number_format($a['valor'],2,',','.') ?></strong>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>

<div class="mb-3">
    <a href="form_conta.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nova Conta
    </a>
    <a href="exportar_pdf.php" class="btn btn-secondary" target="_Blank">
        <i class="bi bi-file-earmark-pdf"></i> Relatório Completo
    </a>
    <a href="exportar_pdf.php?mes=<?=Date('Y')?>-<?=Date('m')?>&tipo=detalhado" class="btn btn-secondary" target="_Blank"><i class="bi bi-file-earmark-pdf"></i> Mês Atual Detalhado</a>
    <a href="exportar_pdf.php?mes=<?=Date('Y')?>-<?=Date('m')?>&tipo=resumido" class="btn btn-secondary" target="_Blank"><i class="bi bi-file-earmark-pdf"></i> Mês Atual Resumido</a>
</div>

<table class="table table-striped table-bordered bg-white shadow-sm">
<thead class="table-dark">
<tr>
    <th>Descrição</th>
    <th>Tipo</th>
    <th>Valor</th>
    <th>Vencimento</th>
    <th>Status</th>
    <th>Ações</th>
</tr>
</thead>
<tbody>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['descricao'] ?></td>
    <td><?= ucfirst($row['tipo']) ?></td>
    <td>R$ <?= number_format($row['valor'],2,',','.') ?></td>
    <td><?= date('d/m/Y', strtotime($row['data_vencimento'])) ?></td>
    <td>
        <?= $row['pago'] 
            ? '<span class="badge bg-success">Pago</span>' 
            : '<span class="badge bg-danger">Aberto</span>' ?>
    </td>
    <td>
        <a href="form_conta.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
            <i class="bi bi-pencil"></i>
        </a>
        <a href="excluir_conta.php?id=<?= $row['id'] ?>" 
           onclick="return confirm('Excluir conta?')"
           class="btn btn-sm btn-danger">
            <i class="bi bi-trash"></i>
        </a>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>

<!-- somente linha do gráfico -->
<!--script>
const historico = <?= json_encode($historico) ?>;

document.querySelectorAll('.sparkline').forEach((canvas)=>{
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: ["-5","-4","-3","-2","-1","Atual"],
            datasets: [{
                data: historico,
                borderWidth: 2,
                tension: 0.4,
                pointRadius: 0
            }]
        },
        options: {
            plugins: { legend: { display:false } },
            scales: { x:{display:false}, y:{display:false} },
            responsive:true,
            maintainAspectRatio:false
        }
    });
});
</script-->

<!-- linha do gráfico com preenchimento -->
<script>
const historico = <?= json_encode($historico) ?>;

// calcula valor máximo para escala proporcional
const maxValor = Math.max(...historico);
const limiteSuperior = maxValor * 1.2;

document.querySelectorAll('.sparkline').forEach((canvas)=>{
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: ["-5","-4","-3","-2","-1","Atual"],
            datasets: [{
                data: historico,
                borderWidth: 3,
                tension: 0.4,
                pointRadius: 0.1,
                fill: true
            }]
        },
        options: {
            plugins: { legend: { display:false } },
            scales: {
                x:{ display:false },
                y:{
                    display:false,
                    beginAtZero:true,
                    suggestedMax: limiteSuperior
                }
            },
            elements: {
                line: {
                    borderJoinStyle: 'round'
                }
            },
            responsive:true,
            maintainAspectRatio:false
        }
    });
});
</script>

</body>
</html>