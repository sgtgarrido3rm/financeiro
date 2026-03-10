<?php
require 'conexao.php';
require 'fpdf/fpdf.php';

$mes = $_GET['mes'] ?? null;
$tipoRelatorio = $_GET['tipo'] ?? 'detalhado';

$where = "";
if($mes){
    $where = "WHERE DATE_FORMAT(data_vencimento, '%Y-%m') = '$mes'";
}

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFillColor(30,30,30);
        $this->SetTextColor(255,255,255);
        $this->SetFont('Arial','B',14);
        $this->Cell(0,12,'RELATORIO FINANCEIRO',0,1,'C',true);
        $this->Ln(5);

        $this->SetTextColor(0,0,0);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Arial','',10);

$sql = "SELECT * FROM contas $where ORDER BY tipo, data_vencimento";
$result = $conn->query($sql);

$contas = [];
$totalPago = 0;
$totalAberto = 0;
$totalGeral = 0;

while($row = $result->fetch_assoc()){
    $contas[$row['tipo']][] = $row;

    $totalGeral += $row['valor'];
    if($row['pago']){
        $totalPago += $row['valor'];
    } else {
        $totalAberto += $row['valor'];
    }
}

# =========================
# RESUMO NO TOPO
# =========================
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(230,230,230);

$pdf->Cell(63,10,'Total Geral',1,0,'C',true);
$pdf->Cell(63,10,'Total Pago',1,0,'C',true);
$pdf->Cell(63,10,'Total em Aberto',1,1,'C',true);

$pdf->SetFont('Arial','',11);

$pdf->Cell(63,10,'R$ '.number_format($totalGeral,2,',','.'),1,0,'C');
$pdf->Cell(63,10,'R$ '.number_format($totalPago,2,',','.'),1,0,'C');
$pdf->Cell(63,10,'R$ '.number_format($totalAberto,2,',','.'),1,1,'C');

$pdf->Ln(10);

# =========================
# FUNÇÃO PARA TABELAS
# =========================
function tabelaDetalhada($pdf, $dados, $titulo){

    $pdf->SetFont('Arial','B',12);
    $pdf->SetTextColor(40,40,40);
    $pdf->Cell(0,8,utf8_decode($titulo),0,1);
    $pdf->Ln(2);

    $pdf->SetFillColor(52, 152, 219);
    $pdf->SetTextColor(255);
    $pdf->SetFont('Arial','B',9);

    $pdf->Cell(60,8,'Descricao',1,0,'C',true);
    $pdf->Cell(30,8,'Valor',1,0,'C',true);
    $pdf->Cell(35,8,'Vencimento',1,0,'C',true);
    $pdf->Cell(25,8,'Status',1,1,'C',true);

    $pdf->SetFont('Arial','',9);
    $pdf->SetTextColor(0);

    foreach($dados as $row){

        $status = $row['pago'] ? 'Pago' : 'Aberto';

        if(!$row['pago']){
            $pdf->SetFillColor(255,230,230);
            $fill = true;
        } else {
            $fill = false;
        }

        $pdf->Cell(60,8,utf8_decode($row['descricao']),1,0,'L',$fill);
        $pdf->Cell(30,8,'R$ '.number_format($row['valor'],2,',','.'),1,0,'R',$fill);
        $pdf->Cell(35,8,date('d/m/Y', strtotime($row['data_vencimento'])),1,0,'C',$fill);
        $pdf->Cell(25,8,$status,1,1,'C',$fill);
    }

    $pdf->Ln(5);
}

function tabelaResumida($pdf, $dados, $titulo){

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,utf8_decode($titulo),0,1);
    $pdf->Ln(2);

    $subtotal = 0;

    foreach($dados as $row){
        $subtotal += $row['valor'];
    }

    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0,8,'Total: R$ '.number_format($subtotal,2,',','.'),1,1);
    $pdf->Ln(5);
}

# =========================
# GERAR RELATÓRIO
# =========================

if($tipoRelatorio == 'resumido'){

    if(isset($contas['recorrente'])){
        tabelaResumida($pdf, $contas['recorrente'], 'Contas Recorrentes');
    }

    if(isset($contas['cartao'])){
        tabelaResumida($pdf, $contas['cartao'], 'Cartao de Credito');
    }

} else {

    if(isset($contas['recorrente'])){
        tabelaDetalhada($pdf, $contas['recorrente'], 'Contas Recorrentes');
    }

    if(isset($contas['cartao'])){
        tabelaDetalhada($pdf, $contas['cartao'], 'Cartao de Credito');
    }

}

$pdf->Output('I','relatorio_financeiro.pdf');
exit;