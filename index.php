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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Information Management System - Login</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm p-6 bg-white rounded shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
        <form action="login.php" method="POST" id="loginForm">
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
        <div id="errorMsg" class="text-red-500 text-center mt-4 hidden"></div>
    </div>
    <script>
        // Optional: Simple client-side validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var username = document.getElementById('username').value.trim();
            var password = document.getElementById('password').value.trim();
            if (!username || !password) {
                e.preventDefault();
                document.getElementById('errorMsg').textContent = 'Please enter both username and password.';
                document.getElementById('errorMsg').classList.remove('hidden');
            }
        });
    </script>
</body>
</html>
