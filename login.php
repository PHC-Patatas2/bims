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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" integrity="sha512-u3fPA7V/q_dR0APDDUuOzvKFBBHlAwKRj5lHZRt1gs3osuTRswblYIWkxVAqkSgM3/CaHXMwEcOuc_2Nqbuhmw==" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
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
                <input type="text" id="username" name="username" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required autofocus autocomplete="username" placeholder="Username">
            </div>
            <div>
                <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required autocomplete="current-password" placeholder="Password">
            </div>
            <div class="flex items-center mt-2">
                <input id="show-password" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="show-password" class="ml-2 block text-sm text-gray-900">Show Password</label>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded transition mt-4">Login</button>
        </form>
    </div>
    <script>
        const showPasswordCheckbox = document.getElementById('show-password');
        const passwordInput = document.getElementById('password');

        showPasswordCheckbox.addEventListener('change', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        });
    </script>
</body>
</html>