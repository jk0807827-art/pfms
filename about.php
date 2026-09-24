<?php
require_once 'config/db.php';
require_once 'include/core.php';

$data = new core($conn);
$settings = $data->get_settings();
$team = $data->get_team_members();

function pfms_setting($settings, $key, $default = ''){
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
}

$siteName      = pfms_setting($settings, 'site_name', 'PFMS');
$logoImage     = pfms_setting($settings, 'logo_image', '');
$aboutTitle    = pfms_setting($settings, 'about_hero_title', 'Who We Are');
$aboutText1    = pfms_setting($settings, 'about_hero_text1', '');
$aboutText2    = pfms_setting($settings, 'about_hero_text2', '');
$teamTitle     = pfms_setting($settings, 'about_team_title', 'Meet Our Team');
$teamIntro     = pfms_setting($settings, 'about_team_intro', '');
$footerText    = pfms_setting($settings, 'footer_text', '© 2026 PFMS. Personal finance tracking & awareness — not financial advice.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us — <?= htmlspecialchars($siteName) ?></title>
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

  <style>
    .about-hero {
      padding: 6rem 5vw 4rem;
      border-bottom: 1px solid var(--line);
      background:
        radial-gradient(ellipse at top right, var(--brass-tint) 0%, transparent 55%);
    }

    .about-hero p {
      max-width: 650px;
      font-size: 1.1rem;
      line-height: 1.8;
      color: var(--ink-muted);
    }

    .team-section {
      padding: 5rem 5vw;
    }

    .team-intro {
      max-width: 600px;
      margin-bottom: 2.5rem;
    }

    .team-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1px;
      background: var(--line);
      border: 1px solid var(--line);
    }

    .team-card {
      background: var(--paper);
      padding: 2rem;
      text-align: center;
    }

    .team-photo {
      width: 150px;
      height: 150px;
      margin: 0 auto 1.5rem;
      border-radius: 50%;
      object-fit: cover;
      display: block;
      border: 3px solid var(--brass);
    }

    .team-card h3 {
      margin-bottom: .5rem;
    }

    .student-id {
      color: var(--ink-faint);
      font-size: .9rem;
      margin-bottom: 1rem;
    }

    .team-role {
      font-size: .85rem;
      color: var(--brass);
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    @media (max-width: 900px) {
      .team-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 600px) {
      .team-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <!-- NAVIGATION -->
  <nav class="pub-nav">
    <div class="brand">
      <div class="name">
        <a href="index.php" style="text-decoration: none;display:flex;align-items:center;gap:.5em;">
          <?php if($logoImage): ?>
            <img src="<?= htmlspecialchars($logoImage) ?>" alt="<?= htmlspecialchars($siteName) ?>" style="height:28px;width:auto;display:block;">
          <?php else: ?>
            <?= htmlspecialchars($siteName) ?>
          <?php endif; ?>
        </a>
      </div>
    </div>

    <div class="links">
      <a href="index.php">Home</a>
      <button type="button" class="theme-toggle" aria-label="Toggle dark mode">
        <span class="ico" data-theme-icon>&#9789;</span><span data-theme-label>Dark mode</span>
      </button>
      <a href="login.php">Log in</a>
      <a class="btn-ledger brass sm" href="register.php">Get started</a>
    </div>
  </nav>


  <!-- ABOUT HERO -->
  <header class="about-hero">
    <div style="max-width: 750px;">
      <h1 style="font-size: 3.1rem; line-height: 1.1;">
        <?= htmlspecialchars($aboutTitle) ?>
      </h1>

      <?php if($aboutText1): ?>
      <p><?= nl2br(htmlspecialchars($aboutText1)) ?></p>
      <?php endif; ?>

      <?php if($aboutText2): ?>
      <p><?= nl2br(htmlspecialchars($aboutText2)) ?></p>
      <?php endif; ?>
    </div>
  </header>


  <!-- TEAM -->
  <section class="team-section">

    <div class="team-intro">
      <h2><?= htmlspecialchars($teamTitle) ?></h2>

      <?php if($teamIntro): ?>
      <p><?= htmlspecialchars($teamIntro) ?></p>
      <?php endif; ?>
    </div>


    <div class="team-grid">

      <?php foreach($team as $member): ?>
      <div class="team-card">
        <img
          src="<?= htmlspecialchars($member['photo'] ?: 'assets/img/header-b.PNG') ?>"
          alt="<?= htmlspecialchars($member['name']) ?>"
          class="team-photo"
        >

        <h3><?= htmlspecialchars($member['name']) ?></h3>

        <?php if($member['student_id']): ?>
        <div class="student-id">
          Student ID: <?= htmlspecialchars($member['student_id']) ?>
        </div>
        <?php endif; ?>

        <div class="team-role">
          <?= htmlspecialchars($member['role']) ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if(empty($team)): ?>
      <div class="team-card" style="grid-column:1/-1;color:var(--ink-faint);">
        Team members will appear here once added from the admin panel.
      </div>
      <?php endif; ?>

    </div>
  </section>


  <!-- FOOTER -->
  <footer style="
    padding:2.5rem 5vw;
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:1rem;
    font-size:.85rem;
    color:var(--ink-faint);
  ">

    <div>
      <?= htmlspecialchars($footerText) ?>
    </div>

    <div style="display:flex; gap:1.5rem;">
      <a href="index.php">Home</a>
    <a href="about.php">About</a>
    </div>

  </footer>

</body>
</html>
