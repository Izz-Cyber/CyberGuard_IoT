<?php
require 'init.php';
require 'db_connect.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit(); }
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) { header('Location: login.php?error=csrf'); exit(); }
$token = $_POST['token'] ?? '';
$pass = $_POST['password'] ?? '';
$confirm = $_POST['password_confirm'] ?? '';
if ($pass !== $confirm) { header('Location: reset_password.php?token=' . urlencode($token) . '&error=pw_mismatch'); exit(); }
if (strlen($pass) < 6) { header('Location: reset_password.php?token=' . urlencode($token) . '&error=pw_short'); exit(); }
$stmt = $conn->prepare('SELECT id, reset_expires FROM users WHERE reset_token = ? LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (strtotime($row['reset_expires']) < time()) { $stmt->close(); header('Location: login.php?error=reset_expired'); exit(); }
    $uid = $row['id'];
    $stmt->close();
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt2 = $conn->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
    $stmt2->bind_param('si', $hash, $uid);
    $stmt2->execute();
    $stmt2->close();
    header('Location: login.php?reset=1');
    exit();
}
$stmt->close();
header('Location: login.php?error=invalid');
exit();
?>