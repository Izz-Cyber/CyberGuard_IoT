<?php
// Simple localhost-only admin creation/upsert tool.
// USE ONLY ON LOCALHOST. Remove this file after use.

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/db_connect.php';

// Restrict to CLI or localhost
$isCli = (php_sapi_name() === 'cli');
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (! $isCli && !in_array($ip, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    echo "Forbidden: create_admin may only be run from localhost.";
    exit;
}

function ensure_role_column($conn) {
    $col = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($col && $col->num_rows === 0) {
        // best-effort; ignore failures
        @$conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'user'");
    }
}

ensure_role_column($conn);

// If run from CLI, accept args: php create_admin.php email username password
if ($isCli) {
    $email = $argv[1] ?? null;
    $username = $argv[2] ?? null;
    $password = $argv[3] ?? null;
    if (!$email || !$username || !$password) {
        echo "Usage: php create_admin.php email username password\n";
        exit(1);
    }
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
    } else {
        // show simple form
        echo '<!doctype html><meta charset="utf-8"><title>Create Admin</title>';
        echo '<h2>Create or promote an admin account (localhost only)</h2>';
        echo '<form method="post"><label>Email:<br><input name="email"></label><br><label>Username:<br><input name="username"></label><br><label>Password:<br><input type="password" name="password"></label><br><button>Create Admin</button></form>';
        echo '<p>After success, delete this file: includes/create_admin.php</p>';
        exit;
    }
}

if (empty($email) || empty($username) || empty($password)) {
    echo "Missing parameters. Aborting.";
    exit;
}

$email = strtolower($email);
$username = $username;
$pwHash = password_hash($password, PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');

// Try to find existing user by email or username
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
if ($stmt === false) { echo "DB prepare failed: " . htmlspecialchars($conn->error); exit; }
$stmt->bind_param('ss', $email, $username);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $uid = (int)$row['id'];
    $stmt->close();
    $up = $conn->prepare('UPDATE users SET password_hash = ?, role = ? WHERE id = ?');
    if ($up === false) { echo "DB prepare failed: " . htmlspecialchars($conn->error); exit; }
    $role = 'admin';
    $up->bind_param('ssi', $pwHash, $role, $uid);
    if ($up->execute()) {
        echo "User promoted to admin (id={$uid}).\n";
    } else {
        echo "Failed to update user: " . htmlspecialchars($conn->error) . "\n";
    }
    $up->close();
} else {
    $stmt->close();
    // Insert new user
    $ins = $conn->prepare('INSERT INTO users (username, email, password_hash, avatar_path, created_at, role) VALUES (?, ?, ?, NULL, ?, ?)');
    if ($ins === false) { echo "DB prepare failed: " . htmlspecialchars($conn->error); exit; }
    $role = 'admin';
    $ins->bind_param('sssss', $username, $email, $pwHash, $now, $role);
    if ($ins->execute()) {
        echo "Admin user created (id=" . (int)$ins->insert_id . ").\n";
    } else {
        echo "Failed to create user: " . htmlspecialchars($conn->error) . "\n";
    }
    $ins->close();
}

// Close connection
$conn->close();

if (! $isCli) {
    echo '<p>Done. Remove <code>includes/create_admin.php</code> to avoid leaving an open tool on your server.</p>';
}

?>
