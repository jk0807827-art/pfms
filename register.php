<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create account — PFMS</title>
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

      <div
        class="name"
        style="font-family:var(--font-display);font-size:1.2rem;"
      >
        PFMS
      </div>

    </div>

    <p class="ledger-quote">
      "A budget is telling your money where to go instead of wondering where it went."
    </p>

    <div style="font-size:.82rem; color:#9AA4B8;">
      Every record you add here stays private to your account.
    </div>

  </div>


  <div class="auth-form-side">

    <div class="auth-box">

      <h2>Create your account</h2>

      <p style="margin-bottom:1.6rem;">
        Already have one?
        <a href="login.php">Log in</a>
      </p>


      <form id="registerForm" method="post" novalidate>

        <!-- NAME -->
        <div class="field-group" id="g_name">

          <label class="field-label" for="name">
            Full name
          </label>

          <input
            class="field-control"
            type="text"
            id="name"
            name="name"
            placeholder="Eg: Prachi Kiran Patil or Bibek Lamichhane"
          >

          <div class="field-error">
            Please enter your full name.
          </div>

        </div>


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


        <!-- PHONE -->
        <div class="field-group" id="g_phone">

          <label class="field-label" for="phone">
            Phone number
          </label>

          <input
            class="field-control"
            type="text"
            id="phone"
            name="phone"
            placeholder="+61444450871"
          >

          <div class="field-error">
            Please enter your phone number.
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
            placeholder="At least 8 characters"
          >

          <div class="field-hint">
            Use 8+ characters with a number and a letter.
          </div>

          <div class="field-error">
            Password does not meet the requirements.
          </div>

        </div>


        <!-- CONFIRM PASSWORD -->
        <div class="field-group" id="g_confirm">

          <label class="field-label" for="confirm">
            Confirm password
          </label>

          <input
            class="field-control"
            type="password"
            id="confirm"
            name="confirm"
            placeholder="Re-enter your password"
          >

          <div class="field-error">
            Passwords do not match.
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


        <!-- SUBMIT -->
        <button
          type="submit"
          class="btn-ledger brass"
          id="registerBtn"
          style="width:100%;"
        >
          Create account
        </button>

      </form>

    </div>

  </div>

</div>


<script src="assets/js/ui.js"></script>


<script>

$(document).ready(function () {

    $("#registerForm").submit(function (e) {

        e.preventDefault();


        // Hide previous server error
        $("#g_form_error").hide();
        $("#formError").text("");


        // Get values
        var name = $("#name").val().trim();
        var email = $("#email").val().trim();
        var phone = $("#phone").val().trim();
        var password = $("#password").val();
        var confirm = $("#confirm").val();


        var valid = true;


        // -------------------------
        // NAME
        // -------------------------

        if(name.length < 2){

            $("#g_name").addClass("has-error");

            valid = false;

        }else{

            $("#g_name").removeClass("has-error");

        }


        // -------------------------
        // EMAIL
        // -------------------------

        if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){

            $("#g_email").addClass("has-error");

            valid = false;

        }else{

            $("#g_email").removeClass("has-error");

        }


        // -------------------------
        // PHONE
        // -------------------------

        if(phone.length < 6){

            $("#g_phone").addClass("has-error");

            valid = false;

        }else{

            $("#g_phone").removeClass("has-error");

        }


        // -------------------------
        // PASSWORD
        // -------------------------

        if(
            password.length < 8 ||
            !/[A-Za-z]/.test(password) ||
            !/[0-9]/.test(password)
        ){

            $("#g_password").addClass("has-error");

            valid = false;

        }else{

            $("#g_password").removeClass("has-error");

        }


        // -------------------------
        // CONFIRM PASSWORD
        // -------------------------

        if(confirm.length === 0 || confirm !== password){

            $("#g_confirm").addClass("has-error");

            valid = false;

        }else{

            $("#g_confirm").removeClass("has-error");

        }


        // Stop if validation failed
        if(!valid){

            return;

        }


        // -------------------------
        // DISABLE BUTTON
        // -------------------------

        $("#registerBtn")
            .prop("disabled", true)
            .text("Creating account...");


        // -------------------------
        // AJAX
        // -------------------------

        $.ajax({

            url: "include/routes.php",

            type: "POST",

            data: {

                type: 2,
                name: name,
                email: email,
                phone: phone,
                password: password

            },

            dataType: "json",


            success: function(response){

                console.log(response);


                // -------------------------
                // SUCCESS
                // -------------------------

                if(response.statusCode == 200){

                    window.location.href = "login.php";

                }


                // -------------------------
                // EMAIL EXISTS
                // -------------------------

                else if(response.statusCode == 202){

                    $("#g_form_error").show();

                    $("#formError").text(
                        response.message ||
                        "An account with this email already exists."
                    );

                }


                // -------------------------
                // OTHER ERROR
                // -------------------------

                else{

                    $("#g_form_error").show();

                    $("#formError").text(
                        response.message ||
                        "Unable to create your account."
                    );

                }

            },


            error: function(xhr, status, error){

                console.log(xhr.responseText);

                $("#g_form_error").show();

                $("#formError").text(
                    "Something went wrong. Please try again."
                );

            },


            complete: function(){

                $("#registerBtn")
                    .prop("disabled", false)
                    .text("Create account");

            }

        });

    });

});

</script>

</body>
</html>
