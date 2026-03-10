<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if (login_with_credentials($user, $pass)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'بيانات الدخول غير صحيحة';
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin Login</title></head>
<body>
<h2>تسجيل دخول الأدمن</h2>
<?php if ($error): ?><p style="color:red"><?=htmlspecialchars($error) ?></p><?php endif; ?>
<form method="post">
    <label>اسم المستخدم: <input name="username" required></label><br>
    <label>كلمة المرور: <input name="password" type="password" required></label><br>
    <button type="submit">دخول</button>
</form>
</body>
</html>
