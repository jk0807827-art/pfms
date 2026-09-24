<?php
$pageTitle = "Site Content - PFMS Admin";

require 'auth.php';
require_once '../config/db.php';
require_once '../include/core.php';

$data = new core($conn);
$settings = $data->get_settings();
$team = $data->get_team_members();

function s($settings, $key, $default = ''){
    return isset($settings[$key]) ? $settings[$key] : $default;
}

include 'header.php';
?>

  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Site Content</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Admin</div>
        <h1 class="page-title">Site Content</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>

    <main class="content" id="pageContent">

      <div class="cat-tabs">
        <button type="button" class="cat-tab-btn active" data-target="panel-home">Home page</button>
        <button type="button" class="cat-tab-btn" data-target="panel-about">About page</button>
        <button type="button" class="cat-tab-btn" data-target="panel-team">Team members</button>
      </div>

      <!-- ===================== HOME PAGE ===================== -->
      <section class="cat-panel active" id="panel-home">

        <div class="grid-2">

          <div class="ledger-panel">
            <div class="panel-head"><div><h3>Logo &amp; header image</h3></div></div>

            <div class="field-group">
              <label class="field-label">Header logo</label>
              <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.6em;">
                <div style="width:64px;height:64px;border:1px solid var(--line);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--paper-deep);">
                  <?php if(s($settings,'logo_image')): ?>
                    <img id="logoPreview" src="../<?= htmlspecialchars(s($settings,'logo_image')) ?>" style="max-width:100%;max-height:100%;">
                  <?php else: ?>
                    <span id="logoPreview" style="font-family:var(--font-display);color:var(--brass);">£</span>
                  <?php endif; ?>
                </div>
                <div class="field-hint">Shown in the top-left of the public site and app sidebar. Leave empty to use the default "£" mark.</div>
              </div>
              <input type="file" class="field-control" id="logoFile" accept="image/png,image/jpeg,image/gif,image/webp">
              <button type="button" class="btn-ledger ghost sm" id="uploadLogoBtn" style="margin-top:.6em;">Upload logo</button>
            </div>

            <div class="field-group" style="margin-top:1.5rem;">
              <label class="field-label">Homepage header image</label>
              <div style="margin-bottom:.6em;">
                <img id="heroPreview" src="../<?= htmlspecialchars(s($settings,'hero_image','assets/img/header-b.PNG')) ?>" style="width:100%;max-height:140px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--line);">
              </div>
              <input type="file" class="field-control" id="heroFile" accept="image/png,image/jpeg,image/gif,image/webp">
              <button type="button" class="btn-ledger ghost sm" id="uploadHeroBtn" style="margin-top:.6em;">Upload header image</button>
              <div class="field-hint">The large background photo behind the homepage headline.</div>
            </div>
          </div>

          <div class="ledger-panel">
            <div class="panel-head"><div><h3>Homepage text</h3></div></div>
            <form id="homeContentForm">
              <div class="field-group">
                <label class="field-label" for="site_name">Site name</label>
                <input class="field-control" id="site_name" name="site_name" value="<?= htmlspecialchars(s($settings,'site_name','PFMS')) ?>">
              </div>
              <div class="field-group">
                <label class="field-label" for="hero_title">Hero headline</label>
                <input class="field-control" id="hero_title" name="hero_title" value="<?= htmlspecialchars(s($settings,'hero_title')) ?>">
              </div>
              <div class="field-group">
                <label class="field-label" for="hero_subtitle">Hero subtext</label>
                <textarea class="field-control" id="hero_subtitle" name="hero_subtitle" rows="4"><?= htmlspecialchars(s($settings,'hero_subtitle')) ?></textarea>
              </div>
              <div class="field-group">
                <label class="field-label" for="footer_text">Footer text</label>
                <input class="field-control" id="footer_text" name="footer_text" value="<?= htmlspecialchars(s($settings,'footer_text')) ?>">
                <div class="field-hint">Shown on the homepage and About page footer.</div>
              </div>
              <div class="field-group" id="g_home_error" style="display:none;">
                <div class="field-error" style="display:block;" id="homeError"></div>
              </div>
              <button type="submit" class="btn-ledger brass" id="homeSaveBtn">Save homepage text</button>
            </form>
          </div>

        </div>
      </section>

      <!-- ===================== ABOUT PAGE ===================== -->
      <section class="cat-panel" id="panel-about">

        <div class="ledger-panel">
          <div class="panel-head"><div><h3>About page text</h3></div></div>
          <form id="aboutContentForm">
            <div class="field-group">
              <label class="field-label" for="about_hero_title">Heading</label>
              <input class="field-control" id="about_hero_title" name="about_hero_title" value="<?= htmlspecialchars(s($settings,'about_hero_title')) ?>">
            </div>
            <div class="field-group">
              <label class="field-label" for="about_hero_text1">Intro paragraph 1</label>
              <textarea class="field-control" id="about_hero_text1" name="about_hero_text1" rows="4"><?= htmlspecialchars(s($settings,'about_hero_text1')) ?></textarea>
            </div>
            <div class="field-group">
              <label class="field-label" for="about_hero_text2">Intro paragraph 2</label>
              <textarea class="field-control" id="about_hero_text2" name="about_hero_text2" rows="4"><?= htmlspecialchars(s($settings,'about_hero_text2')) ?></textarea>
            </div>
            <div class="field-group">
              <label class="field-label" for="about_team_title">Team section heading</label>
              <input class="field-control" id="about_team_title" name="about_team_title" value="<?= htmlspecialchars(s($settings,'about_team_title')) ?>">
            </div>
            <div class="field-group">
              <label class="field-label" for="about_team_intro">Team section intro</label>
              <textarea class="field-control" id="about_team_intro" name="about_team_intro" rows="3"><?= htmlspecialchars(s($settings,'about_team_intro')) ?></textarea>
            </div>
            <div class="field-group" id="g_about_error" style="display:none;">
              <div class="field-error" style="display:block;" id="aboutError"></div>
            </div>
            <button type="submit" class="btn-ledger brass" id="aboutSaveBtn">Save about text</button>
          </form>
        </div>

      </section>

      <!-- ===================== TEAM MEMBERS ===================== -->
      <section class="cat-panel" id="panel-team">

        <div class="grid-2">

          <div class="ledger-panel">
            <div class="panel-head"><div><h3>Add team member</h3></div></div>
            <form id="addMemberForm">
              <div class="field-group">
                <label class="field-label" for="m_name">Name</label>
                <input class="field-control" id="m_name" name="name" required>
              </div>
              <div class="field-group">
                <label class="field-label" for="m_student_id">Student ID</label>
                <input class="field-control" id="m_student_id" name="student_id">
              </div>
              <div class="field-group">
                <label class="field-label" for="m_role">Role</label>
                <input class="field-control" id="m_role" name="role" value="Team Member">
              </div>
              <div class="field-group">
                <label class="field-label" for="m_photo">Photo</label>
                <input type="file" class="field-control" id="m_photo" name="photo" accept="image/png,image/jpeg,image/gif,image/webp">
              </div>
              <div class="field-group" id="g_member_error" style="display:none;">
                <div class="field-error" style="display:block;" id="memberError"></div>
              </div>
              <button type="submit" class="btn-ledger brass" id="addMemberBtn">Add member</button>
            </form>
          </div>

          <div class="ledger-panel">
            <div class="panel-head"><div><h3>Current team</h3><div class="panel-sub"><?= count($team) ?> member(s)</div></div></div>
            <div id="teamList">
              <?php foreach($team as $m): ?>
              <div class="team-row" data-id="<?= (int)$m['member_id'] ?>" style="display:flex;align-items:center;gap:.9em;padding:.7em 0;border-bottom:1px solid var(--line-soft);">
                <img src="../<?= htmlspecialchars($m['photo'] ?: 'assets/img/header-b.PNG') ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                <div style="flex:1;min-width:0;">
                  <div style="font-weight:600;"><?= htmlspecialchars($m['name']) ?></div>
                  <div style="font-size:.78rem;color:var(--ink-faint);"><?= htmlspecialchars($m['role']) ?><?= $m['student_id'] ? ' · ' . htmlspecialchars($m['student_id']) : '' ?></div>
                </div>
                <button type="button" class="btn-ledger ghost sm del-member" data-id="<?= (int)$m['member_id'] ?>">Remove</button>
              </div>
              <?php endforeach; ?>
              <?php if(empty($team)): ?>
              <div class="table-empty">No team members yet — add one on the left.</div>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </section>

    </main>
  </div>
