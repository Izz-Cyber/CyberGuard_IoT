<?php
// header and session start
$pageTitle = "CyberGuard IoT - Profile";
include 'header.php';
require 'db_connect.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$uid = (int)$_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare('SELECT username, email, avatar_path FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$avatar = $user['avatar_path'] ? $user['avatar_path'] : 'style/avatar-placeholder.png';
// Build a CSS background style using the user's avatar as a subtle page background
$bgStyle = '';
if (!empty($user['avatar_path'])) {
  $bgUrl = htmlspecialchars($user['avatar_path']);
  $bgStyle = "background-image: linear-gradient(rgba(13,27,42,0.85), rgba(13,27,42,0.85)), url('" . $bgUrl . "'); background-size: cover; background-position: center; background-attachment: fixed;";
}

// Fetch assessments by user (assumes assessments.user_id column exists)
$stmt = $conn->prepare('SELECT a.id, d.device_name, a.status, a.assessment_date FROM assessments a JOIN devices d ON a.device_id = d.id WHERE a.user_id = ? ORDER BY a.assessment_date DESC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$results = $stmt->get_result();

?>

<section class="container profile-bg" style="padding-top:50px; <?php echo $bgStyle; ?>">
  <div class="profile-grid" style="max-width:900px;margin:0 auto; display:flex; gap:20px; align-items:flex-start;">
    <aside class="profile-card" style="width:260px;">
      <div class="profile-header" style="display:flex; align-items:center; gap:12px;">
        <div class="avatar-circle" style="background-image:url('<?php echo htmlspecialchars($avatar); ?>');"></div>
        <div>
          <h3 style="margin:0"><?php echo htmlspecialchars($user['username']); ?></h3>
          <p class="text-muted" style="margin:4px 0 0; font-size:0.95rem"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
      </div>
      <form id="avatarForm" action="process_profile_avatar.php" method="POST" enctype="multipart/form-data" style="margin-top:12px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ); ?>">
        <label style="display:block;margin-bottom:8px;color:var(--light);">Change avatar</label>
        <input id="profileAvatarInput" type="file" name="avatar" accept="image/png,image/jpeg" class="file-input-hidden">
        <label for="profileAvatarInput" class="btn btn-outline file-btn">Choose Image</label>
        <span id="profileFileName" class="file-name"></span>
        <div id="profileAvatarPreview" style="margin-bottom:8px; display:none;"></div>
        <button class="btn" type="submit" style="margin-top:8px;">Upload</button>
      </form>
      <a href="logout.php" class="btn btn-outline" style="margin-top:12px; display:inline-block;">Logout</a>
    </aside>

    <div style="flex:1;">
      <h2 class="section-title">Your Assessments</h2>
      <?php if ($results->num_rows === 0): ?>
        <p class="text-muted">You have no saved assessments yet. Run a new assessment to save it here.</p>
      <?php else: ?>
        <table class="table" style="width:100%;">
          <thead>
            <tr><th>Device</th><th>Status</th><th>Date</th><th></th></tr>
          </thead>
          <tbody>
            <?php while ($row = $results->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['device_name']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['assessment_date']); ?></td>
                <td><a href="result.php?id=<?php echo (int)$row['id']; ?>" class="btn-link">View</a></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</section>


<!-- Footer -->
<?php include 'footer.php'; ?>


<!-- javaScript for avatar preview -->
<script>
document.addEventListener('DOMContentLoaded', function(){
  var input = document.getElementById('profileAvatarInput');
  var preview = document.getElementById('profileAvatarPreview');
  var fileName = document.getElementById('profileFileName');
  if (!input) return;
  input.addEventListener('change', function(){
    var file = this.files && this.files[0];
    if (!file) { preview.style.display = 'none'; preview.innerHTML = ''; return; }
    if (!file.type.match('image.*')) { preview.style.display = 'none'; preview.innerHTML = ''; return; }
    if (fileName) fileName.textContent = file.name;
    var reader = new FileReader();
    reader.onload = function(e){
      preview.innerHTML = '<img src="'+e.target.result+'" alt="preview" class="profile-preview-img">';
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });
});
</script>