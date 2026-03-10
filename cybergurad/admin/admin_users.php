<?php
$pageTitle = "CyberGuard IoT - Admin Users";
include 'header.php';
require_once 'auth.php';
auth_require_admin();
require 'db_connect.php';

$flash = auth_flash_get('admin_notice');

$search = trim($_GET['search'] ?? '');
$searchSql = '';
$params = [];
$types = '';

if ($search !== '') {
    $searchSql = "WHERE username LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%') ";
    $params = [$search, $search];
    $types = 'ss';
}

$sql = "SELECT id, username, email, email_verified, avatar_path, reset_expires FROM users $searchSql ORDER BY id DESC LIMIT 500";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Query preparation failed: ' . htmlspecialchars($conn->error));
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$users = [];
while ($row = $res->fetch_assoc()) { $users[] = $row; }
$stmt->close();

$csrf = $_SESSION['csrf_token'] ?? '';
?>
<style>
  .admin-wrap{max-width:1200px;margin:0 auto;padding:30px 20px}
  .topbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}
  .topbar h1{margin:0;color:var(--white);font-size:2rem}
  .searchbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
  .searchbar input{
    padding:12px 14px;border-radius:12px;border:1px solid rgba(255,255,255,0.16);
    background: var(--dark-blue);color:var(--white);min-width:260px;
  }
  .searchbar button{
    padding:12px 14px;border-radius:12px;border:0;background:var(--cyan);color:#05232c;font-weight:700;cursor:pointer;
  }
  .searchbar a{color:var(--light-gray);text-decoration:none}
  .notice{
    background: rgba(0, 255, 255, 0.10);
    border: 1px solid rgba(0, 255, 255, 0.22);
    color: var(--white);
    padding: 14px 16px;
    border-radius: 14px;
    margin-bottom: 16px;
  }
  .table-wrap{background: var(--medium-blue);border-radius:16px;padding:18px;border:1px solid rgba(255,255,255,0.08);overflow:auto}
  table{width:100%;border-collapse:collapse;min-width:900px}
  th,td{padding:12px 12px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.08);color:var(--white)}
  th{color:var(--light-gray);font-weight:600;opacity:.9}
  .badge{
    display:inline-block;padding:6px 10px;border-radius:999px;font-size:.85rem;font-weight:700;
    background: rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.14);
  }
  .badge.ok{background: rgba(0,255,255,0.12);border-color: rgba(0,255,255,0.25);}
  .actions{display:flex;gap:8px;flex-wrap:wrap}
  .btn{
    border:0;border-radius:12px;padding:9px 11px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;
    background: rgba(255,255,255,0.10);color:var(--white);border:1px solid rgba(255,255,255,0.14);
  }
  .btn.primary{background: var(--cyan);color:#05232c;border-color: rgba(0,0,0,0.0);}
  .btn.danger{background: rgba(255, 67, 67, 0.14);border-color: rgba(255,67,67,0.28);}
  .btn:hover{opacity:.92}
  .small{font-size:.9rem;color:rgba(255,255,255,0.8)}
  .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace}
  .reset-box{
    margin-top:18px;background: rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.10);
    border-radius:14px;padding:14px;color:var(--white)
  }
  .reset-box code{word-break:break-all}
</style>

<div class="admin-wrap">
  <div class="topbar">
    <div>
      <h1>Users</h1>
      <div class="small">Admin tools for account management</div>
    </div>
    <form class="searchbar" method="get" action="admin_users.php">
      <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by username or email">
      <button type="submit">Search</button>
      <a href="admin_dashboard.php">Back to Admin</a>
    </form>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="notice"><?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <?php $resetLink = auth_flash_get('reset_link'); $resetUser = auth_flash_get('reset_user'); ?>
  <?php if (!empty($resetLink)): ?>
    <div class="reset-box">
      <div style="font-weight:800;margin-bottom:8px">Password Reset Link Generated</div>
      <div class="small" style="margin-bottom:10px">User: <span class="mono"><?php echo htmlspecialchars($resetUser ?? ''); ?></span></div>
      <div class="mono"><code id="resetLink"><?php echo htmlspecialchars($resetLink); ?></code></div>
      <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn primary" type="button" onclick="copyReset()">Copy Link</button>
        <a class="btn" href="<?php echo htmlspecialchars($resetLink); ?>" target="_blank" rel="noopener">Open</a>
      </div>
    </div>
    <script>
      function copyReset(){
        const el = document.getElementById('resetLink');
        if(!el) return;
        const t = el.innerText;
        navigator.clipboard.writeText(t).then(()=>{ alert('Copied'); }).catch(()=>{});
      }
    </script>
  <?php endif; ?>

  <div class="table-wrap" style="margin-top:18px">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Email</th>
          <th>Verified</th>
          <th>Reset Expires</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($users)): ?>
        <tr><td colspan="6" class="small">No users found.</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td class="mono"><?php echo (int)$u['id']; ?></td>
            <td>
              <div style="font-weight:800"><?php echo htmlspecialchars($u['username'] ?? ''); ?></div>
              <div class="small mono"><?php echo !empty($u['avatar_path']) ? htmlspecialchars($u['avatar_path']) : '—'; ?></div>
            </td>
            <td class="mono"><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
            <td>
              <?php if (!empty($u['email_verified'])): ?>
                <span class="badge ok">Verified</span>
              <?php else: ?>
                <span class="badge">Not verified</span>
              <?php endif; ?>
            </td>
            <td class="mono small"><?php echo !empty($u['reset_expires']) ? htmlspecialchars($u['reset_expires']) : '—'; ?></td>
            <td>
              <div class="actions">
                <form method="post" action="process_admin_user.php" style="margin:0;display:inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                  <input type="hidden" name="action" value="toggle_verify">
                  <button class="btn" type="submit"><?php echo !empty($u['email_verified']) ? 'Unverify' : 'Verify'; ?></button>
                </form>

                <form method="post" action="process_admin_user.php" style="margin:0;display:inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                  <input type="hidden" name="action" value="generate_reset">
                  <button class="btn primary" type="submit">Generate Reset</button>
                </form>

                <form method="post" action="process_admin_user.php" style="margin:0;display:inline" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                  <input type="hidden" name="action" value="delete_user">
                  <button class="btn danger" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'footer.php'; ?>
