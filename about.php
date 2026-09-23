<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us — PFMS</title>
  <link rel="stylesheet" href="assets/css/style.css">

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
      <div class="name"><a href="index.php" style="text-decoration: none;">PFMS</a></div>
    </div>

    <div class="links">
      <a href="index.php">Home</a>
      <a href="login.php">Log in</a>
      <a class="btn-ledger brass sm" href="register.php">Get started</a>
    </div>
  </nav>


  <!-- ABOUT HERO -->
  <header class="about-hero">
    <div style="max-width: 750px;">
      <h1 style="font-size: 3.1rem; line-height: 1.1;">
        Who We Are
      </h1>

      <p>
        We are a team of four students who came together to design and
        develop PFMS — Personal Finance Management System. Our goal is to
        create a simple, practical and user-friendly platform that helps
        people organize their income, expenses, budgets and investments
        in one place.
      </p>

      <p>
        PFMS was created as a student project to combine our knowledge of
        web development, database management, user interface design and
        software engineering into a useful real-world application.
      </p>
    </div>
  </header>


  <!-- TEAM -->
  <section class="team-section">

    <div class="team-intro">
      <h2>Meet Our Team</h2>

      <p>
        Four students. One project. A shared goal of making personal
        finance easier to understand and manage.
      </p>
    </div>


    <div class="team-grid">

      <!-- MEMBER 1 -->
      <div class="team-card">
        <img
          src="assets/img/header-b.PNG"
          alt="Student 1"
          class="team-photo"
        >

        <h3>Student Name 1</h3>

        <div class="student-id">
          Student ID: YOUR-ID-001
        </div>

        <div class="team-role">
          Team Member
        </div>
      </div>


      <!-- MEMBER 2 -->
      <div class="team-card">
        <img
          src="assets/img/header-b.PNG"
          alt="Student 2"
          class="team-photo"
        >

        <h3>Student Name 2</h3>

        <div class="student-id">
          Student ID: YOUR-ID-002
        </div>

        <div class="team-role">
          Team Member
        </div>
      </div>


      <!-- MEMBER 3 -->
      <div class="team-card">
        <img
          src="assets/img/header-b.PNG"
          alt="Student 3"
          class="team-photo"
        >

        <h3>Student Name 3</h3>

        <div class="student-id">
          Student ID: YOUR-ID-003
        </div>

        <div class="team-role">
          Team Member
        </div>
      </div>


      <!-- MEMBER 4 -->
      <div class="team-card">
        <img
          src="assets/img/header-b.PNG"
          alt="Student 4"
          class="team-photo"
        >

        <h3>Student Name 4</h3>

        <div class="student-id">
          Student ID: YOUR-ID-004
        </div>

        <div class="team-role">
          Team Member
        </div>
      </div>

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
      © 2026 PFMS. Personal finance tracking &amp; awareness —
      not financial advice.
    </div>

    <div style="display:flex; gap:1.5rem;">
      <a href="index.php">Home</a>
    <a href="about.php">About</a>
    </div>

  </footer>

</body>
</html>
