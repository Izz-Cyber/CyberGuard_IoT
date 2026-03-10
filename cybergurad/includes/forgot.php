<?php
$pageTitle = "CyberGuard IoT - Password Reset";
include 'header.php';
?>
<section class="container" style="padding-top:50px;">
  <h1 class="section-title">Forgot Password</h1>
  <p class="text-muted">Enter your email to receive a password reset link.</p>
  <div style="max-width:420px;margin:0 auto;">
    <form action="process_reset_request.php" method="POST" style="background:var(--medium-blue);padding:20px;border-radius:12px;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <button class="btn" type="submit">Send Reset Link</button>
    </form>
  </div>
</section>
<?php include 'footer.php'; ?>