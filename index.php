<?php
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$settings = $data->get_settings();

function pfms_setting($settings, $key, $default = ''){
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
}

$siteName    = pfms_setting($settings, 'site_name', 'PFMS');
$logoImage   = pfms_setting($settings, 'logo_image', '');
$heroImage   = pfms_setting($settings, 'hero_image', 'assets/img/header-b.PNG');
$heroTitle   = pfms_setting($settings, 'hero_title', 'One page for every dollar in, every dollar out.');
$heroSub     = pfms_setting($settings, 'hero_subtitle', 'PFMS brings your income, expenses, budgets and investments together in a single ledger.');
$footerText  = pfms_setting($settings, 'footer_text', '© 2026 PFMS. Personal finance tracking & awareness — not financial advice.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($siteName) ?> — Personal Finance Management System</title>
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
<script src="assets/js/theme.js" defer></script>
</head>
<body>

<nav class="pub-nav">
  <div class="name">
    <a href="index.php" style="text-decoration:none;display:flex;align-items:center;gap:.5em;">
      <?php if($logoImage): ?>
        <img src="<?= htmlspecialchars($logoImage) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="height:28px;width:auto;display:block;">
      <?php else: ?>
        <?= htmlspecialchars($siteName) ?>
      <?php endif; ?>
    </a>
  </div>
  <div class="links">
    <a href="about.php">About</a>
    <a href="index.php#features">Features</a>
    <button type="button" class="theme-toggle" aria-label="Toggle dark mode">
      <span class="ico" data-theme-icon>&#9789;</span><span data-theme-label>Dark mode</span>
    </button>
    <a href="login.php">Log in</a>
    <a class="btn-ledger brass sm" href="register.php">Get started</a>
  </div>
</nav>

<!-- HERO -->
<header style="
  padding:6.5rem 5vw 5rem;
  border-bottom:1px solid var(--line);
  background:
    linear-gradient(
      90deg,
      rgba(116, 96, 96, 0.363) 0%,
      rgba(20, 20, 20, 0.65) 45%,
      rgba(20, 20, 20, 0.25) 100%
    ),
    url('<?= htmlspecialchars($heroImage) ?>') center/cover no-repeat;
  color:white;
">
  <div style="max-width:640px;">
    <h1 style="font-size:3.1rem; line-height:1.1;color:white;">
      <?= htmlspecialchars($heroTitle) ?>
    </h1>

    <p style="font-size:1.1rem; max-width:520px;color:rgba(255,255,255,.88);">
      <?= htmlspecialchars($heroSub) ?>
    </p>

    <div style="display:flex; gap:1rem; margin-top:1.8rem; flex-wrap:wrap;">
      <a class="btn-ledger brass" href="register.php">Create your account</a>
      <a class="btn-ledger ghost" href="login.php" style="color:white;border-color:rgba(255,255,255,.45);">I already have one</a>
    </div>
  </div>
</header>

<!-- FEATURES -->
<section id="features" style="padding:5rem 5vw; border-bottom:1px solid var(--line);">
  <h2 style="max-width:460px;">Six modules. One ledger. Nothing you don't need.</h2>
  <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1px; background:var(--line); border:1px solid var(--line); margin-top:2rem;">
    <div class="ledger-panel" style="border:none; border-radius:0;">
      <h3>Income &amp; expense tracking</h3>
      <p>Record every transaction with a category, date and note — searchable and editable any time.</p>
    </div>
    <div class="ledger-panel" style="border:none; border-radius:0;">
      <h3>Monthly budgeting</h3>
      <p>Set a spending limit per category and watch progress bars fill as the month goes on.</p>
    </div>
    <div class="ledger-panel" style="border:none; border-radius:0;">
      <h3>Financial dashboard</h3>
      <p>Charts and summary cards turn raw entries into a picture you can actually read.</p>
    </div>
    <div class="ledger-panel" style="border:none; border-radius:0;">
      <h3>Investment tracking</h3>
      <p>Log what you've invested and its current value to see gain or loss — tracking, not advice.</p>
    </div>
    <div class="ledger-panel" style="border:none; border-radius:0;">
      <h3>Reports &amp; history</h3>
      <p>Filterable transaction history and month-end reports summarise where your money went.</p>
    </div>
    <div class="ledger-panel" style="border:none; border-radius:0;">
      <h3>Secure by default</h3>
      <p>Hashed passwords, private sessions and account-scoped records — your ledger is yours alone.</p>
    </div>
  </div>
</section>

<footer style="padding:2.5rem 5vw; display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; font-size:.85rem; color:var(--ink-faint);">
  <div><?= htmlspecialchars($footerText) ?></div>
  <div style="display:flex; gap:1.5rem;">
    <a href="index.php">Home</a>
    <a href="about.php">About</a>
  </div>
</footer>

</body>
</html>
