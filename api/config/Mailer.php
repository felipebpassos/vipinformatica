<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    public static function sendWelcomeEmail($toEmail, $toName, $password, $serviceType) {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'];
            $mail->Port       = $_ENV['SMTP_PORT'];

            $mail->setFrom($_ENV['FROM_EMAIL'], $_ENV['FROM_NAME']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = 'Confirmação de Abertura de Chamado - VIP Informática';
            $mail->Body    = "
                <h1>Seu chamado foi registrado!</h1>
                <p>Olá {$toName},</p>
                <p>Confirmamos a abertura do seu chamado para:</p>
                <p><strong>Serviço:</strong> {$serviceType}</p>
                <p>Nossa equipe entrará em contato em breve para dar andamento ao seu atendimento.</p>
                <p>Acompanhe seu chamado através do nosso sistema: 
                <a href='{$_ENV['LOGIN_URL']}'>{$_ENV['LOGIN_URL']}</a></p>
                <p>Sua senha de acesso:</p>
                <p><strong>Senha:</strong> {$password}</p>
                <p>Atenciosamente,<br>Equipe VIP Informática</p>
            ";
            
            $mail->AltBody = "Chamado aberto para: {$serviceType}\n\nNossa equipe entrará em contato em breve.\n\nAcesse: {$_ENV['LOGIN_URL']}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            throw new Exception("Falha ao enviar email: " . $mail->ErrorInfo);
        }
    }
}