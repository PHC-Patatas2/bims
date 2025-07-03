<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare('SELECT username, first_name, last_name, email FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($username, $first_name, $last_name, $email);
$stmt->fetch();
$stmt->close();

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $new_first_name = trim($_POST['first_name'] ?? '');
    $new_last_name = trim($_POST['last_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    if ($new_username && $new_first_name && $new_last_name && $new_email) {
        $stmt = $conn->prepare('UPDATE users SET username=?, first_name=?, last_name=?, email=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('ssssi', $new_username, $new_first_name, $new_last_name, $new_email, $user_id);
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            $username = $new_username;
            $first_name = $new_first_name;
            $last_name = $new_last_name;
            $email = $new_email;
            $_SESSION['username'] = $username;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
        } else {
            $error = 'Failed to update profile.';
        }
        $stmt->close();
    } else {
        $error = 'All fields are required.';
    }
}

$system_title = 'Resident Information and Certification Management System';
$title_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='system_title' LIMIT 1");
if ($title_result && $title_row = $title_result->fetch_assoc()) {
    if (!empty($title_row['setting_value'])) {
        $system_title = $title_row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        .fade-out { transition: opacity 0.5s; opacity: 0; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-lg bg-white rounded-xl shadow-lg p-8 flex flex-col items-center mt-10">
        <h1 class="text-2xl font-bold text-blue-700 mb-6 text-center">My Profile</h1>
        <?php if ($success): ?>
            <div id="successBox" class="w-full bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-center animate-pulse"><?php echo htmlspecialchars($success); ?></div>
            <script>
                setTimeout(function() {
                    var box = document.getElementById('successBox');
                    if (box) { box.classList.add('fade-out'); setTimeout(() => box.style.display = 'none', 500); }
                }, 3000);
            </script>
        <?php endif; ?>
        <?php if ($error): ?>
            <div id="errorBox" class="w-full bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-center animate-pulse"><?php echo htmlspecialchars($error); ?></div>
            <script>
                setTimeout(function() {
                    var box = document.getElementById('errorBox');
                    if (box) { box.classList.add('fade-out'); setTimeout(() => box.style.display = 'none', 500); }
                }, 3000);
            </script>
        <?php endif; ?>
        <form method="post" class="w-full flex flex-col gap-4">
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Username</label>
                <div class="bg-gray-100 rounded px-3 py-2 cursor-not-allowed opacity-70"><?php echo htmlspecialchars($username); ?></div>
            </div>
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="font-semibold text-gray-700">First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" class="bg-gray-100 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required />
                </div>
                <div class="flex-1">
                    <label class="font-semibold text-gray-700">Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" class="bg-gray-100 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required />
                </div>
            </div>
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="bg-gray-100 border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required />
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded transition mt-4">Save</button>
            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>" />
        </form>
        <a href="dashboard.php" class="mt-8 text-blue-600 hover:underline">&larr; Back to Dashboard</a>
    </div>
</body>
</html>
