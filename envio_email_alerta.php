<?php
require 'conexao.php';

/* CONFIGURAÇÕES */
$destinatario = "sgtgarrido3rm@gmail.com";
$remetente = "garrido@gis-rs.info";

$hoje = date('Y-m-d');
$limite = date('Y-m-d', strtotime('+5 days'));

$sql = "
SELECT * FROM contas
WHERE pago = 0
AND notificar = 1
AND data_vencimento BETWEEN '$hoje' AND '$limite'
AND (ultima_notificacao IS NULL OR ultima_notificacao < '$hoje')
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($conta = $result->fetch_assoc()){

        $diasRestantes = (strtotime($conta['data_vencimento']) - strtotime($hoje)) / 86400;

        $assunto = "⚠️ Conta vencendo em $diasRestantes dia(s)";
        
        $mensagem = "
        <h2>Alerta de Conta</h2>
        <p><strong>Descrição:</strong> {$conta['descricao']}</p>
        <p><strong>Valor:</strong> R$ ".number_format($conta['valor'],2,',','.')."</p>
        <p><strong>Vencimento:</strong> ".date('d/m/Y', strtotime($conta['data_vencimento']))."</p>
        <p><strong>Status:</strong> Em aberto</p>
        <hr>
        <p>Este e-mail será enviado diariamente até que a conta seja marcada como paga.</p>
        ";
        
        $headers = "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: Alerta de Conta <$remetente>";

        if(mail($destinatario, $assunto, $mensagem, $headers)){
            echo "E-mail enviado com sucesso!";
            $conn->query("
                UPDATE contas 
                SET ultima_notificacao = '$hoje'
                WHERE id = {$conta['id']}
            ");
        } else {
            echo "Falha no envio.";
        }
    } 
}
?>