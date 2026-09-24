<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Recover account — PFMS</title>

<script>
(function(){
  try{
    var t = localStorage.getItem('pfms-theme');
    if(!t){ t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'; }
    document.documentElement.setAttribute('data-theme', t);
  }catch(e){}
})();
</script>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

<script
    src="https://code.jquery.com/jquery-3.7.1.min.js">
</script>
<script src="assets/js/theme.js" defer></script>

</head>


<body>


<!-- =========================
     NAVIGATION
========================= -->

<nav class="pub-nav">

    <div class="brand">

        <div class="name">

            <a
                href="index.php"
                style="text-decoration:none;"
            >
                PFMS
            </a>

        </div>

    </div>


    <div class="links">

        <a href="index.php">
            Home
        </a>

        <a href="about.php">
            About
        </a>

        <button type="button" class="theme-toggle" aria-label="Toggle dark mode">
            <span class="ico" data-theme-icon>&#9789;</span><span data-theme-label>Dark mode</span>
        </button>

        <a href="login.php">
            Log in
        </a>

        <a
            class="btn-ledger brass sm"
            href="register.php"
        >
            Get started
        </a>

    </div>

</nav>



<!-- =========================
     AUTH WRAPPER
========================= -->

<div class="auth-wrap">


    <!-- =========================
         LEFT SIDE
    ========================= -->

    <div class="auth-side">

        <div
            class="brand"
            style="display:flex;align-items:center;gap:.6em;"
        >

            <div
                class="name"
                style="font-family:var(--font-display);font-size:1.2rem;"
            >
                PFMS
            </div>

        </div>


        <p class="ledger-quote">

            Lost access to your account?
            We'll help you get your ledger back on track.

        </p>

    </div>



    <!-- =========================
         FORM SIDE
    ========================= -->

    <div class="auth-form-side">

        <div class="auth-box">


            <h2>
                Recover account
            </h2>


            <p style="margin-bottom:1.6rem;">

                Remembered it after all?

                <a href="login.php">
                    Log in
                </a>

            </p>



            <!-- =========================
                 RECOVERY FORM
            ========================= -->

            <form
                id="recoverForm"
                method="post"
                novalidate
            >


                <!-- EMAIL -->

                <div
                    class="field-group"
                    id="g_email"
                >

                    <label
                        class="field-label"
                        for="email"
                    >
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

                <div
                    class="field-group"
                    id="g_phone"
                >

                    <label
                        class="field-label"
                        for="phone"
                    >
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



                <!-- NEW PASSWORD -->

                <div
                    class="field-group"
                    id="g_password"
                >

                    <label
                        class="field-label"
                        for="password"
                    >
                        New password
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


                    <label
                        style="
                            display:flex;
                            align-items:center;
                            gap:.45em;
                            margin-top:.6em;
                            font-size:.85rem;
                            cursor:pointer;
                        "
                    >

                        <input
                            type="checkbox"
                            data-show-password="password"
                        >

                        Show password

                    </label>

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
                    id="recoverBtn"
                    style="width:100%;"
                >
                    Recover account
                </button>


            </form>


        </div>

    </div>

</div>



<script src="assets/js/ui.js"></script>


<script>

$(document).ready(function () {


    $("#recoverForm").on("submit", function (e) {

        /*
         * Prevent normal form submission.
         * AJAX will handle the request.
         */
        e.preventDefault();

        e.stopPropagation();


        // -------------------------
        // HIDE OLD SERVER ERROR
        // -------------------------

        $("#g_form_error").hide();

        $("#formError").text("");


        // -------------------------
        // GET VALUES
        // -------------------------

        var email = $("#email").val().trim();

        var phone = $("#phone").val().trim();

        var password = $("#password").val();


        var valid = true;


        // -------------------------
        // EMAIL VALIDATION
        // -------------------------

        if (
            !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
        ) {

            $("#g_email").addClass("has-error");

            valid = false;

        } else {

            $("#g_email").removeClass("has-error");

        }


        // -------------------------
        // PHONE VALIDATION
        // -------------------------

        if (phone.length < 6) {

            $("#g_phone").addClass("has-error");

            valid = false;

        } else {

            $("#g_phone").removeClass("has-error");

        }


        // -------------------------
        // PASSWORD VALIDATION
        // -------------------------

        if (
            password.length < 8 ||
            !/[A-Za-z]/.test(password) ||
            !/[0-9]/.test(password)
        ) {

            $("#g_password").addClass("has-error");

            valid = false;

        } else {

            $("#g_password").removeClass("has-error");

        }


        // -------------------------
        // STOP IF INVALID
        // -------------------------

        if (!valid) {

            return false;

        }


        // -------------------------
        // DISABLE BUTTON
        // -------------------------

        var recoverBtn = $("#recoverBtn");


        recoverBtn
            .prop("disabled", true)
            .text("Recovering...");


        // -------------------------
        // AJAX REQUEST
        // -------------------------

        $.ajax({

            url: "include/routes.php",

            type: "POST",

            data: {

                type: 3,

                email: email,

                phone: phone,

                password: password

            },

            dataType: "json",


            // -------------------------
            // SUCCESS
            // -------------------------

            success: function (response) {

                console.log(response);


                // PASSWORD CHANGED
                if (response.statusCode == 200) {


                    if (typeof toast === "function") {

                        toast(
                            response.message ||
                            "Password changed successfully."
                        );

                    }


                    setTimeout(function () {

                        window.location.href =
                            "login.php";

                    }, 900);


                }


                // USER NOT FOUND
                else {

                    $("#g_form_error").show();


                    $("#formError").text(
                        response.message ||
                        "User not found"
                    );

                }

            },


            // -------------------------
            // AJAX ERROR
            // -------------------------

            error: function (
                xhr,
                status,
                error
            ) {

                console.log(
                    "AJAX error:",
                    error
                );


                console.log(
                    "Server response:",
                    xhr.responseText
                );


                $("#g_form_error").show();


                $("#formError").text(
                    "Something went wrong. Please try again."
                );

            },


            // -------------------------
            // COMPLETE
            // -------------------------

            complete: function () {

                recoverBtn
                    .prop("disabled", false)
                    .text("Recover account");

            }

        });


        return false;

    });

});

</script>


</body>

</html>