<?php
// Consolidated auth helpers (keeps backward-compatible names)
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function is_logged_in()
{
    return !empty($_SESSION['user_id']);
}

function current_user()
{
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'] ?? 'user'
    ];
}

function require_admin()
{
    if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: login.php');
        exit;
    }
}

function login_with_credentials($username, $password)
{
    $mysqli = db_connect();
    $stmt = $mysqli->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            return true;
        }
    }
    return false;
}

function logout()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }
    session_destroy();
}

// Newer auth_* helpers
function auth_is_logged_in(): bool {
    return is_logged_in();
}

function auth_is_admin(): bool {
    return !empty($_SESSION['is_admin']) || (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

function auth_require_login(string $redirectTo = 'login.php'): void {
    if (!auth_is_logged_in()) {
        header('Location: ' . $redirectTo);
        exit();
    }
}

function auth_require_admin(string $redirectTo = 'dashboard.php?error=forbidden'): void {
    auth_require_login();
    if (!auth_is_admin()) {
        header('Location: ' . $redirectTo);
        exit();
    }
}

function auth_flash_set(string $key, string $value): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['_flash'][$key] = $value;
    }
}

function auth_flash_get(string $key): ?string {
    if (session_status() !== PHP_SESSION_ACTIVE) return null;
    if (empty($_SESSION['_flash']) || !array_key_exists($key, $_SESSION['_flash'])) return null;
    $val = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function auth_admin_list_from_env(string $envName): array {
    $raw = getenv($envName);
    if (!$raw) return [];
    $parts = array_map('trim', explode(',', $raw));
    $parts = array_filter($parts, fn($v) => $v !== '');
    return array_values(array_unique($parts));
}

function auth_compute_is_admin(string $email, string $username): bool {
    $adminEmails = auth_admin_list_from_env('CYBERGURAD_ADMIN_EMAILS');
    $adminUsers  = auth_admin_list_from_env('CYBERGURAD_ADMIN_USERNAMES');

    $singleEmail = getenv('CYBERGURAD_ADMIN_EMAIL');
    if ($singleEmail) $adminEmails[] = trim($singleEmail);

    $emailLower = strtolower($email);
    foreach ($adminEmails as $ae) {
        if ($emailLower === strtolower($ae)) return true;
    }
    foreach ($adminUsers as $au) {
        if (strcasecmp($username, $au) === 0) return true;
    }
    return false;
}

?>
