<?php
require 'init.php';
require_once 'auth.php';
auth_require_admin();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_assessments.php');
    exit();
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    auth_flash_set('admin_notice', 'Security check failed (CSRF).');
    header('Location: admin_assessments.php');
    exit();
}

$action = $_POST['action'] ?? '';
$assessmentId = (int)($_POST['assessment_id'] ?? 0);
if ($assessmentId <= 0) {
    auth_flash_set('admin_notice', 'Invalid assessment.');
    header('Location: admin_assessments.php');
    exit();
}

if ($action === 'delete_assessment') {
    // Only delete from assessments; keep device record intact
    $stmt = $conn->prepare("DELETE FROM assessments WHERE id=?");
    $stmt->bind_param('i', $assessmentId);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    auth_flash_set('admin_notice', $affected ? 'Assessment deleted.' : 'Assessment not found (nothing deleted).');
    header('Location: admin_assessments.php');
    exit();
}

auth_flash_set('admin_notice', 'Unknown action.');
header('Location: admin_assessments.php');
exit();
?>
