<?php
$pageTitle = "Users - PFMS Admin";

require 'auth.php';
require_once '../config/db.php';
require_once '../include/core.php';

$data = new core($conn);
$users = $data->get_all_users();
$myId = $_SESSION['user_id'];

include 'header.php';
?>

  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Users</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Admin</div>
        <h1 class="page-title">Users</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>

    <main class="content" id="pageContent">

      <div class="ledger-panel">
        <div class="panel-head">
          <div><h3>All accounts</h3><div class="panel-sub"><?= count($users) ?> registered</div></div>
        </div>

        <table class="ledger">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Role</th>
              <th>Joined</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="usersBody">
            <?php foreach($users as $u): ?>
            <tr data-id="<?= (int)$u['user_id'] ?>">
              <td><?= htmlspecialchars($u['full_name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['phone']) ?></td>
              <td>
                <span class="tag <?= $u['is_active'] ? 'gain' : 'loss' ?> status-tag">
                  <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td>
                <span class="tag <?= $u['is_admin'] ? 'neutral' : '' ?> role-tag" style="<?= $u['is_admin'] ? '' : 'background:transparent;color:var(--ink-faint);' ?>">
                  <?= $u['is_admin'] ? 'Admin' : 'Customer' ?>
                </span>
              </td>
              <td><?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?></td>
              <td class="row-actions">
                <button type="button" class="toggle-active" data-id="<?= (int)$u['user_id'] ?>" data-active="<?= (int)$u['is_active'] ?>">
                  <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                </button>
                <?php if((int)$u['user_id'] !== (int)$myId): ?>
                <button type="button" class="toggle-admin" data-id="<?= (int)$u['user_id'] ?>" data-admin="<?= (int)$u['is_admin'] ?>">
                  <?= $u['is_admin'] ? 'Revoke admin' : 'Make admin' ?>
                </button>
                <?php else: ?>
                <span style="font-size:.75rem;color:var(--ink-faint);">(you)</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($users)): ?>
            <tr><td colspan="7" class="table-empty">No users found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </main>
  </div>
</div>

<div id="toast"></div>

<script src="../assets/js/ui.js"></script>
<script>

$(document).on('click', '.toggle-active', function(){
  const $btn = $(this);
  const id = $btn.data('id');
  const goingActive = $btn.data('active') == 0 ? 1 : 0;

  $btn.prop('disabled', true);

  $.ajax({
    url: 'routes.php', type: 'POST', dataType: 'json',
    data: { type: 7, user_id: id, active: goingActive },
    success: function(res){
      if(res.statusCode == 200){
        const $row = $btn.closest('tr');
        $btn.data('active', goingActive).text(goingActive ? 'Deactivate' : 'Activate');
        $row.find('.status-tag')
          .removeClass('gain loss')
          .addClass(goingActive ? 'gain' : 'loss')
          .text(goingActive ? 'Active' : 'Inactive');
        toast('User updated.');
      }else{
        toast(res.message || 'Unable to update user.', 'error');
      }
    },
    error: function(){ toast('Something went wrong.', 'error'); },
    complete: function(){ $btn.prop('disabled', false); }
  });
});

$(document).on('click', '.toggle-admin', function(){
  const $btn = $(this);
  const id = $btn.data('id');
  const goingAdmin = $btn.data('admin') == 0 ? 1 : 0;

  $btn.prop('disabled', true);

  $.ajax({
    url: 'routes.php', type: 'POST', dataType: 'json',
    data: { type: 8, user_id: id, admin: goingAdmin },
    success: function(res){
      if(res.statusCode == 200){
        const $row = $btn.closest('tr');
        $btn.data('admin', goingAdmin).text(goingAdmin ? 'Revoke admin' : 'Make admin');
        $row.find('.role-tag')
          .text(goingAdmin ? 'Admin' : 'Customer')
          .attr('style', goingAdmin ? '' : 'background:transparent;color:var(--ink-faint);');
        toast('User updated.');
      }else{
        toast(res.message || 'Unable to update user.', 'error');
      }
    },
    error: function(){ toast('Something went wrong.', 'error'); },
    complete: function(){ $btn.prop('disabled', false); }
  });
});

</script>

</body>
</html>
