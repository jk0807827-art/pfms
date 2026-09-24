<?php
$pageTitle = "Settings - Personal Finance Management System";

require 'auth.php';
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$userId = $_SESSION['user_id'];

$user = $data->get_user_by_id($userId);

$currentPage = basename($_SERVER['PHP_SELF']);
include 'header.php';
?>


  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Settings</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Account</div>
        <h1 class="page-title">Settings</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>
    <main class="content" id="pageContent">

      <div class="grid-2">
        <div class="ledger-panel">
          <div class="panel-head"><div><h3>Profile</h3></div></div>
          <form id="profileForm">
            <div class="field-group" id="g_name">
              <label class="field-label" for="name">Name</label>
              <input class="field-control" id="name" name="name" value="<?= htmlspecialchars($user['full_name']) ?>">
              <div class="field-error">Enter your name.</div>
            </div>
            <div class="field-group" id="g_email">
              <label class="field-label" for="email">Email</label>
              <input class="field-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" type="email">
              <div class="field-error">Enter a valid email address.</div>
            </div>
            <div class="field-group" id="g_phone">
              <label class="field-label" for="phone">Phone</label>
              <input class="field-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
              <div class="field-error">Enter your phone number.</div>
            </div>
            <div class="field-group" id="g_profile_error" style="display:none;">
              <div class="field-error" style="display:block;" id="profileError"></div>
            </div>
            <button type="submit" class="btn-ledger brass" id="profileBtn">Update profile</button>
          </form>
        </div>

        <div class="ledger-panel">
          <div class="panel-head"><div><h3>Change password</h3></div></div>
          <form id="passwordForm">
            <div class="field-group" id="g_current">
              <label class="field-label" for="current">Current password</label>
              <input class="field-control" id="current" name="current_password" type="password">
              <div class="field-error">Current password is incorrect.</div>
            </div>
            <div class="field-group" id="g_new">
              <label class="field-label" for="newPass">New password</label>
              <input class="field-control" id="newPass" name="new_password" type="password">
              <div class="field-hint">Use 8+ characters with a number and a letter.</div>
              <div class="field-error">Password does not meet the requirements.</div>
            </div>
            <div class="field-group" id="g_confirm">
              <label class="field-label" for="confirmPass">Confirm new password</label>
              <input class="field-control" id="confirmPass" type="password">
              <div class="field-error">Passwords do not match.</div>
            </div>
            <div class="field-group" id="g_password_error" style="display:none;">
              <div class="field-error" style="display:block;" id="passwordError"></div>
            </div>
            <button type="submit" class="btn-ledger brass" id="passwordBtn">Change password</button>
          </form>
        </div>
      </div>

      <div class="ledger-panel" style="margin-top:1.25rem;">
        <div class="panel-head"><div><h3>Account</h3></div></div>
        <p>Signed in as <strong><?= htmlspecialchars($user['email']) ?></strong>. Logging out will end your session on this device.</p>
        <a href="logout.php" class="btn-ledger ghost">Log out</a>
      </div>

    </main>
  </div>
</div>

<div id="toast"></div>
<div class="modal-veil" id="confirmVeil">
  <div class="modal-box">
    <div class="modal-title" id="confirmTitle">Are you sure?</div>
    <div class="modal-body" id="confirmBody"></div>
    <div class="modal-actions">
      <button class="btn-ledger ghost sm" id="confirmCancel">Cancel</button>
      <button class="btn-ledger danger sm" id="confirmOk">Delete</button>
    </div>
  </div>
</div>

<script src="assets/js/ui.js"></script>
<script>
document.getElementById('profileForm').addEventListener('submit', function(e){
  e.preventDefault();
  document.getElementById('g_profile_error').style.display = 'none';

  let valid = true;
  const name = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();
  const phone = document.getElementById('phone').value.trim();

  function setError(id, hasError){ document.getElementById(id).classList.toggle('has-error', hasError); if(hasError) valid = false; }
  setError('g_name', name.length === 0);
  setError('g_email', !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email));
  setError('g_phone', phone.length === 0);
  if(!valid) return;

  const btn = document.getElementById('profileBtn');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  $.ajax({
    url: 'include/routes.php',
    type: 'POST',
    data: { type: 19, name: name, email: email, phone: phone },
    dataType: 'json',
    success: function(response){
      if(response.statusCode == 200){
        toast('Profile updated.');
      } else {
        document.getElementById('g_profile_error').style.display = 'block';
        document.getElementById('profileError').textContent = response.message || 'Unable to update profile.';
      }
    },
    error: function(){
      document.getElementById('g_profile_error').style.display = 'block';
      document.getElementById('profileError').textContent = 'Something went wrong. Please try again.';
    },
    complete: function(){
      btn.disabled = false;
      btn.textContent = 'Update profile';
    }
  });
});

document.getElementById('passwordForm').addEventListener('submit', function(e){
  e.preventDefault();
  document.getElementById('g_password_error').style.display = 'none';

  let valid = true;
  const current = document.getElementById('current').value;
  const next = document.getElementById('newPass').value;
  const confirm = document.getElementById('confirmPass').value;

  function setError(id, hasError){ document.getElementById(id).classList.toggle('has-error', hasError); if(hasError) valid = false; }
  setError('g_current', current.length === 0);
  setError('g_new', !(next.length >= 8 && /[A-Za-z]/.test(next) && /[0-9]/.test(next)));
  setError('g_confirm', confirm !== next || confirm.length === 0);
  if(!valid) return;

  const btn = document.getElementById('passwordBtn');
  btn.disabled = true;
  btn.textContent = 'Saving...';

  $.ajax({
    url: 'include/routes.php',
    type: 'POST',
    data: { type: 20, current_password: current, new_password: next },
    dataType: 'json',
    success: function(response){
      if(response.statusCode == 200){
        document.getElementById('passwordForm').reset();
        toast('Password changed.');
      } else if(response.statusCode == 202){
        document.getElementById('g_current').classList.add('has-error');
        document.getElementById('g_password_error').style.display = 'block';
        document.getElementById('passwordError').textContent = response.message || 'Current password is incorrect.';
      } else {
        document.getElementById('g_password_error').style.display = 'block';
        document.getElementById('passwordError').textContent = response.message || 'Unable to change password.';
      }
    },
    error: function(){
      document.getElementById('g_password_error').style.display = 'block';
      document.getElementById('passwordError').textContent = 'Something went wrong. Please try again.';
    },
    complete: function(){
      btn.disabled = false;
      btn.textContent = 'Change password';
    }
  });
});
</script>
</body>
</html>
