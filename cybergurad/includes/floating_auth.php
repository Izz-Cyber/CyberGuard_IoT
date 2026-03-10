<?php
// Floating auth button: draggable circle with modal for login/register or profile links
// This file is safe to include in footer.php so it appears on every page.
?>
<style>
  #floatingAuthBtn {
    position: fixed;
    right: 20px;
    bottom: 80px;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--cyan);
    color: #042029;
    display:flex;align-items:center;justify-content:center;
    font-size:22px;cursor:grab;z-index:99999;box-shadow:0 8px 20px rgba(0,0,0,0.4);
  }
  #floatingAuthBtn:active{cursor:grabbing}
  #floatingAuthModal{position:fixed;right:20px;bottom:150px;z-index:99998;display:none}
  #floatingAuthModal .card{background:var(--medium-blue);color:var(--white);padding:14px;border-radius:10px;min-width:220px}
  #floatingAuthModal a{color:var(--cyan);text-decoration:none;font-weight:600}
  #floatingAuthReset{display:block;margin-top:8px;font-size:12px;color:rgba(200,210,220,0.6);cursor:pointer}
</style>

<div id="floatingAuthBtn" title="Account">
  <span id="floatingAuthIcon">🔐</span>
</div>

<div id="floatingAuthModal">
  <div class="card">
    <?php if (!empty($_SESSION['user_id'])): ?>
      <div style="margin-bottom:8px">Signed in as <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
      </div>
      <div id="floatingAuthReset">Double-click button to reset position</div>
    <?php else: ?>
      <div style="margin-bottom:8px">Not signed in</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      </div>
      <div id="floatingAuthReset">Double-click button to reset position</div>
    <?php endif; ?>
  </div>
</div>

<script>
;(function(){
  const btn = document.getElementById('floatingAuthBtn');
  const modal = document.getElementById('floatingAuthModal');
  const storageKey = 'floatingAuthPos';

  // restore position
  try{
    const pos = JSON.parse(localStorage.getItem(storageKey) || 'null');
    if(pos && typeof pos.x === 'number' && typeof pos.y === 'number'){
      btn.style.right = 'auto'; btn.style.bottom = 'auto';
      btn.style.left = pos.x + 'px'; btn.style.top = pos.y + 'px';
      modal.style.right = 'auto'; modal.style.bottom = 'auto';
      modal.style.left = pos.x + 'px'; modal.style.top = (pos.y - 90) + 'px';
    }
  }catch(e){}

  // toggle modal on click
  btn.addEventListener('click', function(e){
    // ignore if dragging
    if(btn.dataset.dragging === '1') return;
    modal.style.display = modal.style.display === 'block' ? 'none' : 'block';
  });

  // reset position on double-click
  btn.addEventListener('dblclick', function(){
    localStorage.removeItem(storageKey);
    btn.style.left = '';
    btn.style.top = '';
    btn.style.right = '20px';
    btn.style.bottom = '80px';
    modal.style.left = '';
    modal.style.top = '';
    modal.style.right = '20px';
    modal.style.bottom = '150px';
  });

  // drag support
  let isDown = false, startX=0, startY=0, origX=0, origY=0;
  function pointerDown(e){
    isDown = true; btn.dataset.dragging = '1';
    startX = (e.touches? e.touches[0].clientX : e.clientX);
    startY = (e.touches? e.touches[0].clientY : e.clientY);
    const rect = btn.getBoundingClientRect();
    origX = rect.left; origY = rect.top;
    modal.style.display = 'none';
    e.preventDefault();
  }
  function pointerMove(e){ if(!isDown) return; const cx = (e.touches? e.touches[0].clientX : e.clientX); const cy = (e.touches? e.touches[0].clientY : e.clientY); const nx = origX + (cx - startX); const ny = origY + (cy - startY); btn.style.left = nx + 'px'; btn.style.top = ny + 'px'; modal.style.left = nx + 'px'; modal.style.top = (ny - 90) + 'px'; }
  function pointerUp(e){ if(!isDown) return; isDown=false; setTimeout(()=>btn.dataset.dragging='0',100); // allow click suppression
    // save position
    const rect = btn.getBoundingClientRect();
    localStorage.setItem(storageKey, JSON.stringify({x: Math.round(rect.left), y: Math.round(rect.top)}));
  }
  btn.addEventListener('mousedown', pointerDown);
  document.addEventListener('mousemove', pointerMove);
  document.addEventListener('mouseup', pointerUp);
  // touch
  btn.addEventListener('touchstart', pointerDown);
  document.addEventListener('touchmove', pointerMove, {passive:false});
  document.addEventListener('touchend', pointerUp);
})();
</script>
