<?php
// login.php
session_start();

// Only one user: secretary
$secretary_username = 'secretary';
$secretary_password = 'password123'; // Change this to your preferred password

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === $secretary_username && $password === $secretary_password) {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = $secretary_username;
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay Information Management System</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm p-6 bg-white rounded shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
        <?php if (!empty($error)): ?>
            <div class="text-red-500 text-center mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 mb-2" for="username">Username</label>
                <input class="w-full px-3 py-2 border rounded focus:outline-none focus:ring" type="text" id="username" name="username" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 mb-2" for="password">Password</label>
                <input class="w-full px-3 py-2 border rounded focus:outline-none focus:ring" type="password" id="password" name="password" required>
            </div>
            <button class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600 transition" type="submit">Login</button>
        </form>
    </div>
</body>
</html>
