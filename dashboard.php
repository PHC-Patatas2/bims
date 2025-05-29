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
$user_full_name = '';
$stmt = $conn->prepare('SELECT full_name FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($user_full_name);
$stmt->fetch();
$stmt->close();

// Fetch system title from system_settings table
$system_title = 'Barangay Information Management System'; // default fallback
$title_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='system_title' LIMIT 1");
if ($title_result && $title_row = $title_result->fetch_assoc()) {
    if (!empty($title_row['setting_value'])) {
        $system_title = $title_row['setting_value'];
    }
}

// Fetch dashboard stats from database
$stats = [
    'total_residents' => null,
    'total_families' => null,
    'total_male' => null,
    'total_female' => null,
    'total_seniors' => null,
    'total_pwd' => null,
    'total_voters' => null,
    'total_4ps' => null,
    'total_solo_parents' => null,
    'total_pregnant' => null,
    'total_newborns' => null,
    'total_minors' => null,
];

// Updated queries for each stat as per new requirements
$queries = [
    // All individuals
    'total_residents' => "SELECT COUNT(*) FROM individuals",
    // Families: group by family_id, count unique families
    'total_families' => "SELECT COUNT(DISTINCT family_id) FROM individuals WHERE family_id IS NOT NULL",
    // Male
    'total_male' => "SELECT COUNT(*) FROM individuals WHERE gender = 'male'",
    // Female
    'total_female' => "SELECT COUNT(*) FROM individuals WHERE gender = 'female'",
    // Registered Voters
    'total_voters' => "SELECT COUNT(*) FROM individuals WHERE is_voter = 1",
    // 4Ps Members
    'total_4ps' => "SELECT COUNT(*) FROM individuals WHERE is_4ps = 1",
    // Senior Citizens (age >= 60)
    'total_seniors' => "SELECT COUNT(*) FROM individuals WHERE birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60",
    // PWDs
    'total_pwd' => "SELECT COUNT(*) FROM individuals WHERE is_pwd = 1",
    // Solo Parents
    'total_solo_parents' => "SELECT COUNT(*) FROM individuals WHERE is_solo_parent = 1",
    // Pregnant Women
    'total_pregnant' => "SELECT COUNT(*) FROM individuals WHERE is_pregnant = 1 AND gender = 'female' AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 10 AND 50",
    // Newborns (age < 28 days)
    'total_newborns' => "SELECT COUNT(*) FROM individuals WHERE birthdate IS NOT NULL AND DATEDIFF(CURDATE(), birthdate) >= 0 AND DATEDIFF(CURDATE(), birthdate) < 28",
    // Minors (age < 18)
    'total_minors' => "SELECT COUNT(*) FROM individuals WHERE birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 18",
];
foreach ($queries as $key => $sql) {
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_row()) {
        $stats[$key] = (int)$row[0];
    }
}

// Use the actual ENUM values for the 3 valid puroks
$purok_list = [
    'Purok 1 (pulongtingga)',
    'Purok 2 (looban)',
    'Purok 3 (proper)'
];
$purok_stats = [];
$purok_in = "'" . implode("','", array_map(function($p) use ($conn) { return $conn->real_escape_string($p); }, $purok_list)) . "'";
$purok_query = "SELECT purok, COUNT(*) as total FROM individuals WHERE purok IN ($purok_in) GROUP BY purok ORDER BY FIELD(purok, $purok_in)";
$purok_result = $conn->query($purok_query);
if ($purok_result) {
    while ($row = $purok_result->fetch_assoc()) {
        $purok_stats[$row['purok']] = (int)$row['total'];
    }
}

// Automatic Age Updates: Age-based queries are already dynamic using birthdate and CURDATE()
// Data Integrity for Boolean Fields: Ensure all boolean fields are set to 0 if NULL
$conn->query("UPDATE individuals SET is_pwd = 0 WHERE is_pwd IS NULL");
$conn->query("UPDATE individuals SET is_4ps = 0 WHERE is_4ps IS NULL");
$conn->query("UPDATE individuals SET is_voter = 0 WHERE is_voter IS NULL");
$conn->query("UPDATE individuals SET is_solo_parent = 0 WHERE is_solo_parent IS NULL");
$conn->query("UPDATE individuals SET is_pregnant = 0 WHERE is_pregnant IS NULL");

