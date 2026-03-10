<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$mysqli = db_connect();

// Ensure settings table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pubkey = $_POST['public_key'] ?? '';
    $prompt = $_POST['prompt'] ?? '';
    $stmt = $mysqli->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    $k1 = 'public_key'; $k2 = 'prompt';
    $stmt->bind_param('ss', $k1, $pubkey);
    $stmt->execute();
    $stmt->bind_param('ss', $k2, $prompt);
    $stmt->execute();
    header('Location: settings.php');
    exit;
}

$get = $mysqli->query("SELECT `key`,`value` FROM settings WHERE `key` IN ('public_key','prompt')");
$settings = ['public_key'=>'','prompt'=>''];
while ($row = $get->fetch_assoc()) $settings[$row['key']] = $row['value'];
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Settings</title></head><body>
<h2>إعدادات الموقع</h2>
<form method="post">
    <label>المفتاح العام:<br><textarea name="public_key" rows="6" cols="80"><?=htmlspecialchars($settings['public_key'])?></textarea></label><br>
    <label>البرمبت (Prompt):<br><textarea name="prompt" rows="6" cols="80"><?=htmlspecialchars($settings['prompt'])?></textarea></label><br>
    <button>حفظ</button>
</form>
<p><a href="dashboard.php">عودة</a></p>
</body></html>
