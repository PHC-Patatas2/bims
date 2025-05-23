<?php
// reports.php
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
// Population by purok
$purok_stats = $conn->query('SELECT p.name, COUNT(i.id) as total FROM puroks p LEFT JOIN individuals i ON i.purok_id = p.id GROUP BY p.id');
// PWD count
$pwd_count = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_pwd = 1')->fetch_assoc()['total'];
// 4Ps count
$fourps_count = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_4ps = 1')->fetch_assoc()['total'];
// Gender count
$male = $conn->query("SELECT COUNT(*) as total FROM individuals WHERE gender = 'Male'")->fetch_assoc()['total'];
$female = $conn->query("SELECT COUNT(*) as total FROM individuals WHERE gender = 'Female'")->fetch_assoc()['total'];
// Age brackets
$children = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE age < 18')->fetch_assoc()['total'];
$adults = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE age >= 18 AND age < 60')->fetch_assoc()['total'];
$seniors = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE age >= 60')->fetch_assoc()['total'];
if (isset($_GET['dashboard'])) {
    $population = $conn->query('SELECT COUNT(*) as total FROM individuals')->fetch_assoc()['total'];
    $families = $conn->query('SELECT COUNT(*) as total FROM families')->fetch_assoc()['total'];
    $pwd = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_pwd = 1')->fetch_assoc()['total'];
    $fourps = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_4ps = 1')->fetch_assoc()['total'];
    header('Content-Type: application/json');
    echo json_encode([
        'population' => $population,
        'families' => $families,
        'pwd' => $pwd,
        'fourps' => $fourps
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BIMS</title>
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