// For each card, show count if available, else show two lines
function stat_card_count($value) {
    if ($value === null) {
        return '<div class="h-4 bg-white/30 rounded mb-1 w-2/3 animate-pulse"></div><div class="h-4 bg-white/20 rounded w-1/2 animate-pulse"></div>';
    } else {
        return '<div class="text-3xl font-bold mb-0.5">' . $value . '</div>';
    }
}
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
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 10px 20px -5px #0002; }
        .stat-card .icon { transition: transform 0.3s; }
        .stat-card:hover .icon { transform: scale(1.1); }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        .calendar-day-today { background: #2563eb; color: #fff; border-radius: 50%; }
        .sidebar-border { border-right: 1px solid #e5e7eb; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Sidepanel -->
    <div id="sidepanel" class="fixed top-0 left-0 h-full w-64 shadow-lg z-40 transform -translate-x-full transition-transform duration-300 ease-in-out sidebar-border" style="background-color: #454545;">
        <div class="flex flex-col items-center justify-center min-h-[90px] px-4 pt-3 pb-3 relative" style="border-bottom: 4px solid #FFD700;">
            <button id="closeSidepanel" class="absolute right-2 top-2 text-white hover:text-blue-400 focus:outline-none text-2xl md:hidden" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
            <?php
            // Fetch barangay logo from 'system_settings' table (correct column: setting_value)
            $barangay_logo = 'img/logo.png'; // default
            $logo_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='barangay_logo_path' LIMIT 1");
            if ($logo_result && $logo_row = $logo_result->fetch_assoc()) {
                if (!empty($logo_row['setting_value'])) {
                    $barangay_logo = $logo_row['setting_value'];
                }
            }
            ?>
            <img src="<?php echo htmlspecialchars($barangay_logo); ?>" alt="Barangay Logo" class="w-28 h-28 object-cover rounded-full mb-1 border-2 border-white bg-white p-1" style="aspect-ratio:1/1;" onerror="this.onerror=null;this.src='img/logo.png';">
        </div>
        <nav class="flex flex-col p-4 gap-2">
            <?php
            $pages = [
                ['dashboard.php', 'fas fa-tachometer-alt', 'Dashboard'],
                ['individuals.php', 'fas fa-users', 'Residents'],
                ['families.php', 'fas fa-house-user', 'Families'],
                ['reports.php', 'fas fa-chart-bar', 'Reports'],
                ['certificate.php', 'fas fa-file-alt', 'Certificates'],
                ['business_permit.php', 'fas fa-briefcase', 'Business Permits'],
                ['blotter_records.php', 'fas fa-book', 'Blotter'],
                ['system_settings.php', 'fas fa-cogs', 'System Settings'],
            ];
            $current = basename($_SERVER['PHP_SELF']);
            foreach ($pages as $page) {
                $isActive = $current === $page[0];
                // Active: blue fill, no border. Inactive: no fill, just white text.
                $activeClass = $isActive 
                    ? 'bg-blue-600 text-white font-bold shadow-md' 
                    : 'text-white';
                // Add blue background fill on hover for inactive options, no border
                $hoverClass = 'hover:bg-blue-500 hover:text-white';
                echo '<a href="' . $page[0] . '" class="py-2 px-3 rounded-lg flex items-center gap-2 ' . $activeClass . ' ' . $hoverClass . '"><i class="' . $page[1] . '"></i> ' . $page[2] . '</a>';
            }
            ?>
        </nav>
    </div>
    <!-- Overlay -->
    <div id="sidepanelOverlay" class="fixed inset-0 bg-black bg-opacity-30 z-30 hidden"></div>
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-30 bg-white shadow flex items-center justify-between h-16 px-4 md:px-8">
        <div class="flex items-center gap-2">            <button id="menuBtn" class="h-8 w-8 mr-2 flex items-center justify-center text-blue-700 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <span class="font-bold text-lg text-blue-700"><?php echo htmlspecialchars($system_title); ?></span>
        </div>
        <div class="relative flex items-center gap-2">
            <span class="hidden sm:inline text-gray-700 font-medium">Welcome, <?php echo htmlspecialchars($user_full_name); ?></span>
            <button id="userDropdownBtn" class="focus:outline-none flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-100">
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="userDropdownMenu" class="dropdown-menu mt-2">
                <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100 text-gray-700"><i class="fas fa-user mr-2"></i>Manage Profile</a>
                <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 text-red-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-4">
                <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_residents']); ?>
                            <div class="mb-2 text-base">Total Residents</div>
                        </div>
                        <i class="fas fa-users icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-green-500 to-green-700 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_families']); ?>
                            <div class="mb-2 text-base">Total Families</div>
                        </div>
                        <i class="fas fa-people-roof icon text-6xl opacity-30"></i>
                    </div>
                    <a href="families.php" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-cyan-600 to-cyan-800 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_male']); ?>
                            <div class="mb-2 text-base">Male Residents</div>
                        </div>
                        <i class="fas fa-mars icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?gender=male" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-pink-500 to-pink-700 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_female']); ?>
                            <div class="mb-2 text-base">Female Residents</div>
                        </div>
                        <i class="fas fa-venus icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?gender=female" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-indigo-500 to-indigo-700 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_voters']); ?>
                            <div class="mb-2 text-base">Registered Voters</div>
                        </div>
                        <i class="fas fa-vote-yea icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?voter=1" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_4ps']); ?>
                            <div class="mb-2 text-base">4Ps Members</div>
                        </div>
                        <i class="fas fa-hand-holding-heart icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?fourps=1" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-yellow-500 to-yellow-700 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_seniors']); ?>
                            <div class="mb-2 text-base text-white">Senior Citizens</div>
                        </div>
                        <i class="fas fa-person-cane icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?senior=1" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-700 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_pwd']); ?>
                            <div class="mb-2 text-base">PWDs</div>
                        </div>
                        <i class="fas fa-wheelchair icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?pwd=1" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-red-600 to-red-800 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_solo_parents']); ?>
                            <div class="mb-2 text-base">Solo Parents</div>
                        </div>
                        <i class="fas fa-user-shield icon text-6xl opacity-30"></i>
                    </div>
                    <a href="#" class="mt-4 text-red-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-fuchsia-600 to-fuchsia-800 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_pregnant']); ?>
                            <div class="mb-2 text-base">Pregnant Women</div>
                        </div>
                        <i class="fas fa-baby-carriage icon text-6xl opacity-30"></i>
                    </div>
                    <a href="#" class="mt-4 text-pink-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-lime-600 to-lime-800 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_newborns']); ?>
                            <div class="mb-2 text-base">Newborns</div>
                        </div>
                        <i class="fas fa-baby icon text-6xl opacity-30"></i>
                    </div>
                    <a href="#" class="mt-4 text-yellow-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-emerald-600 to-emerald-800 text-white rounded-xl shadow-lg p-3 m-1 flex flex-col justify-between min-h-[110px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <?php echo stat_card_count($stats['total_minors']); ?>
                            <div class="mb-2 text-base">Minors</div>
                        </div>
                        <i class="fas fa-child icon text-6xl opacity-30"></i>
                    </div>
                    <a href="#" class="mt-4 text-emerald-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <!-- Two columns/containers row: merged left (c1+c2) and right (c3) -->
            <div class="w-full mb-6 flex flex-row gap-8">
                <div class="bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl shadow p-4 basis-0 grow-[2] min-h-[180px] flex flex-col border-2 border-blue-300 transition relative overflow-hidden">
                    <div class="absolute right-4 top-4 opacity-10 text-blue-400 text-7xl pointer-events-none select-none">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h2 class="text-lg font-semibold mb-4 text-blue-700 flex items-center gap-2 z-10 relative">
                        <i class="fas fa-chart-bar text-blue-500"></i>
                        Number of Individuals per Purok
                    </h2>
                    <div class="flex flex-col gap-3 text-base z-10 relative">
                        <?php foreach ($purok_list as $purok): ?>
                            <?php 
                                $count = isset($purok_stats[$purok]) ? $purok_stats[$purok] : '-';
                            ?>
                            <div class="flex justify-between items-center bg-white/80 rounded-lg px-4 py-3 shadow-sm border-l-8 <?php
                                if (strpos($purok, '1') !== false) echo 'border-blue-400';
                                elseif (strpos($purok, '2') !== false) echo 'border-green-400';
                                else echo 'border-yellow-400';
                            ?> transition">
                                <span class="flex items-center gap-2 text-gray-700 font-semibold text-lg">
                                    <i class="fas fa-map-pin text-blue-500"></i>
                                    <?php echo ucwords(strtolower(htmlspecialchars($purok))); ?>
                                </span>
                                <span class="font-bold text-2xl text-blue-700 bg-blue-100 px-5 py-1.5 rounded shadow">
                                    <?php echo $count; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div> <!-- left: purok stats -->
                <div class="bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl shadow p-4 basis-0 grow-[2] min-h-[180px] flex flex-col border-2 border-blue-300 transition relative overflow-hidden">
                    <div class="absolute right-4 top-4 opacity-10 text-yellow-400 text-7xl pointer-events-none select-none">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-500"></i>
                        Quick Actions
                    </h2>
                    <div class="flex flex-row gap-3 mt-2 w-full">
    <div class="flex flex-col gap-3 flex-1">
        <a href="generate_pdf.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-red-300 focus:ring-4 focus:ring-red-400">
            <i class="fas fa-file-pdf text-xl"></i>
            Generate PDF Report
        </a>
        <a href="export_excel.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-green-300 focus:ring-4 focus:ring-green-400">
            <i class="fas fa-file-excel text-xl"></i>
            Export to Excel
        </a>
        <a href="add_resident.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-blue-300 focus:ring-4 focus:ring-blue-400">
            <i class="fas fa-user-plus text-xl"></i>
            Add New Resident
        </a>
        <a href="import_residents.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-yellow-300 focus:ring-4 focus:ring-yellow-400">
            <i class="fas fa-file-upload text-xl"></i>
            Import Residents (CSV/Excel)
        </a>
    </div>
    <div class="flex flex-col gap-3 flex-1">
        <a href="print_summary.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-indigo-300 focus:ring-4 focus:ring-indigo-400">
            <i class="fas fa-print text-xl"></i>
            Print Summary
        </a>
        <a href="backup_records.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-700 hover:bg-gray-800 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-gray-400 focus:ring-4 focus:ring-gray-500">
            <i class="fas fa-database text-xl"></i>
            Backup Records
        </a>
        <a href="add_family.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-teal-300 focus:ring-4 focus:ring-teal-400">
            <i class="fas fa-house-user text-xl"></i>
            Add New Family
        </a>
        <a href="generate_id.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-fuchsia-300 focus:ring-4 focus:ring-fuchsia-400">
            <i class="fas fa-id-card text-xl"></i>
            Generate Barangay ID
        </a>
    </div>
</div>
                </div> <!-- right: quick actions -->
            </div>
            <!-- End two columns/containers row -->
        </div>
    </div>
    <!-- Scripts -->
    <script>
        // Ensure DOM is loaded before attaching event listeners
        window.addEventListener('DOMContentLoaded', function() {
            // Dropdown menu
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdownMenu.classList.toggle('show');
                });
                document.addEventListener('click', function(e) {
                    if (!userDropdownMenu.contains(e.target) && !userDropdownBtn.contains(e.target)) {
                        userDropdownMenu.classList.remove('show');
                    }
                });
            }
            // Sidepanel open/close
            const menuBtn = document.getElementById('menuBtn');
            const sidepanel = document.getElementById('sidepanel');
            const sidepanelOverlay = document.getElementById('sidepanelOverlay');
            const closeSidepanel = document.getElementById('closeSidepanel');
            function openSidepanel() {
                sidepanel.classList.remove('-translate-x-full');
                sidepanel.classList.add('translate-x-0');
                sidepanelOverlay.classList.remove('hidden');
            }
            function closeSidepanelFn() {
                sidepanel.classList.add('-translate-x-full');
                sidepanel.classList.remove('translate-x-0');
                sidepanelOverlay.classList.add('hidden');
            }
            if (menuBtn && sidepanel && sidepanelOverlay && closeSidepanel) {
                menuBtn.addEventListener('click', openSidepanel);
                closeSidepanel.addEventListener('click', closeSidepanelFn);
                sidepanelOverlay.addEventListener('click', closeSidepanelFn);
            }
        });
    </script>
</body>
</html>
