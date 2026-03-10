<?php
if (!defined('CYBERGURAD_HEADER_INCLUDED')) {
    define('CYBERGURAD_HEADER_INCLUDED', true);
    require 'init.php';
    require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ====== Title ====== -->
    <title><?php echo htmlspecialchars($pageTitle ?? 'CyberGuard IoT'); ?></title>

    <!-- ====== Google Font ====== -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">

    <!-- ====== CSS ====== -->
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- ====== HEADER ====== -->
    <header>
        <nav class="navbar">
            <div class="logo">CyberGuard <span>IoT</span></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="assessment.php">Assessment</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if (function_exists('auth_is_admin') && auth_is_admin()): ?>
                <li><a href="admin_dashboard.php">Admin</a></li>
                <?php endif; ?>
                <li><a href="about.php">About</a></li>
            </ul>
            <button class="hamburger"><span></span></button>
        </nav>
    </header>
    <main class="site-main">
<?php
} // end header guard
?>