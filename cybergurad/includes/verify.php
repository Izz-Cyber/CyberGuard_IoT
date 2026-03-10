<?php
require 'init.php';
require 'db_connect.php';
$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: login.php?error=invalid');
    exit();
}
$stmt = $conn->prepare('SELECT id FROM users WHERE verification_token = ? LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $uid = $row['id'];
    $stmt->close();
    $stmt2 = $conn->prepare('UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?');
    $stmt2->bind_param('i', $uid);
    $stmt2->execute();
    $stmt2->close();
    header('Location: login.php?verified=1');
    exit();
}
$stmt->close();
header('Location: login.php?error=invalid');
exit();
?>