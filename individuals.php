<?php
// individuals.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'secretary') {
    header('Location: index.php');
    exit();
}

$currentPage = basename($_SERVER['PHP_SELF']);
include 'navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individuals - BIMS</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'navbar.php'; ?>
    <div class="flex items-center justify-center min-h-screen" style="padding-top:4.5rem">
        <!-- Page content here -->
    </div>
</body>
</html>
