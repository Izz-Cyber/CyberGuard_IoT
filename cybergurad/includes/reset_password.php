<?php
require 'init.php';
require 'db_connect.php';
$token = $_GET['token'] ?? '';
if (empty($token)) { header('Location: login.php'); exit(); }
$stmt = $conn->prepare('SELECT id, reset_expires FROM users WHERE reset_token = ? LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (strtotime($row['reset_expires']) < time()) { $stmt->close(); header('Location: login.php?error=reset_expired'); exit(); }
    $uid = $row['id'];
} else { $stmt->close(); header('Location: login.php?error=invalid'); exit(); }
$stmt->close();

$pageTitle = "CyberGuard IoT - Reset Password";
include 'header.php';
?>
<section class="container" style="padding-top:50px;">
  <h1 class="section-title">Reset Password</h1>
  <div style="max-width:420px;margin:0 auto;">
    <form action="process_reset.php" method="POST" style="background:var(--medium-blue);padding:20px;border-radius:12px;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
      <div class="input-group"><label>New password</label><input type="password" name="password" required></div>
      <div class="input-group"><label>Confirm password</label><input type="password" name="password_confirm" required></div>
      <button class="btn" type="submit">Set new password</button>
    </form>
  </div>
</section>
<?php include 'footer.php'; ?>