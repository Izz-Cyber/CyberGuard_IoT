<?php
// Simple mailer wrapper: tries PHPMailer via Composer if available, otherwise falls back to mail().
$config = [];
if (file_exists(__DIR__ . '/mail_config.php')) {
    $config = include __DIR__ . '/mail_config.php';
}

function mailer_send($to, $subject, $body, $isHtml = false) {
    global $config;
    // Try to use PHPMailer if composer autoload exists
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            if (!empty($config['use_smtp'])) {
                $mail->isSMTP();
                $mail->Host = $config['smtp']['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $config['smtp']['username'];
                $mail->Password = $config['smtp']['password'];
                $mail->SMTPSecure = $config['smtp']['encryption'] ?: '';
                $mail->Port = $config['smtp']['port'];
            }
            $from = $config['from']['email'] ?? 'no-reply@localhost';
            $fromName = $config['from']['name'] ?? '';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            if ($isHtml) {
                $mail->isHTML(true);
                $mail->Body = $body;
            } else {
                $mail->Body = $body;
            }
            $mail->send();
            return true;
        } catch (Exception $e) {
            // fallback to mail()
        }
    }

    // Fallback: simple mail()
    $headers = [];
    $from = $config['from']['email'] ?? 'no-reply@localhost';
    $headers[] = 'From: ' . $from;
    if ($isHtml) $headers[] = 'MIME-Version: 1.0';
    if ($isHtml) $headers[] = 'Content-type: text/html; charset=utf-8';
    $hdr = implode("\r\n", $headers);
    $to_clean = filter_var($to, FILTER_SANITIZE_EMAIL);
    if (!filter_var($to_clean, FILTER_VALIDATE_EMAIL)) return false;
    return @mail($to_clean, $subject, $body, $hdr);
}

?>