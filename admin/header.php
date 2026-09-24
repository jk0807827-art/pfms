<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$sidebarUserName  = $_SESSION['user_name']  ?? 'Admin';
$sidebarUserEmail = $_SESSION['user_email'] ?? '';

$sidebarInitials = '';
foreach(preg_split('/\s+/', trim($sidebarUserName)) as $part){
    if($part !== ''){
        $sidebarInitials .= strtoupper(substr($part, 0, 1));
    }
    if(strlen($sidebarInitials) >= 2) break;
}
if($sidebarInitials === ''){
    $sidebarInitials = 'A';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle ?? 'Admin — Personal Finance Management System'; ?></title>
<script>
(function(){
  try{
    var t = localStorage.getItem('pfms-theme');
    if(!t){ t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'; }
    document.documentElement.setAttribute('data-theme', t);
  }catch(e){}
})();
</script>
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../assets/js/theme.js" defer></script>

</head>
<body>

<div class="shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="mark">£</div>
      <div class="name">PFMS Admin</div>
    </div>
    <nav>

      <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
        <span class="ico">&#9670;</span><span>Dashboard</span>
      </a>

      <a class="nav-link <?= $currentPage === 'content.php' ? 'active' : '' ?>" href="content.php">
        <span class="ico">&#9998;</span><span>Site Content</span>
      </a>

      <a class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="users.php">
        <span class="ico">&#128100;</span><span>Users</span>
      </a>

      <a class="nav-link" href="../dashboard.php" style="margin-top:.6em;border-top:1px solid rgba(238,234,224,.12);padding-top:1em;">
        <span class="ico">&#8617;</span><span>Back to app</span>
      </a>

    </nav>
    <div class="sidebar-foot">
      <div class="who"><strong><?= htmlspecialchars($sidebarUserName) ?></strong><?= htmlspecialchars($sidebarUserEmail) ?></div>
      <button type="button" class="theme-toggle" aria-label="Toggle dark mode">
        <span class="ico" data-theme-icon>&#9789;</span><span data-theme-label>Dark mode</span>
      </button>
      <a class="btn-ledger ghost sm" href="../logout.php" style="width:100%;color:var(--sidebar-ink);border-color:rgba(238,234,224,.3);margin-top:.6em;">Log out</a>
    </div>
  </aside>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
