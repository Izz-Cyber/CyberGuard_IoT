<?php
$pageTitle = "CyberGuard IoT - Admin Assessments";
include 'header.php';
require_once 'auth.php';
auth_require_admin();
require 'db_connect.php';

$flash = auth_flash_get('admin_notice');

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(d.device_name LIKE CONCAT('%', ?, '%') OR d.manufacturer LIKE CONCAT('%', ?, '%') OR d.model LIKE CONCAT('%', ?, '%'))";
    $params[] = $search; $params[] = $search; $params[] = $search;
    $types .= 'sss';
}
if ($status !== '') {
    $where[] = "a.status = ?";
    $params[] = $status;
    $types .= 's';
}
$whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT a.id, a.status, a.assessment_date, d.device_name, d.manufacturer, d.model
        FROM assessments a
        JOIN devices d ON a.device_id = d.id
        $whereSql
        ORDER BY a.assessment_date DESC
        LIMIT 800";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Query preparation failed: ' . htmlspecialchars($conn->error));
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) { $rows[] = $r; }
$stmt->close();

$csrf = $_SESSION['csrf_token'] ?? '';
?>
<style>
  .admin-wrap{max-width:1200px;margin:0 auto;padding:30px 20px}
  .topbar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-bottom:18px}
  .topbar h1{margin:0;color:var(--white);font-size:2rem}
  .filters{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
  .filters input, .filters select{
    padding:12px 14px;border-radius:12px;border:1px solid rgba(255,255,255,0.16);
    background: var(--dark-blue);color:var(--white);min-width:240px;
  }
  .filters select{min-width:200px}
  .filters button{
    padding:12px 14px;border-radius:12px;border:0;background:var(--cyan);color:#05232c;font-weight:700;cursor:pointer;
  }
  .filters a{color:var(--light-gray);text-decoration:none}
  .notice{
    background: rgba(0, 255, 255, 0.10);
    border: 1px solid rgba(0, 255, 255, 0.22);
    color: var(--white);
    padding: 14px 16px;
    border-radius: 14px;
    margin-bottom: 16px;
  }
  .table-wrap{background: var(--medium-blue);border-radius:16px;padding:18px;border:1px solid rgba(255,255,255,0.08);overflow:auto}
  table{width:100%;border-collapse:collapse;min-width:980px}
  th,td{padding:12px 12px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.08);color:var(--white)}
  th{color:var(--light-gray);font-weight:600;opacity:.9}
  .badge{
    display:inline-block;padding:6px 10px;border-radius:999px;font-size:.85rem;font-weight:800;
    background: rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.14);
  }
  .badge.high{background: rgba(255, 67, 67, 0.14);border-color: rgba(255,67,67,0.28);}
  .badge.med{background: rgba(255, 191, 71, 0.14);border-color: rgba(255,191,71,0.30);}
  .badge.safe{background: rgba(0,255,255,0.12);border-color: rgba(0,255,255,0.25);}
  .actions{display:flex;gap:8px;flex-wrap:wrap}
  .btn{
    border:0;border-radius:12px;padding:9px 11px;font-weight:800;cursor:pointer;text-decoration:none;display:inline-block;
    background: rgba(255,255,255,0.10);color:var(--white);border:1px solid rgba(255,255,255,0.14);
  }
  .btn.primary{background: var(--cyan);color:#05232c;border-color: rgba(0,0,0,0.0);}
  .btn.danger{background: rgba(255, 67, 67, 0.14);border-color: rgba(255,67,67,0.28);}
  .btn:hover{opacity:.92}
  .small{font-size:.9rem;color:rgba(255,255,255,0.8)}
  .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace}
</style>

<div class="admin-wrap">
  <div class="topbar">
    <div>
      <h1>Assessments</h1>
      <div class="small">Moderate and review all device assessments</div>
    </div>
    <form class="filters" method="get" action="admin_assessments.php">
      <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search devices...">
      <select name="status">
        <option value="">All statuses</option>
        <option value="High Risk" <?php echo ($status==='High Risk'?'selected':''); ?>>High Risk</option>
        <option value="Medium" <?php echo ($status==='Medium'?'selected':''); ?>>Medium</option>
        <option value="Safe" <?php echo ($status==='Safe'?'selected':''); ?>>Safe</option>
      </select>
      <button type="submit">Filter</button>
      <a href="admin_dashboard.php">Back to Admin</a>
    </form>
  </div>

  <?php if (!empty($flash)): ?>
    <div class="notice"><?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Device</th>
          <th>Manufacturer</th>
          <th>Model</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="small">No assessments found.</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $st = $r['status'] ?? '';
              $badgeClass = 'badge';
              if ($st === 'High Risk') $badgeClass .= ' high';
              else if ($st === 'Medium') $badgeClass .= ' med';
              else if ($st === 'Safe') $badgeClass .= ' safe';
            ?>
            <tr>
              <td class="mono"><?php echo (int)$r['id']; ?></td>
              <td style="font-weight:800"><?php echo htmlspecialchars($r['device_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['manufacturer'] ?? ''); ?></td>
              <td class="mono"><?php echo htmlspecialchars($r['model'] ?? ''); ?></td>
              <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($st); ?></span></td>
              <td class="mono small"><?php echo htmlspecialchars($r['assessment_date'] ?? ''); ?></td>
              <td>
                <div class="actions">
                  <a class="btn primary" href="result.php?id=<?php echo (int)$r['id']; ?>">View</a>
                  <form method="post" action="process_admin_assessment.php" style="margin:0;display:inline" onsubmit="return confirm('Delete this assessment?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="assessment_id" value="<?php echo (int)$r['id']; ?>">
                    <input type="hidden" name="action" value="delete_assessment">
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
