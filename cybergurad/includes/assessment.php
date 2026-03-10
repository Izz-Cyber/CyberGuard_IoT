<?php
    $pageTitle = "CyberGuard IoT - Assessment";
    include 'header.php';
?>

<section class="container" style="padding-top:50px;">

  <h1 class="section-title">🔍 Device Security Assessment</h1>
  <p class="text-muted" style="margin-bottom:30px;">
    Enter basic device information to receive an AI-powered security analysis.
  </p>

  <!-- ====== Style for Assessment Form ====== -->
  <style>
    .assessment-center-wrapper{max-width:760px;margin:0 auto;display:flex;flex-direction:column;justify-content:center;align-items:center;min-height:calc(100vh - 200px);}
    .assessment-center-wrapper .form{width:100%;}
    .center-select{color:#fff !important;background:var(--medium-blue) !important;border:1px solid rgba(255,255,255,0.06);padding:10px;border-radius:6px}
    .center-select option{color:#fff !important;background:var(--medium-blue) !important}
  </style>

  <div class="assessment-center-wrapper">
    <form class="form" action="process_assessment.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

    <div class="input-group">
      <label>Device Name</label>
      <input type="text" name="device_name" placeholder="e.g. TP-Link AX72" required>
    </div>

    <div class="input-group">
      <label>Manufacturer</label>
      <input type="text" name="manufacturer" placeholder="e.g. TP-Link" required>
    </div>

    <div class="input-group">
      <label>Model</label>
      <input type="text" name="model" placeholder="e.g. AX72" required>
    </div>

    <div class="input-group">
      <label>Firmware Version (optional)</label>
      <input type="text" name="firmware_version" placeholder="e.g. v1.3.2">
    </div>

    <div class="input-group">
      <label>Device Category (optional)</label>
      <select name="device_category" class="center-select">
        <option value="">Auto detect by AI</option>
        <option value="Router">Router</option>
        <option value="Camera">Camera</option>
        <option value="IoT Sensor">IoT Sensor</option>
        <option value="Smart Device">Smart Device</option>
      </select>
    </div>

    <button class="btn" type="submit">Start Security Assessment</button>

    </form>
  </div>

</section>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(13, 27, 42, 0.9); z-index:9999; justify-content:center; align-items:center; flex-direction:column; text-align:center;">
    <div class="ai-scanner" style="width:80px; height:80px; border:4px solid var(--cyan); border-top:4px solid transparent; border-radius:50%; animation:spin 1s linear infinite; margin-bottom:20px;"></div>
    <h2 style="color:var(--cyan); font-weight:600;">CyberGuard AI is Analyzing...</h2>
    <p id="loadingText" style="color:var(--white); margin-top:10px; font-size:1.1rem;">Scanning device vulnerabilities...</p>
</div>

<style>
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>


        <script src="assessment.js"></script>
<?php
    include 'footer.php';
?>