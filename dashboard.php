<?php
// dashboard.php
session_start();
// Check if user is logged in and has the correct role (e.g., admin or secretary)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'secretary'])) {
    header('Location: login.php'); // Redirect to login if not authorized
    exit();
}

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Fetch stats - using new schema field names
$total_residents = $conn->query('SELECT COUNT(*) as total FROM individuals')->fetch_assoc()['total'] ?? 0;
$total_male = $conn->query("SELECT COUNT(*) as total FROM individuals WHERE gender = 'Male'")->fetch_assoc()['total'] ?? 0;
$total_female = $conn->query("SELECT COUNT(*) as total FROM individuals WHERE gender = 'Female'")->fetch_assoc()['total'] ?? 0;
$total_voters = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_voter = 1')->fetch_assoc()['total'] ?? 0;
$total_seniors = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_senior_citizen = 1')->fetch_assoc()['total'] ?? 0;
$total_pwd = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_pwd = 1')->fetch_assoc()['total'] ?? 0;
$total_4ps = $conn->query('SELECT COUNT(*) as total FROM individuals WHERE is_4ps_member = 1')->fetch_assoc()['total'] ?? 0;
$total_families = $conn->query('SELECT COUNT(*) as total FROM families')->fetch_assoc()['total'] ?? 0;

