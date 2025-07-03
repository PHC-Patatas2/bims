<?php
// error_page.php
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Error - Resident Information and Certification Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 10px 20px -5px #0002; }
        .stat-card .icon { transition: transform 0.3s; }
        .stat-card:hover .icon { transform: scale(1.1); }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">
    <div class="w-full max-w-xl bg-white rounded-xl shadow-lg p-8 flex flex-col items-center gap-4 border border-gray-200 mt-24">
        <i class="fas fa-exclamation-triangle text-7xl text-red-400 mb-4"></i>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">System Error</h1>
        <p class="text-lg text-gray-600 mb-2 text-center">Sorry, we are unable to connect to the database or complete your request at this time.<br><br>Please try again later or contact your system administrator.</p>
        <a href="login.php" class="mt-4 text-blue-600 hover:underline">Back to Login</a>
    </div>
</body>
</html>
