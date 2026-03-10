<?php
require 'init.php';
require_once 'auth.php';
auth_require_admin();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_users.php');
    exit();
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    auth_flash_set('admin_notice', 'Security check failed (CSRF).');
    header('Location: admin_users.php');
    exit();
}

$action = $_POST['action'] ?? '';
$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    auth_flash_set('admin_notice', 'Invalid user.');
    header('Location: admin_users.php');
    exit();
}

// Block self-delete (safety)
if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId && $action === 'delete_user') {
    auth_flash_set('admin_notice', 'You cannot delete your own account while signed in.');
    header('Location: admin_users.php');
    exit();
}

if ($action === 'toggle_verify') {
    // Read current value
    $stmt = $conn->prepare("SELECT email_verified, username, email FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if (!$row) {
        auth_flash_set('admin_notice', 'User not found.');
        header('Location: admin_users.php');
        exit();
    }

    $newVal = empty($row['email_verified']) ? 1 : 0;
    $stmt2 = $conn->prepare("UPDATE users SET email_verified=? WHERE id=?");
    $stmt2->bind_param('ii', $newVal, $userId);
    $stmt2->execute();
    $stmt2->close();

    auth_flash_set('admin_notice', $newVal ? 'User marked as verified.' : 'User marked as not verified.');
    header('Location: admin_users.php');
    exit();
}

if ($action === 'generate_reset') {
    // Generate reset token and expiry (1 hour)
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id=?");
    $stmt->bind_param('ssi', $token, $expires, $userId);
    $stmt->execute();
    $stmt->close();

    // Get user info for display
    $stmt2 = $conn->prepare("SELECT username, email FROM users WHERE id=? LIMIT 1");
    $stmt2->bind_param('i', $userId);
    $stmt2->execute();
    $res = $stmt2->get_result();
    $row = $res->fetch_assoc();
    $stmt2->close();

    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $base = $proto . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $link = $base . '/reset_password.php?token=' . $token;

    auth_flash_set('admin_notice', 'Reset link generated (expires in 1 hour).');
    auth_flash_set('reset_link', $link);
    $label = ($row ? (($row['username'] ?? '') . ' <' . ($row['email'] ?? '') . '>') : ('User ID ' . $userId));
    auth_flash_set('reset_user', $label);

    header('Location: admin_users.php');
    exit();
}

if ($action === 'delete_user') {
    // Delete user record
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    auth_flash_set('admin_notice', $affected ? 'User deleted.' : 'User not found (nothing deleted).');
    header('Location: admin_users.php');
    exit();
}

auth_flash_set('admin_notice', 'Unknown action.');
header('Location: admin_users.php');
exit();
?>