// Fetch system title and barangay name for the dashboard
$system_title = 'Barangay Information Management System'; // Default
$barangay_name = 'Barangay'; // Default
$result_settings = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('system_title', 'barangay_name')");
if ($result_settings) {
    while ($row = $result_settings->fetch_assoc()) {
        if ($row['setting_key'] == 'system_title') {
            $system_title = $row['setting_value'];
        }
        if ($row['setting_key'] == 'barangay_name') {
            $barangay_name = $row['setting_value'];
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($system_title); ?></title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
    <style>
        .stat-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .stat-card .icon {
            transition: transform 0.3s ease;
        }
        .stat-card:hover .icon {
            transform: scale(1.1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <?php include 'navbar.php'; // Ensure navbar uses $_SESSION['role'] for conditional display ?>
    <?php include 'sidepanel.php'; // Ensure sidepanel uses $_SESSION['role'] ?>

    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out ml-0 md:ml-64 p-4 md:p-8 mt-16">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Welcome to the <?php echo htmlspecialchars($barangay_name); ?> Dashboard</h1>
            <p class="text-gray-600">Overview of your barangay's information.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Residents -->
            <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-between min-h-[180px] overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-4xl font-bold mb-1"><?php echo $total_residents; ?></div>
                        <div class="mb-4 text-lg">Total Residents</div>
                    </div>
                    <i class="fas fa-users icon text-6xl opacity-30"></i>
                </div>
                <a href="individuals.php" class="mt-auto bg-blue-700 hover:bg-blue-800 px-4 py-2 rounded-lg text-sm text-center font-medium transition-colors">
                    View Details <i class="fas fa-arrow-circle-right ml-1"></i>
                </a>
            </div>

            <!-- Families -->
            <div class="stat-card bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-between min-h-[180px] overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-4xl font-bold mb-1"><?php echo $total_families; ?></div>
                        <div class="mb-4 text-lg">Total Families</div>
                    </div>
                    <i class="fas fa-people-roof icon text-6xl opacity-30"></i>
                </div>
                <a href="families.php" class="mt-auto bg-green-700 hover:bg-green-800 px-4 py-2 rounded-lg text-sm text-center font-medium transition-colors">
                    View Details <i class="fas fa-arrow-circle-right ml-1"></i>
                </a>
            </div>

            <!-- Male Residents -->
            <div class="stat-card bg-gradient-to-br from-sky-500 to-sky-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-between min-h-[180px] overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-4xl font-bold mb-1"><?php echo $total_male; ?></div>
                        <div class="mb-4 text-lg">Male Residents</div>
                    </div>
                    <i class="fas fa-mars icon text-6xl opacity-30"></i>
                </div>
                <a href="individuals.php?gender=Male" class="mt-auto bg-sky-700 hover:bg-sky-800 px-4 py-2 rounded-lg text-sm text-center font-medium transition-colors">
                    View Details <i class="fas fa-arrow-circle-right ml-1"></i>
                </a>
            </div>

            <!-- Female Residents -->
            <div class="stat-card bg-gradient-to-br from-pink-500 to-pink-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-between min-h-[180px] overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-4xl font-bold mb-1"><?php echo $total_female; ?></div>
                        <div class="mb-4 text-lg">Female Residents</div>
                    </div>
                    <i class="fas fa-venus icon text-6xl opacity-30"></i>
                </div>
                <a href="individuals.php?gender=Female" class="mt-auto bg-pink-700 hover:bg-pink-800 px-4 py-2 rounded-lg text-sm text-center font-medium transition-colors">
                    View Details <i class="fas fa-arrow-circle-right ml-1"></i>
                </a>
            </div>

            <!-- Senior Citizens -->
            <div class="stat-card bg-gradient-to-br from-yellow-400 to-yellow-500 text-gray-900 rounded-xl shadow-lg p-6 flex flex-col justify-between min-h-[180px] overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-4xl font-bold mb-1"><?php echo $total_seniors; ?></div>
                        <div class="mb-4 text-lg">Senior Citizens</div>
                    </div>
                    <i class="fas fa-person-cane icon text-6xl opacity-30"></i>
                </div>
                <a href="individuals.php?filter=senior_citizen" class="mt-auto bg-yellow-600 hover:bg-yellow-700 px-4 py-2 rounded-lg text-sm text-center font-medium transition-colors">
                    View Details <i class="fas fa-arrow-circle-right ml-1"></i>
                </a>
            </div>

            <!-- PWD -->
            <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-between min-h-[180px] overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-4xl font-bold mb-1"><?php echo $total_pwd; ?></div>
                        <div class="mb-4 text-lg">PWDs</div>
                    </div>
                    <i class="fas fa-wheelchair icon text-6xl opacity-30"></i>
                </div>
                <a href="individuals.php?filter=pwd" class="mt-auto bg-purple-700 hover:bg-purple-800 px-4 py-2 rounded-lg text-sm text-center font-medium transition-colors">
                    View Details <i class="fas fa-arrow-circle-right ml-1"></i>
                </a>
            </div>

            <!-- Voters -->
            <div class="stat-card bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-between min-h-[180px] overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-4xl font-bold mb-1"><?php echo $total_voters; ?></div>
                        <div class="mb-4 text-lg">Registered Voters</div>
                    </div>
                    <i class="fas fa-vote-yea icon text-6xl opacity-30"></i>
                </div>
                <a href="individuals.php?filter=voter" class="mt-auto bg-indigo-700 hover:bg-indigo-800 px-4 py-2 rounded-lg text-sm text-center font-medium transition-colors">
                    View Details <i class="fas fa-arrow-circle-right ml-1"></i>
                </a>
            </div>

            <!-- 4Ps Members -->
            <div class="stat-card bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-between min-h-[180px] overflow-hidden">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-4xl font-bold mb-1"><?php echo $total_4ps; ?></div>
                        <div class="mb-4 text-lg">4Ps Members</div>
                    </div>
                    <i class="fas fa-hand-holding-heart icon text-6xl opacity-30"></i>
                </div>
                <a href="individuals.php?filter=4ps_member" class="mt-auto bg-orange-700 hover:bg-orange-800 px-4 py-2 rounded-lg text-sm text-center font-medium transition-colors">
                    View Details <i class="fas fa-arrow-circle-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Placeholder for more dashboard content like charts or recent activities -->
        <div class="mt-10 bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Recent Activity</h2>
            <p class="text-gray-600">Activity log will be displayed here. (Coming Soon)</p>
            <!-- Example: Fetch and display last 5 entries from audit_trail -->
        </div>

    </div>

    <script>
        // Script for sidepanel toggle if it's not already in navbar.php or a global script
        const menuButton = document.getElementById('menuButton');
        const sidePanel = document.getElementById('sidePanel');
        const mainContent = document.getElementById('mainContent');

        if (menuButton && sidePanel && mainContent) {
            menuButton.addEventListener('click', () => {
                sidePanel.classList.toggle('-ml-64');
                mainContent.classList.toggle('md:ml-64');
                mainContent.classList.toggle('ml-0');
            });
        }
    </script>
</body>
</html>
