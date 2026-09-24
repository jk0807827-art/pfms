<?php

session_start();

if(!isset($_SESSION['user_id'])){

    header("Location: ../login.php");
    exit;

}

if(empty($_SESSION['is_admin'])){

    // Logged in, but not an admin — send them to their own dashboard.
    header("Location: ../dashboard.php");
    exit;

}

?>