</div>

<div id="toast"></div>

<script src="../assets/js/ui.js"></script>
<script>
document.querySelectorAll('.cat-tab-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('.cat-tab-btn').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.cat-panel').forEach(p=>p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.target).classList.add('active');
  });
});

/* ---------- Save homepage text ---------- */
$('#homeContentForm').on('submit', function(e){
  e.preventDefault();
  const $btn = $('#homeSaveBtn');
  $btn.prop('disabled', true).text('Saving...');
  $('#g_home_error').hide();

  $.ajax({
    url: 'routes.php',
    type: 'POST',
    data: $(this).serialize() + '&type=1',
    dataType: 'json',
    success: function(res){
      if(res.statusCode == 200){
        toast('Homepage text updated.');
      }else{
        $('#g_home_error').show();
        $('#homeError').text(res.message || 'Unable to save.');
      }
    },
    error: function(){
      $('#g_home_error').show();
      $('#homeError').text('Something went wrong. Please try again.');
    },
    complete: function(){ $btn.prop('disabled', false).text('Save homepage text'); }
  });
});

/* ---------- Save about text ---------- */
$('#aboutContentForm').on('submit', function(e){
  e.preventDefault();
  const $btn = $('#aboutSaveBtn');
  $btn.prop('disabled', true).text('Saving...');
  $('#g_about_error').hide();

  $.ajax({
    url: 'routes.php',
    type: 'POST',
    data: $(this).serialize() + '&type=1',
    dataType: 'json',
    success: function(res){
      if(res.statusCode == 200){
        toast('About page text updated.');
      }else{
        $('#g_about_error').show();
        $('#aboutError').text(res.message || 'Unable to save.');
      }
    },
    error: function(){
      $('#g_about_error').show();
      $('#aboutError').text('Something went wrong. Please try again.');
    },
    complete: function(){ $btn.prop('disabled', false).text('Save about text'); }
  });
});

