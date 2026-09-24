<?php
$pageTitle = "Admin Dashboard - PFMS";

require 'auth.php';
require_once '../config/db.php';
require_once '../include/core.php';

$data = new core($conn);
$stats = $data->get_admin_dashboard_stats();

include 'header.php';
?>

  <div class="main">
    <div class="mobile-topbar">
      <button id="menuToggle" aria-label="Open menu">&#9776;</button>
      <span class="name">Admin Dashboard</span>
    </div>
    <header class="topbar">
      <div>
        <div class="breadcrumb">Admin</div>
        <h1 class="page-title">Dashboard</h1>
      </div>
      <div style="display:flex;align-items:center;gap:.8rem;">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--brass-tint);color:var(--brass-deep);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;"><?= htmlspecialchars($sidebarInitials) ?></div>
      </div>
    </header>

    <main class="content" id="pageContent">

      <div class="stat-row">
        <div class="stat">
          <div class="stat-label">Total users</div>
          <div class="stat-value"><?= (int)$stats['total_users'] ?></div>
          <div class="stat-sub"><?= (int)$stats['active_users'] ?> active</div>
        </div>
        <div class="stat">
          <div class="stat-label">Total income logged</div>
          <div class="stat-value">$<?= number_format((float)$stats['total_income'], 2) ?></div>
        </div>
        <div class="stat">
          <div class="stat-label">Total expenses logged</div>
          <div class="stat-value">$<?= number_format((float)$stats['total_expenses'], 2) ?></div>
        </div>
        <div class="stat">
          <div class="stat-label">Investments on record</div>
          <div class="stat-value"><?= (int)$stats['total_investments'] ?></div>
        </div>
      </div>

      <div class="grid-2 even">
        <div class="ledger-panel">
          <div class="panel-head">
            <div>
              <h3>Site content</h3>
              <div class="panel-sub">Logo, header image, hero text, footer text, about page &amp; team</div>
            </div>
          </div>
          <p>Edit everything visitors see on the homepage and the About page — the header logo, the hero banner image and copy, the footer text, and the About page's story and team cards.</p>
          <a class="btn-ledger brass sm" href="content.php">Edit site content</a>
        </div>

        <div class="ledger-panel">
          <div class="panel-head">
            <div>
              <h3>Users</h3>
              <div class="panel-sub">Activate, deactivate or promote accounts</div>
            </div>
          </div>
          <p>See every registered account, disable access for an account, or grant/revoke admin rights.</p>
          <a class="btn-ledger brass sm" href="users.php">Manage users</a>
        </div>
      </div>

    </main>
  </div>
</div>

<div id="toast"></div>

<script src="../assets/js/ui.js"></script>

</body>
</html>
