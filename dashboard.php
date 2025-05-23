<?php
// dashboard.php
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
// Fetch stats
$total_residents = $conn->query('SELECT COUNT(*) as total FROM individuals')->fetch_assoc()['total'];
$total_male = $conn->query("SELECT COUNT(*) as total FROM individuals WHERE gender = 'Male'")->fetch_assoc()['total'];
$total_female = $conn->query("SELECT COUNT(*) as total FROM individuals WHERE gender = 'Female'")->fetch_assoc()['total'];
$total_voters = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE voter_status = 1')->fetch_assoc()['total'];
$total_seniors = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE age >= 60')->fetch_assoc()['total'];
$total_pwd = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_pwd = 1')->fetch_assoc()['total'];
$total_4ps = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_4ps = 1')->fetch_assoc()['total'];
$total_families = $conn->query('SELECT COUNT(*) as total FROM families')->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Barangay Information Management System</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <?php include 'navbar.php'; ?>
    <div id="mainContent" class="transition-all duration-200 ml-0">
        <div class="flex items-center justify-center min-h-screen" style="padding-top:4.5rem">
            <div class="max-w-6xl w-full px-4 py-10 grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Residents -->
                <div class="relative bg-blue-500 text-white rounded-xl shadow p-6 flex flex-col justify-between min-h-[180px] overflow-hidden transition-all duration-200 hover:outline hover:outline-4 hover:outline-blue-700 hover:shadow-lg">
                    <i class="fa-solid fa-users absolute right-4 top-4 text-[5rem] opacity-30 pointer-events-none"></i>
                    <div>
                        <div class="text-4xl font-bold mb-1 z-10 relative"><?php echo $total_residents; ?></div>
                        <div class="mb-4 text-lg z-10 relative">Residents</div>
                    </div>
                    <a href="individuals.php" class="absolute left-0 right-0 bottom-0 mb-0 bg-blue-600 px-3 py-2 rounded-b-xl text-white text-sm text-center z-10">View Details</a>
                </div>
                <!-- Families -->
                <div class="relative bg-green-500 text-white rounded-xl shadow p-6 flex flex-col justify-between min-h-[180px] overflow-hidden transition-all duration-200 hover:outline hover:outline-4 hover:outline-green-700 hover:shadow-lg">
                    <i class="fa-solid fa-people-roof absolute right-4 top-4 text-[5rem] opacity-30 pointer-events-none"></i>
                    <div>
                        <div class="text-4xl font-bold mb-1 z-10 relative"><?php echo $total_families; ?></div>
                        <div class="mb-4 text-lg z-10 relative">Families</div>
                    </div>
                    <a href="families.php" class="absolute left-0 right-0 bottom-0 mb-0 bg-green-600 px-3 py-2 rounded-b-xl text-white text-sm text-center z-10">View Details</a>
                </div>
                <!-- Female -->
                <div class="relative bg-pink-500 text-white rounded-xl shadow p-6 flex flex-col justify-between min-h-[180px] overflow-hidden transition-all duration-200 hover:outline hover:outline-4 hover:outline-pink-700 hover:shadow-lg">
                    <i class="fa-solid fa-venus absolute right-4 top-4 text-[5rem] opacity-30 pointer-events-none"></i>
                    <div>
                        <div class="text-4xl font-bold mb-1 z-10 relative"><?php echo $total_female; ?></div>
                        <div class="mb-4 text-lg z-10 relative">Female</div>
                    </div>
                    <a href="individuals.php" class="absolute left-0 right-0 bottom-0 mb-0 bg-pink-600 px-3 py-2 rounded-b-xl text-white text-sm text-center z-10">View Details</a>
                </div>
                <!-- Male -->
                <div class="relative bg-blue-700 text-white rounded-xl shadow p-6 flex flex-col justify-between min-h-[180px] overflow-hidden transition-all duration-200 hover:outline hover:outline-4 hover:outline-blue-900 hover:shadow-lg">
                    <i class="fa-solid fa-mars absolute right-4 top-4 text-[5rem] opacity-30 pointer-events-none"></i>
                    <div>
                        <div class="text-4xl font-bold mb-1 z-10 relative"><?php echo $total_male; ?></div>
                        <div class="mb-4 text-lg z-10 relative">Male</div>
                    </div>
                    <a href="individuals.php" class="absolute left-0 right-0 bottom-0 mb-0 bg-blue-800 px-3 py-2 rounded-b-xl text-white text-sm text-center z-10">View Details</a>
                </div>
                <!-- Senior Citizens -->
                <div class="relative bg-yellow-400 text-gray-900 rounded-xl shadow p-6 flex flex-col justify-between min-h-[180px] overflow-hidden transition-all duration-200 hover:outline hover:outline-4 hover:outline-yellow-600 hover:shadow-lg">
                    <i class="fa-solid fa-person-cane absolute right-4 top-4 text-[5rem] opacity-30 pointer-events-none"></i>
                    <div>
                        <div class="text-4xl font-bold mb-1 z-10 relative"><?php echo $total_seniors; ?></div>
                        <div class="mb-4 text-lg z-10 relative">Senior Citizens</div>
                    </div>
                    <a href="individuals.php" class="absolute left-0 right-0 bottom-0 mb-0 bg-yellow-500 px-3 py-2 rounded-b-xl text-gray-900 text-sm text-center z-10">View Details</a>
                </div>
                <!-- PWD -->
                <div class="relative bg-purple-600 text-white rounded-xl shadow p-6 flex flex-col justify-between min-h-[180px] overflow-hidden transition-all duration-200 hover:outline hover:outline-4 hover:outline-purple-900 hover:shadow-lg">
                    <i class="fa-solid fa-wheelchair absolute right-4 top-4 text-[5rem] opacity-30 pointer-events-none"></i>
                    <div>
                        <div class="text-4xl font-bold mb-1 z-10 relative"><?php echo $total_pwd; ?></div>
                        <div class="mb-4 text-lg z-10 relative">PWD</div>
                    </div>
                    <a href="individuals.php" class="absolute left-0 right-0 bottom-0 mb-0 bg-purple-700 px-3 py-2 rounded-b-xl text-white text-sm text-center z-10">View Details</a>
                </div>
                <!-- Voters -->
                <div class="relative bg-indigo-500 text-white rounded-xl shadow p-6 flex flex-col justify-between min-h-[180px] overflow-hidden transition-all duration-200 hover:outline hover:outline-4 hover:outline-indigo-700 hover:shadow-lg">
                    <i class="fa-solid fa-vote-yea absolute right-4 top-4 text-[5rem] opacity-30 pointer-events-none"></i>
                    <div>
                        <div class="text-4xl font-bold mb-1 z-10 relative"><?php echo $total_voters; ?></div>
                        <div class="mb-4 text-lg z-10 relative">Voters</div>
                    </div>
                    <a href="individuals.php" class="absolute left-0 right-0 bottom-0 mb-0 bg-indigo-600 px-3 py-2 rounded-b-xl text-white text-sm text-center z-10">View Details</a>
                </div>
                <!-- 4Ps Members -->
                <div class="relative bg-orange-500 text-white rounded-xl shadow p-6 flex flex-col justify-between min-h-[180px] overflow-hidden transition-all duration-200 hover:outline hover:outline-4 hover:outline-orange-700 hover:shadow-lg">
                    <i class="fa-solid fa-hand-holding-heart absolute right-4 top-4 text-[5rem] opacity-30 pointer-events-none"></i>
                    <div>
                        <div class="text-4xl font-bold mb-1 z-10 relative"><?php echo $total_4ps; ?></div>
                        <div class="mb-4 text-lg z-10 relative">4Ps Members</div>
                    </div>
                    <a href="individuals.php" class="absolute left-0 right-0 bottom-0 mb-0 bg-orange-600 px-3 py-2 rounded-b-xl text-white text-sm text-center z-10">View Details</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
