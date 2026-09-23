<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PFMS — Personal Finance Management System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="pub-nav">
  <div class="name"> <a href="index.php" style="text-decoration: none;">PFMS</a></div>
  <div class="links">
    <a href="about.php">About</a>
    <a href="index.php#features">Features</a>
    <a href="login.php">Log in</a>
    <a class="btn-ledger brass sm" href="register.php">Get started</a>
  </div>
</nav>

<!-- HERO -->
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
    url('assets/img/header-b.PNG') center/cover no-repeat;
  color:white;
">
  <div style="max-width:640px;">
    <h1 style="font-size:3.1rem; line-height:1.1;">
      One page for every dollar in, every dollar out.
    </h1>

    <p style="font-size:1.1rem; max-width:520px;">
      PFMS brings your income, expenses, budgets and investments together
      in a single ledger — so you always know exactly where you stand,
      without juggling spreadsheets and screenshots.
    </p>

    <div style="display:flex; gap:1rem; margin-top:1.8rem; flex-wrap:wrap;">
      <a class="btn-ledger brass" href="register.php">Create your account</a>
      <a class="btn-ledger ghost" href="login.php">I already have one</a>
    </div>
  </div>
</header>



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
  <div>© 2026 PFMS. Personal finance tracking &amp; awareness — not financial advice.</div>
  <div style="display:flex; gap:1.5rem;">
    <a href="index.php">Home</a>
    <a href="about.php">About</a>
  </div>
</footer>

</body>
</html>
