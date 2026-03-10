<?php
require 'init.php';
require 'db_connect.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// CSRF check
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: login.php?error=csrf');
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

// Rate limiting: check login_attempts by IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$block = false;
$stmt = $conn->prepare('SELECT attempts, last_attempt FROM login_attempts WHERE ip = ? LIMIT 1');
$stmt->bind_param('s', $ip);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $attempts = (int)$row['attempts'];
    $last = strtotime($row['last_attempt']);
    if ($attempts >= 5 && (time() - $last) < (15 * 60)) {
        $block = true;
    }
}
$stmt->close();

if ($block) {
    header('Location: login.php?error=locked');
    exit();
}

$stmt = $conn->prepare('SELECT id, username, email, password_hash, avatar_path FROM users WHERE email = ? OR username = ? LIMIT 1');
$stmt->bind_param('ss', $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();
if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password_hash'])) {
        // login success: clear attempts and set session
        $stmt->close();
        $stmt2 = $conn->prepare('DELETE FROM login_attempts WHERE ip = ?');
        $stmt2->bind_param('s', $ip);
        $stmt2->execute();
        $stmt2->close();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['avatar_path'] = $user['avatar_path'];

        // Admin detection (full permissions) - controlled via env vars:
        // CYBERGURAD_ADMIN_EMAILS="admin@example.com,other@example.com"
        // CYBERGURAD_ADMIN_USERNAMES="admin,superuser"
        $_SESSION['is_admin'] = auth_compute_is_admin($user['email'], $user['username']) ? 1 : 0;

        if (!empty($_SESSION['is_admin'])) {
            header('Location: admin_dashboard.php');
        } else {
            header('Location: profile.php');
        }
        exit();
} else {
        $stmt->close();
    }
} else {
    $stmt->close();
}

// On failure, increment attempts
$stmt = $conn->prepare('SELECT id, attempts FROM login_attempts WHERE ip = ? LIMIT 1');
$stmt->bind_param('s', $ip);
$stmt->execute();
$r = $stmt->get_result();
if ($row = $r->fetch_assoc()) {
    $id = $row['id'];
    $attempts = (int)$row['attempts'] + 1;
    $stmt->close();
    $stmt2 = $conn->prepare('UPDATE login_attempts SET attempts = ?, last_attempt = ? WHERE id = ?');
    $now = date('Y-m-d H:i:s');
    $stmt2->bind_param('isi', $attempts, $now, $id);
    $stmt2->execute();
    $stmt2->close();
} else {
    $stmt->close();
    $now = date('Y-m-d H:i:s');
    $stmt2 = $conn->prepare('INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, ?)');
    $stmt2->bind_param('ss', $ip, $now);
    $stmt2->execute();
    $stmt2->close();
}

header('Location: login.php?error=1');
exit();
?>