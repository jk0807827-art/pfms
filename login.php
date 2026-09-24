<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in — PFMS</title>
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

<!-- NAVIGATION -->
<nav class="pub-nav">
  <div class="brand">
    <div class="name">
      <a href="index.php" style="text-decoration: none;">PFMS</a>
    </div>
  </div>

  <div class="links">
    <a href="index.php">Home</a>
    <a href="about.php">About</a>
    <button type="button" class="theme-toggle" aria-label="Toggle dark mode">
      <span class="ico" data-theme-icon>&#9789;</span><span data-theme-label>Dark mode</span>
    </button>
    <a href="login.php">Log in</a>
    <a class="btn-ledger brass sm" href="register.php">Get started</a>
  </div>
</nav>


<div class="auth-wrap">

  <div class="auth-side">

    <div class="brand" style="display:flex;align-items:center;gap:.6em;">
      <div class="name" style="font-family:var(--font-display);font-size:1.2rem;">
        PFMS
      </div>
    </div>

    <p class="ledger-quote">
      Welcome! Your ledger picked up exactly where you left it.
    </p>

    <div style="font-size:.82rem; color:#9AA4B8;">
      
    </div>

  </div>


  <div class="auth-form-side">

    <div class="auth-box">

      <h2>Log in</h2>

      <p style="margin-bottom:1.6rem;">
        Don't have an account?
        <a href="register.php">Register</a>
      </p>


      <form id="loginForm" method="post" novalidate>

        <!-- EMAIL -->
        <div class="field-group" id="g_email">

          <label class="field-label" for="email">
            Email
          </label>

          <input
            class="field-control"
            type="email"
            id="email"
            name="email"
            placeholder="kiran@gmail.com"
          >

          <div class="field-error">
            Enter a valid email address.
          </div>

        </div>


        <!-- PASSWORD -->
        <div class="field-group" id="g_password">

          <label class="field-label" for="password">
            Password
          </label>

          <input
            class="field-control"
            type="password"
            id="password"
            name="password"
            placeholder="Your password"
          >

          <label style="display:flex; align-items:center; gap:.45em; margin-top:.6em; font-size:.85rem; cursor:pointer;">

            <input
              type="checkbox"
              data-show-password="password"
            >

            Show password

          </label>


          <div style="text-align:right; margin-top:.4em;">

            <a
              href="forgot.php"
              style="font-size:.8rem;"
            >
              Forgot password?
            </a>

          </div>

        </div>


        <!-- SERVER ERROR -->
        <div
          class="field-group"
          id="g_form_error"
          style="display:none;"
        >

          <div
            class="field-error"
            style="display:block;"
            id="formError"
          ></div>

        </div>


        <!-- LOGIN BUTTON -->
        <button
          type="submit"
          class="btn-ledger brass"
          id="loginBtn"
          style="width:100%;"
        >
          Log in
        </button>

      </form>

    </div>

  </div>

</div>


<script src="assets/js/ui.js"></script>

<script>

$(document).ready(function () {
    $("#loginForm").submit(function (e) {
        e.preventDefault();
        // Hide previous server error
        $("#g_form_error").hide();
        $("#formError").text("");
        // Get values
        var email = $("#email").val().trim();
        var password = $("#password").val();
        var valid = true;
        // -------------------------
        // EMAIL VALIDATION
        // -------------------------
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {

            $("#g_email").addClass("has-error");

            valid = false;

        } else {

            $("#g_email").removeClass("has-error");

        }
        // -------------------------
        // PASSWORD VALIDATION
        // -------------------------
        if (password.length === 0) {
            $("#g_password").addClass("has-error");
            valid = false;
        } else {
            $("#g_password").removeClass("has-error");
        }
        // Stop if validation failed
        if (!valid) {
            return;
        }
        // -------------------------
        // DISABLE BUTTON
        // -------------------------
        $("#loginBtn")
            .prop("disabled", true)
            .text("Logging in...");
        // -------------------------
        // AJAX LOGIN
        // -------------------------
        $.ajax({
            url: "include/routes.php",
            type: "POST",
            data: {
                type: 1,
                email: email,
                password: password
            },
            dataType: "json",
            success: function (response) {
                console.log(response);
                // LOGIN SUCCESS
                if (response.statusCode == 200) {
                    if (response.role == "admin") {
                        window.location.href = "admin/dashboard.php";
                    } else {
                        window.location.href = "dashboard.php";
                    }
                }
                // INVALID LOGIN
                else if (response.statusCode == 202) {
                    $("#g_form_error").show();
                    $("#formError").text(
                        "Invalid Email or Password"
                    );
                }
                // OTHER RESPONSE
                else {
                    $("#g_form_error").show();
                    $("#formError").text(
                        response.message ||
                        "Invalid Email or Password"
                    );
                }
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseText);
                $("#g_form_error").show();
                $("#formError").text(
                    "Something went wrong. Please try again."
                );
            },
            complete: function () {
                $("#loginBtn")
                    .prop("disabled", false)
                    .text("Log in");
            }

        });

    });

});

</script>

</body>
</html>
