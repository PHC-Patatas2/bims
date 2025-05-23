<?php
// certificate.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'secretary') {
    header('Location: index.php');
    exit();
}

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid ID');

$stmt = $conn->prepare('SELECT i.*, p.name as purok_name, f.family_name FROM individuals i LEFT JOIN puroks p ON i.purok_id = p.id LEFT JOIN families f ON i.family_id = f.id WHERE i.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$ind = $result->fetch_assoc();
if (!$ind) die('Not found');
$stmt->close();

// Barangay logo (place your logo in the same folder and set the filename here)
$barangay_logo = 'barangay_logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - BIMS</title>
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
