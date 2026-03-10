<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$aboutFile = __DIR__ . '/../includes/about.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';
    // Basic safety: write only if writable
    if (is_writable($aboutFile) || (!file_exists($aboutFile) && is_writable(dirname($aboutFile)))) {
        file_put_contents($aboutFile, $content);
        $message = 'تم الحفظ.';
    } else {
        $message = 'لا يمكن الكتابة على الملف. تحقق من أذونات الملف.';
    }
}
$current = '';
if (file_exists($aboutFile)) {
    $current = file_get_contents($aboutFile);
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Edit About</title></head><body>
<h2>تعديل صفحة about</h2>
<?php if ($message): ?><p><?=htmlspecialchars($message)?></p><?php endif; ?>
<form method="post">
    <textarea name="content" rows="20" cols="80"><?=htmlspecialchars($current)?></textarea><br>
    <button>حفظ</button>
</form>
<p><a href="dashboard.php">عودة</a></p>
</body></html>
