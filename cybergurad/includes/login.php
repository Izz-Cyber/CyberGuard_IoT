<?php
$pageTitle = "CyberGuard IoT - Login";
include 'header.php';
?>

<section class="container" style="padding-top:50px;">
  <h1 class="section-title">Login</h1>
  <p class="text-muted" style="margin-bottom:20px;">Access your saved assessments.</p>

  <?php if (!empty($_GET['registered'])): ?>
    <div style="max-width:520px;margin:10px auto;color:#06D6A0;background:rgba(6,214,160,0.06);padding:12px;border-radius:8px;border:1px solid rgba(6,214,160,0.08);">Registration successful. You may now login.</div>
  <?php endif; ?>

  <?php if (!empty($_GET['error'])): ?>
    <div style="max-width:520px;margin:10px auto;color:#e63946;background:rgba(230,57,70,0.06);padding:12px;border-radius:8px;border:1px solid rgba(230,57,70,0.08);">
      <?php
        $err = $_GET['error'];
        if ($err === 'csrf') echo 'Invalid session. Please refresh the page and try again.';
        else echo 'Login failed. Check your credentials.';
      ?>
    </div>
  <?php endif; ?>

    <div style="max-width:520px;margin:0 auto;">
    <form action="process_login.php" method="POST" style="background:var(--medium-blue);padding:24px;border-radius:12px;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
      <div class="input-group">
        <label>Email or Username</label>
        <input type="text" name="identifier" required>
      </div>
      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button class="btn" type="submit">Login</button>
      <p style="margin-top:12px;color:rgba(200,210,220,0.7);">No account? <a class="btn-link" href="register.php">Register</a></p>
    </form>
  </div>
</section>

<?php include 'footer.php'; ?>