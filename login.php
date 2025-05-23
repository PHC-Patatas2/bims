<?php
// login.php
session_start();

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check against database
        $stmt = $conn->prepare('SELECT id, username, password, full_name, role FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Log successful login
            $log_stmt = $conn->prepare("INSERT INTO audit_trail (user_id, action, ip_address) VALUES (?, 'Logged in', ?)");
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("is", $user['id'], $ip_address);
            $log_stmt->execute();
            $log_stmt->close();

            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
$conn->close();

// Fetch system title for login page
$system_title = 'Barangay Information Management System'; // Default title
$conn_settings = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn_settings->connect_error) {
    $result = $conn_settings->query("SELECT setting_value FROM system_settings WHERE setting_key = 'system_title'");
    if ($result && $result->num_rows > 0) {
        $system_title = $result->fetch_assoc()['setting_value'];
    }
    $conn_settings->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($system_title); ?></title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
    <style>
        .focus\:ring {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm p-8 bg-white rounded-lg shadow-xl">
        <div class="flex justify-center mb-6">
            <?php 
                $logo_to_display = 'lib/assets/default_logo.png'; // Default logo
                // Re-use existing $conn_settings if available, or create a new one for logo path
                $db_conn_logo = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if (!$db_conn_logo->connect_error) {
                    $logo_query_result = $db_conn_logo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'barangay_logo_path'");
                    if ($logo_query_result && $logo_query_result->num_rows > 0) {
                        $db_logo_path = $logo_query_result->fetch_assoc()['setting_value'];
                        // Check if the path from DB exists. Prepend __DIR__ if it's a relative path from root.
                        // Assuming 'img/logo.png' is relative to the project root (where login.php is)
                        $potential_logo_path = __DIR__ . '/' . $db_logo_path;
                        if (file_exists($potential_logo_path)) {
                            $logo_to_display = $db_logo_path; // Use the relative path for the <img> src
                        } else {
                            // Fallback or error logging if needed
                            // e.g., error_log("Logo not found at: " . $potential_logo_path);
                        }
                    }
                    $db_conn_logo->close();
                }
            ?>
            <img src="<?php echo htmlspecialchars($logo_to_display); ?>" alt="Barangay Logo" class="h-20 w-auto">
        </div>
        <h2 class="text-2xl font-bold mb-1 text-center text-gray-700"><?php echo htmlspecialchars($system_title); ?></h2>
        <p class="text-sm text-gray-500 text-center mb-6">Please sign in to continue</p>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="username">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 pl-10 text-gray-700 leading-tight focus:outline-none focus:ring" type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 pl-10 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring" type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <button class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out" type="submit">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </div>
        </form>
        <p class="text-center text-gray-500 text-xs mt-6">
            &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($system_title); ?>. All rights reserved.
        </p>
    </div>
</body>
</html>
