<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
$mysqli = db_connect();
$res = $mysqli->query('SELECT id,target,result,created_at FROM scan_logs ORDER BY created_at DESC LIMIT 500');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Scan Logs</title></head><body>
<h2>سجلات الفحص</h2>
<table border="1" cellpadding="6"><tr><th>ID</th><th>Target</th><th>Result</th><th>Time</th></tr>
<?php while ($r = $res->fetch_assoc()): ?>
    <tr>
        <td><?=htmlspecialchars($r['id'])?></td>
        <td><?=htmlspecialchars($r['target'])?></td>
        <td><pre style="max-width:600px;white-space:pre-wrap"><?=htmlspecialchars($r['result'])?></pre></td>
        <td><?=htmlspecialchars($r['created_at'])?></td>
    </tr>
<?php endwhile; ?></table>
<p><a href="dashboard.php">عودة</a></p>
</body></html>
