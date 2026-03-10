<?php
require 'init.php';
require 'db_connect.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: forgot.php'); exit(); }
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) { header('Location: forgot.php?error=csrf'); exit(); }
$email = trim($_POST['email'] ?? '');
if (empty($email)) { header('Location: forgot.php?error=1'); exit(); }

$stmt = $conn->prepare('SELECT id, username FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $uid = $row['id'];
    $username = $row['username'];
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);
    $stmt->close();
    $stmt2 = $conn->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?');
    $stmt2->bind_param('ssi', $token, $expires, $uid);
    $stmt2->execute();
    $stmt2->close();

    $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . $token;
    $subject = 'Password reset for CyberGuard';
    $message = "Hello $username,\n\nUse the link below to reset your password (valid 1 hour):\n$resetUrl\n\nIf you didn't request this, ignore this message.";
    if (file_exists(__DIR__ . '/mailer.php')) {
        require_once __DIR__ . '/mailer.php';
        mailer_send($email, $subject, nl2br(htmlspecialchars($message)), true);
    } else {
        @mail($email, $subject, $message);
    }
}
header('Location: login.php?reset_sent=1');
exit();
?>