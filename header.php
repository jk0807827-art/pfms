<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$sidebarUserName  = $_SESSION['user_name']  ?? 'Account';
$sidebarUserEmail = $_SESSION['user_email'] ?? '';

$sidebarInitials = '';
foreach(preg_split('/\s+/', trim($sidebarUserName)) as $part){
    if($part !== ''){
        $sidebarInitials .= strtoupper(substr($part, 0, 1));
    }
    if(strlen($sidebarInitials) >= 2) break;
}
if($sidebarInitials === ''){
    $sidebarInitials = 'U';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle ?? 'Personal Finance Management System'; ?></title>
<script>
(function(){
  try{
    var t = localStorage.getItem('pfms-theme');
    if(!t){ t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'; }
    document.documentElement.setAttribute('data-theme', t);
  }catch(e){}
})();
</script>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="assets/js/theme.js" defer></script>

</head>
<body>

<div class="shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="mark">£</div>
      <div class="name">PFMS</div>
    </div>
    <nav>

      <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
        <span class="ico">&#9670;</span><span>Dashboard</span>
      </a>

      <a class="nav-link <?= $currentPage === 'income.php' ? 'active' : '' ?>" href="income.php">
        <span class="ico">&#65291;</span><span>Income</span>
      </a>

      <a class="nav-link <?= $currentPage === 'expenses.php' ? 'active' : '' ?>" href="expenses.php">
        <span class="ico">&#65293;</span><span>Expenses</span>
      </a>

      <a class="nav-link <?= $currentPage === 'budgets.php' ? 'active' : '' ?>" href="budgets.php">
        <span class="ico">&#9638;</span><span>Budgets</span>
      </a>

      <a class="nav-link <?= $currentPage === 'investments.php' ? 'active' : '' ?>" href="investments.php">
        <span class="ico">&#9650;</span><span>Investments</span>
      </a>

      <a class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>" href="categories.php">
        <span class="ico">&#127991;</span><span>Categories</span>
      </a>

      <a class="nav-link <?= $currentPage === 'transactions.php' ? 'active' : '' ?>" href="transactions.php">
        <span class="ico">&#8801;</span><span>Transactions</span>
      </a>

      <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php">
        <span class="ico">&#9636;</span><span>Reports</span>
      </a>

      <a class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>" href="settings.php">
        <span class="ico">&#9881;</span><span>Settings</span>
      </a>

      <?php if(!empty($_SESSION['is_admin'])): ?>
      <a class="nav-link" href="admin/dashboard.php" style="margin-top:.6em;border-top:1px solid rgba(238,234,224,.12);padding-top:1em;">
        <span class="ico">&#9733;</span><span>Admin Panel</span>
      </a>
      <?php endif; ?>

    </nav>
    <div class="sidebar-foot">
      <div class="who"><strong><?= htmlspecialchars($sidebarUserName) ?></strong><?= htmlspecialchars($sidebarUserEmail) ?></div>
      <button type="button" class="theme-toggle" aria-label="Toggle dark mode">
        <span class="ico" data-theme-icon>&#9789;</span><span data-theme-label>Dark mode</span>
      </button>
      <a class="btn-ledger ghost sm" href="logout.php" id="logoutBtn" style="width:100%;color:var(--sidebar-ink);border-color:rgba(238,234,224,.3);margin-top:.6em;">Log out</a>
    </div>
  </aside>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>