/* ---------- Upload logo ---------- */
$('#uploadLogoBtn').on('click', function(){
  const file = $('#logoFile')[0].files[0];
  if(!file){ toast('Choose an image first.', 'error'); return; }
  const fd = new FormData();
  fd.append('type', 2);
  fd.append('logo_image', file);
  const $btn = $(this);
  $btn.prop('disabled', true).text('Uploading...');
  $.ajax({
    url: 'routes.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
    success: function(res){
      if(res.statusCode == 200){
        toast('Logo updated.');
        $('#logoPreview').replaceWith('<img id="logoPreview" src="../' + res.path + '?t=' + Date.now() + '" style="max-width:100%;max-height:100%;">');
      }else{
        toast(res.message || 'Upload failed.', 'error');
      }
    },
    error: function(){ toast('Something went wrong.', 'error'); },
    complete: function(){ $btn.prop('disabled', false).text('Upload logo'); }
  });
});

/* ---------- Upload hero image ---------- */
$('#uploadHeroBtn').on('click', function(){
  const file = $('#heroFile')[0].files[0];
  if(!file){ toast('Choose an image first.', 'error'); return; }
  const fd = new FormData();
  fd.append('type', 3);
  fd.append('hero_image', file);
  const $btn = $(this);
  $btn.prop('disabled', true).text('Uploading...');
  $.ajax({
    url: 'routes.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
    success: function(res){
      if(res.statusCode == 200){
        toast('Header image updated.');
        $('#heroPreview').attr('src', '../' + res.path + '?t=' + Date.now());
      }else{
        toast(res.message || 'Upload failed.', 'error');
      }
    },
    error: function(){ toast('Something went wrong.', 'error'); },
    complete: function(){ $btn.prop('disabled', false).text('Upload header image'); }
  });
});

/* ---------- Add team member ---------- */
$('#addMemberForm').on('submit', function(e){
  e.preventDefault();
  const $btn = $('#addMemberBtn');
  $('#g_member_error').hide();

  if(!$('#m_name').val().trim()){
    $('#g_member_error').show();
    $('#memberError').text('Name is required.');
    return;
  }

  const fd = new FormData(this);
  fd.append('type', 4);
  $btn.prop('disabled', true).text('Adding...');

  $.ajax({
    url: 'routes.php', type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
    success: function(res){
      if(res.statusCode == 200){
        toast('Team member added.');
        setTimeout(function(){ location.reload(); }, 600);
      }else{
        $('#g_member_error').show();
        $('#memberError').text(res.message || 'Unable to add team member.');
      }
    },
    error: function(){
      $('#g_member_error').show();
      $('#memberError').text('Something went wrong.');
    },
    complete: function(){ $btn.prop('disabled', false).text('Add member'); }
  });
});

/* ---------- Remove team member ---------- */
$(document).on('click', '.del-member', function(){
  const $row = $(this).closest('.team-row');
  const id = $(this).data('id');
  confirmModal('Remove this team member?', 'This cannot be undone.').then(function(ok){
    if(!ok) return;
    $.ajax({
      url: 'routes.php', type: 'POST', dataType: 'json',
      data: { type: 6, member_id: id },
      success: function(res){
        if(res.statusCode == 200){
          $row.remove();
          toast('Team member removed.');
        }else{
          toast(res.message || 'Unable to remove.', 'error');
        }
      },
      error: function(){ toast('Something went wrong.', 'error'); }
    });
  });
});
</script>

</body>
</html>
