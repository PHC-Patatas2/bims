<?php
// logout.php
session_start();
include 'config.php'; // Include your database configuration

// Log the logout action to audit_trail
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $action = "Logged out";
    $timestamp = date("Y-m-d H:i:s");

    // Prepare and execute the statement to log the action
    // Ensure $pdo is your database connection object from config.php
    if (isset($pdo)) {
        $log_stmt = $pdo->prepare("INSERT INTO audit_trail (user_id, action, timestamp) VALUES (?, ?, ?)");
        if ($log_stmt) {
            $log_stmt->execute([$user_id, $action, $timestamp]);
        }
    } else {
        // Handle error: Database connection not available
        // This part depends on how you want to handle errors, e.g., log to a file or ignore
    }
}

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
