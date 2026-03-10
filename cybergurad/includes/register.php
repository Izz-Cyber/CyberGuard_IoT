<?php
$pageTitle = "CyberGuard IoT - Register";
include 'header.php';
?>

<section class="container" style="padding-top:50px;">
  <h1 class="section-title">Create Account</h1>
  <p class="text-muted" style="margin-bottom:20px;">Register to save your assessments and manage your devices. Avatar is optional.</p>

  <?php if (!empty($_GET['error'])): ?>
    <div style="max-width:520px;margin:10px auto;color:#e63946;background:rgba(230,57,70,0.06);padding:12px;border-radius:8px;border:1px solid rgba(230,57,70,0.08);">
      <?php
        $err = $_GET['error'];
        $map = [
          'csrf' => 'Invalid session. Please refresh the page and try again.',
          'pw_mismatch' => 'Passwords do not match.',
          'pw_short' => 'Password must be at least 6 characters.',
          'exists' => 'A user with that email or username already exists.',
          'file_error' => 'File upload error. Try another image.',
          'file_large' => 'Image too large. Max 2MB.',
          'invalid_image' => 'Uploaded file is not a valid image.',
          'db' => 'Database error. Try again later.'
        ];
        echo htmlspecialchars($map[$err] ?? 'An error occurred.');
      ?>
    </div>
  <?php endif; ?>

    <div style="max-width:520px;margin:0 auto;">
    <form action="process_register.php" method="POST" enctype="multipart/form-data" style="background:var(--medium-blue);padding:24px;border-radius:12px;">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(
          
          
          
          
          
          $_SESSION['csrf_token']
      ); ?>">

      <div class="input-group">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>

      <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>

      <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>

      <div class="input-group">
        <label>Confirm Password</label>
        <input type="password" name="password_confirm" required>
      </div>

      <div class="input-group">
        <label>Avatar (optional)</label>
        <input type="file" id="avatarInput" name="avatar" accept="image/png, image/jpeg" class="file-input-hidden">
        <label for="avatarInput" class="btn btn-outline file-btn">Choose Image</label>
        <span id="fileName" class="file-name"></span>
        <div id="avatarPreview" style="margin-top:10px;display:none;">
          <img id="avatarImg" src="#" alt="preview" style="max-width:120px;border-radius:8px;border:1px solid rgba(255,255,255,0.04);">
        </div>
      </div>

      <button class="btn" type="submit">Register</button>
      <p style="margin-top:12px;color:rgba(200,210,220,0.7);">Already have an account? <a class="btn-link" href="login.php">Login</a></p>
    </form>
  </div>
</section>

<?php include 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var input = document.getElementById('avatarInput');
  var preview = document.getElementById('avatarPreview');
  var img = document.getElementById('avatarImg');
  var fileName = document.getElementById('fileName');
  if (!input) return;
  input.addEventListener('change', function(e){
    var file = this.files && this.files[0];
    if (!file) { preview.style.display = 'none'; return; }
    if (!file.type.match('image.*')) { preview.style.display = 'none'; return; }
    // show filename
    if (fileName) fileName.textContent = file.name;
    var reader = new FileReader();
    reader.onload = function(evt){ img.src = evt.target.result; preview.style.display = 'block'; }
    reader.readAsDataURL(file);
  });
});
</script>