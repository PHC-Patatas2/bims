<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'secretary') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frontend - BIMS</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'navbar.php'; ?>
    <div id="mainContent" class="transition-all duration-200 ml-0 flex items-center justify-center min-h-screen">
        <div class="text-center">
            <h1 class="text-3xl font-bold mb-2">Under Maintenance</h1>
            <p class="text-gray-600">This page is currently under maintenance. Please check back later.</p>
        </div>
    </div>
</body>
</html>
