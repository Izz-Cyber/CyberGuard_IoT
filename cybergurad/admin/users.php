<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$mysqli = db_connect();

// Simple actions: create, delete, edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';
        if ($username && $password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO users (username,password,role) VALUES (?,?,?)');
            $stmt->bind_param('sss', $username, $hash, $role);
            $stmt->execute();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $mysqli->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
    } elseif ($action === 'update') {
        $id = intval($_POST['id']);
        $role = $_POST['role'] ?? 'user';
        $stmt = $mysqli->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $role, $id);
        $stmt->execute();
    }
    header('Location: users.php');
    exit;
}

$res = $mysqli->query('SELECT id,username,role,created_at FROM users ORDER BY id DESC');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Users</title></head><body>
<h2>المستخدمون</h2>
<table border="1" cellpadding="6"><tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr>
<?php while ($row = $res->fetch_assoc()): ?>
    <tr>
        <td><?=htmlspecialchars($row['id'])?></td>
        <td><?=htmlspecialchars($row['username'])?></td>
        <td><?=htmlspecialchars($row['role'])?></td>
        <td><?=htmlspecialchars($row['created_at'])?></td>
        <td>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?=htmlspecialchars($row['id'])?>">
                <button onclick="return confirm('تأكيد الحذف؟')">حذف</button>
            </form>
            <form method="post" style="display:inline">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?=htmlspecialchars($row['id'])?>">
                <select name="role">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                </select>
                <button>تحديث</button>
            </form>
        </td>
    </tr>
<?php endwhile; ?>
</table>

<h3>إنشاء مستخدم جديد</h3>
<form method="post">
    <input type="hidden" name="action" value="create">
    <label>اسم المستخدم: <input name="username" required></label><br>
    <label>كلمة المرور: <input name="password" type="password" required></label><br>
    <label>الصلاحية: <select name="role"><option value="user">user</option><option value="admin">admin</option></select></label><br>
    <button>إنشاء</button>
</form>

<p><a href="dashboard.php">عودة</a></p>
</body></html>
