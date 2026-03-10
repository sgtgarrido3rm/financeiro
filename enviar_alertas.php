<?php
require 'conexao.php';

// =========================
// CARREGAR PHPMailer MANUAL
// =========================
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// =========================
// BUSCAR CONTAS QUE VENCEM EM 5 DIAS
// =========================
$sql = "
SELECT *
FROM contas
WHERE pago = 0
AND DATEDIFF(data_vencimento, CURDATE()) = 5
";

$result = $conn->query($sql);

if ($result->num_rows > 0) {

    while ($conta = $result->fetch_assoc()) {

        $mail = new PHPMailer(true);

        try {

            // =========================
            // CONFIG SMTP GMAIL
            // =========================
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sgtgarrido3rm@gmail.com';
            $mail->Password   = 'Am@ndita100%';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // =========================
            // REMETENTE E DESTINO
            // =========================
            $mail->setFrom('sgtgarrido3rm@gmail.com', 'Sistema Financeiro');
            $mail->addAddress('sgtgarrido3rm@gmail.com');

            // =========================
            // CONTEÚDO
            // =========================
            $mail->isHTML(true);
            $mail->Subject = '⚠ Conta vencendo em 5 dias';

            $mail->Body = "
                <h2>Alerta de Vencimento</h2>
                <p><strong>Descrição:</strong> {$conta['descricao']}</p>
                <p><strong>Valor:</strong> R$ " . number_format($conta['valor'], 2, ',', '.') . "</p>
                <p><strong>Vencimento:</strong> " . date('d/m/Y', strtotime($conta['data_vencimento'])) . "</p>
                <hr>
                <p style='color:red;'><strong>Atenção:</strong> Faltam 5 dias para o vencimento.</p>
            ";

            $mail->send();

        } catch (Exception $e) {
            echo "Erro ao enviar: {$mail->ErrorInfo}";
        }
    }
}