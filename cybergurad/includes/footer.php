<?php
if (!defined('CYBERGURAD_FOOTER_INCLUDED')) {
    define('CYBERGURAD_FOOTER_INCLUDED', true);
?>

<!-- ====== FOOTER ====== -->
    </main>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> CyberGuard IoT | All Rights Reserved</p>
    </footer>

<!-- ====== js ====== -->
     <script src="hamburger.js"></script>

<?php
// Ensure session is closed and output buffer flushed on every page
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
if (ob_get_level()) {
    ob_end_flush();
}
?>

<?php if (file_exists(__DIR__ . '/floating_auth.php')) include __DIR__ . '/floating_auth.php'; ?>

</body>
</html>

<?php
} // end footer guard
?>