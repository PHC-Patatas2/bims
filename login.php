<?php
session_start();
require_once 'config.php';

// Fetch system title and logo path
$system_title = 'Barangay Information Management System';
$logo_path = 'img/logo.png';
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('system_title', 'barangay_logo_path')");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'system_title') $system_title = $row['setting_value'];
        if ($row['setting_key'] === 'barangay_logo_path') $logo_path = $row['setting_value'];
    }
} catch (Exception $e) {}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = 'secretary'; // or fetch from DB if you have roles
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid credentials.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
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
        .fade-out { transition: opacity 0.5s; opacity: 0; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 flex flex-col items-center">
        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Barangay Logo" class="w-24 h-24 mb-4 rounded-full border-2 border-blue-500 object-cover">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 text-center"><?php echo htmlspecialchars($system_title); ?></h1>
        <?php if ($error): ?>
            <div id="errorBox" class="w-full bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-center animate-pulse">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <script>
                setTimeout(function() {
                    var box = document.getElementById('errorBox');
                    if (box) { box.classList.add('fade-out'); setTimeout(() => box.style.display = 'none', 500); }
                }, 3000);
            </script>
        <?php endif; ?>
        <form method="post" class="w-full flex flex-col gap-4">
            <div>
                <label for="username" class="block text-gray-700 font-semibold mb-1">Username</label>
                <input type="text" id="username" name="username" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required autofocus autocomplete="username">
            </div>
            <div>
                <label for="password" class="block text-gray-700 font-semibold mb-1">Password</label>
                <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required autocomplete="current-password">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded transition">Login</button>
        </form>
    </div>
</body>
</html>