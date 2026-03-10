<?php
$pageTitle = "CyberGuard IoT - Admin Dashboard";
include 'header.php';
require_once 'auth.php';
auth_require_admin();
require 'db_connect.php';

// Admin quick stats
$users_total = (int)($conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0);
$assess_total = (int)($conn->query("SELECT COUNT(*) AS c FROM assessments")->fetch_assoc()['c'] ?? 0);
$assess_high  = (int)($conn->query("SELECT COUNT(*) AS c FROM assessments WHERE status='High Risk'")->fetch_assoc()['c'] ?? 0);
$assess_med   = (int)($conn->query("SELECT COUNT(*) AS c FROM assessments WHERE status='Medium'")->fetch_assoc()['c'] ?? 0);
$assess_safe  = (int)($conn->query("SELECT COUNT(*) AS c FROM assessments WHERE status='Safe'")->fetch_assoc()['c'] ?? 0);

$flash = auth_flash_get('admin_notice');
?>
<style>
  .admin-wrap{max-width:1200px;margin:0 auto;padding:30px 20px}
  .admin-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:22px}
  .admin-head h1{font-size:2.1rem;margin:0;color:var(--white)}
  .admin-head p{margin:0;color:var(--light-gray);opacity:.9}
  .admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-top:18px}
  .admin-card{
    background: linear-gradient(135deg, var(--dark-blue), var(--medium-blue));
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    transition: transform .25s ease, box-shadow .25s ease;
    border: 1px solid rgba(255,255,255,0.08);
    cursor:pointer;
    text-decoration:none;
    display:block;
  }
  .admin-card:hover{transform:translateY(-6px);box-shadow:0 14px 34px rgba(0,0,0,0.32)}
  .admin-card .k{font-size:.95rem;color:var(--light-gray);opacity:.9;margin:0 0 10px}
  .admin-card .v{font-size:2.2rem;color:var(--white);font-weight:700;margin:0}
  .admin-card .hint{margin:12px 0 0;color:rgba(255,255,255,0.75);font-size:.95rem}
  .admin-actions{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin-top:26px}
  .action-tile{
    background: var(--medium-blue);
    border-radius: 16px;
    padding: 22px;
    border: 1px solid rgba(255,255,255,0.08);
  }
  .action-tile h3{margin:0 0 10px;color:var(--white)}
  .action-tile p{margin:0 0 14px;color:var(--light-gray)}
  .tile-links{display:flex;gap:10px;flex-wrap:wrap}
  .tile-links a{
    display:inline-block;
    background: var(--cyan);
    color:#05232c;
    padding:10px 14px;
    border-radius: 12px;
    font-weight:600;
    text-decoration:none;
  }
  .tile-links a:hover{opacity:.92}
  .notice{
    background: rgba(0, 255, 255, 0.10);
    border: 1px solid rgba(0, 255, 255, 0.22);
    color: var(--white);
    padding: 14px 16px;
    border-radius: 14px;
    margin-top: 14px;
  }
</style>

<div class="admin-wrap">
  <div class="admin-head">
    <div>
      <h1>Admin Dashboard</h1>
      <p>Manage users, assessments, and platform settings.</p>
      <?php if (!empty($flash)): ?>
        <div class="notice"><?php echo htmlspecialchars($flash); ?></div>
      <?php endif; ?>
    </div>
    <div style="text-align:right">
      <div style="color:var(--light-gray);opacity:.9;font-size:.95rem">Signed in as</div>
      <div style="color:var(--white);font-weight:700"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
    </div>
  </div>

  <div class="admin-grid">
    <a class="admin-card" href="admin_users.php">
      <p class="k">Total Users</p>
      <p class="v"><?php echo $users_total; ?></p>
      <p class="hint">View, verify, reset, and remove accounts</p>
    </a>

    <a class="admin-card" href="admin_assessments.php">
      <p class="k">Total Assessments</p>
      <p class="v"><?php echo $assess_total; ?></p>
      <p class="hint">Search, filter, and moderate all assessments</p>
    </a>

    <a class="admin-card" href="admin_assessments.php?status=High%20Risk">
      <p class="k">High Risk</p>
      <p class="v"><?php echo $assess_high; ?></p>
      <p class="hint">Click to view high risk devices</p>
    </a>

    <a class="admin-card" href="admin_assessments.php?status=Medium">
      <p class="k">Medium</p>
      <p class="v"><?php echo $assess_med; ?></p>
      <p class="hint">Click to view medium risk devices</p>
    </a>

    <a class="admin-card" href="admin_assessments.php?status=Safe">
      <p class="k">Safe</p>
      <p class="v"><?php echo $assess_safe; ?></p>
      <p class="hint">Click to view safe devices</p>
    </a>

    <a class="admin-card" href="dashboard.php">
      <p class="k">User Dashboard</p>
      <p class="v">Open</p>
      <p class="hint">Go to the regular dashboard view</p>
    </a>
  </div>

  <div class="admin-actions">
    <div class="action-tile">
      <h3>Quick Actions</h3>
      <p>Common admin tools and shortcuts.</p>
      <div class="tile-links">
        <a href="admin_users.php">Manage Users</a>
        <a href="admin_assessments.php">Manage Assessments</a>
        <a href="profile.php">My Profile</a>
      </div>
    </div>

    <div class="action-tile">
      <h3>Risk Views</h3>
      <p>Jump directly to risk-filtered lists.</p>
      <div class="tile-links">
        <a href="admin_assessments.php?status=High%20Risk">High Risk</a>
        <a href="admin_assessments.php?status=Medium">Medium</a>
        <a href="admin_assessments.php?status=Safe">Safe</a>
      </div>
    </div>

    <div class="action-tile">
      <h3>Security</h3>
      <p>Admin access is controlled using environment variables (no database change required).</p>
      <div class="tile-links">
        <a href="about.php">Docs</a>
        <a href="logout.php">Logout</a>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
