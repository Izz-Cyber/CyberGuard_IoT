<?php
// init.php - central session and output buffering initialization
// Start output buffering as early as possible
if (!ob_get_level()) {
    ob_start();
}

// Try to set secure session cookie settings only if headers not yet sent
if (!headers_sent()) {
    @ini_set('session.cookie_httponly', 1);
    @ini_set('session.use_only_cookies', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        @ini_set('session.cookie_secure', 1);
    }
} else {
    error_log('init.php: headers already sent before session cookie ini_set; skipped ini_set calls.');
}

// Start session if not active. Suppress warnings and log if it fails.
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        @session_start();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log('init.php: session_start() failed even though headers not sent.');
        }
    } else {
        // Cannot start session after headers sent: log for debugging
        error_log('init.php: cannot start session because headers were already sent.');
    }
}

// CSRF token: generate once per session (only if session is active)
if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

?>
