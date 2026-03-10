<?php
// سكربت تهيئة قواعد البيانات والجداول وإضافة مستخدم أدمن أولي
require_once __DIR__ . '/../includes/db.php';
$mysqli = db_connect();

// users table
$mysqli->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// scan_logs table
$mysqli->query("CREATE TABLE IF NOT EXISTS scan_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target VARCHAR(255),
    result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// settings table
$mysqli->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT
)");

// Create initial admin user named 'you' with password 'ChangeMe123!'
$username = 'you';
$password = 'ChangeMe123!';
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
if (!$res->fetch_assoc()) {
    $ins = $mysqli->prepare('INSERT INTO users (username,password,role) VALUES (?,?,"admin")');
    $ins->bind_param('ss', $username, $hash);
    $ins->execute();
    echo "Admin user 'you' created with password 'ChangeMe123!'. Please change after login.<br>";
} else {
    echo "Admin user 'you' already exists.<br>";
}

echo "Done.";
