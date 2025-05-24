<?php
// index.php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// If no user is logged in, redirect to the login page
header('Location: login.php');
exit();
?>