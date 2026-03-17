<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

function sendMail(string $subject, string $body): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST']      ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER']      ?? '';
        $mail->Password   = $_ENV['SMTP_PASS']      ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);

        $mail->setFrom(
            $_ENV['SMTP_FROM']      ?? '',
            $_ENV['SMTP_FROM_NAME'] ?? 'Framenode QR'
        );
        $mail->addAddress($_ENV['NOTIFY_EMAIL'] ?? '');

        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return false;
    }
}
