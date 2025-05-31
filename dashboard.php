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
    // Minors (age < 18, not a voter)
    'total_minors' => "SELECT COUNT(*) FROM individuals WHERE birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 18 AND is_voter = 0",
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
    <link rel="stylesheet" href="lib/assets/tabulator.min.css">
    <script src="lib/assets/all.min.js" defer></script>
    <script src="lib/assets/tabulator.min.js"></script>
    <style>
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 10px 20px -5px #0002; }
        .stat-card .icon { transition: transform 0.3s; }
        .stat-card:hover .icon { transform: scale(1.1); }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        .calendar-day-today { background: #2563eb; color: #fff; border-radius: 50%; }
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        /* --- MODAL STYLE CLASSES (Barangay-friendly) --- */
        .modal-classic {
            background: #fff;
            box-shadow: 0 4px 24px 0 #0001;
            border: 2px solid #2563eb33;
            border-radius: 1.1rem;
            color: #1e293b;
            font-family: inherit;
            backdrop-filter: none;
            animation: none;
        }
        .modal-flat {
            background: #f8fafc;
            box-shadow: 0 2px 8px 0 #0001;
            border: 1.5px solid #e5e7eb;
            border-radius: 1rem;
            color: #1e293b;
            font-family: inherit;
            backdrop-filter: none;
            animation: none;
        }
        /* Table style classes */
        .table-classic .tabulator-table {
            background: #fff;
            border-radius: 0.9rem;
            box-shadow: 0 2px 12px 0 #2563eb11;
            border: 1.5px solid #2563eb33;
        }
        .table-flat .tabulator-table {
            background: #f8fafc;
            border-radius: 0.7rem;
            box-shadow: 0 2px 8px 0 #0001;
            border: 1.5px solid #e5e7eb;
        }
        .table-classic .tabulator-header {
            background: #2563eb !important;
            color: #fff !important;
            border-bottom: 2px solid #2563eb55 !important;
            font-weight: bold;
            text-transform: none;
            border-radius: 0.9rem 0.9rem 0 0;
        }
        .table-flat .tabulator-header {
            background: #e0e7ef !important;
            color: #1e293b !important;
            border-bottom: 1.5px solid #cbd5e1 !important;
            font-weight: 500;
            border-radius: 0.7rem 0.7rem 0 0;
        }
        .table-classic .tabulator-row:nth-child(even) {
            background: #f1f5fa;
        }
        .table-flat .tabulator-row:nth-child(even) {
            background: #f3f4f6;
        }
        /* Hide default border for both */
        .table-classic .tabulator-cell, .table-flat .tabulator-cell {
            border-right: none !important;
        }
        /* Style switcher select */
        #modalStyleSwitcher {
            font-size: 0.95rem;
            border-radius: 0.5rem;
            padding: 0.2rem 0.7rem;
            background: #f1f5f9;
            color: #2563eb;
            border: 1.5px solid #60a5fa;
            margin-left: 1rem;
            outline: none;
            transition: border 0.2s;
        }
        #modalStyleSwitcher:focus {
            border-color: #2563eb;
        }
        /* --- END MODAL STYLE CLASSES --- */
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
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
                    <a href="individuals.php?filter_type=all_residents" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=members_with_family_id" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=male" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=female" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=voter" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=4ps" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=senior" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=pwd" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=solo_parent" class="mt-4 text-red-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=pregnant" class="mt-4 text-pink-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=newborn" class="mt-4 text-yellow-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
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
                    <a href="individuals.php?filter_type=minor" class="mt-4 text-emerald-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <!-- Two columns/containers row: merged left (c1+c2) and right (c3) -->
            <div class="w-full mb-6 flex flex-row gap-8">
                <!-- Number of Individuals per Purok (left) -->
                <div class="bg-blue-200/60 rounded-xl shadow p-4 basis-0 grow-[2] min-h-[180px] flex flex-col border border-blue-300 transition relative overflow-hidden">
                    <div class="absolute right-6 top-6 opacity-10 text-blue-400 text-8xl pointer-events-none select-none">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h2 class="text-xl font-bold mb-6 text-blue-900 flex items-center gap-3 z-10 relative tracking-wide">
                        <i class="fas fa-chart-bar text-blue-500 text-2xl"></i>
                        Number of Individuals per Purok
                    </h2>
                    <div class="flex flex-col gap-4 text-base z-10 relative flex-1">
                        <?php foreach ($purok_list as $i => $purok): ?>
                            <?php 
                                $count = isset($purok_stats[$purok]) ? $purok_stats[$purok] : '-';
                                // Assign a unique color
                                if ($i === 0) { // Purok 1
                                    $border = 'border-red-500';
                                    $bg = 'bg-red-100/80';
                                    $icon = 'text-red-600';
                                } elseif ($i === 1) { // Purok 2
                                    $border = 'border-green-500';
                                    $bg = 'bg-green-100/80';
                                    $icon = 'text-green-600';
                                } else { // Purok 3
                                    $border = 'border-yellow-500';
                                    $bg = 'bg-yellow-100/80';
                                    $icon = 'text-yellow-600';
                                }
                            ?>
                            <div class="flex justify-between items-center px-6 py-4 rounded-xl shadow border-l-8 <?php echo $border; ?> <?php echo $bg; ?> hover:scale-[1.025] hover:shadow-md transition-transform duration-200">
                                <span class="flex items-center gap-3 text-blue-900 font-semibold text-lg">
                                    <i class="fas fa-map-pin <?php echo $icon; ?> text-xl"></i>
                                    <?php echo ucwords(strtolower(htmlspecialchars($purok))); ?>
                                </span>
                                <span class="font-extrabold text-3xl text-blue-900 bg-white/80 px-7 py-2 rounded-lg shadow border border-blue-200">
                                    <?php echo $count; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        <div class="flex-1"></div>
                    </div>
                </div>
                <!-- Quick Actions (right) -->
                <div class="bg-blue-200/60 rounded-xl shadow p-4 basis-0 grow-[2] min-h-[180px] flex flex-col border border-blue-300 transition relative overflow-hidden">
                    <div class="absolute right-4 top-4 opacity-10 text-blue-400 text-7xl pointer-events-none select-none">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h2 class="text-lg font-semibold mb-4 text-blue-900 flex items-center gap-2">
                        <i class="fas fa-bolt text-blue-500"></i>
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
                                Export Records via Excel
                            </a>
                            <a href="add_resident.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-blue-300 focus:ring-4 focus:ring-blue-400">
                                <i class="fas fa-user-plus text-xl"></i>
                                Add New Resident
                            </a>
                            <a href="import_residents.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-yellow-300 focus:ring-4 focus:ring-yellow-400">
                                <i class="fas fa-file-upload text-xl"></i>
                                Import Residents (CSV/Excel)
                            </a>
                            <a href="backup_records.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-700 hover:bg-gray-800 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-gray-400 focus:ring-4 focus:ring-gray-500">
                                <i class="fas fa-database text-xl"></i>
                                Backup Records
                            </a>
                            <a href="add_family.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold shadow transition-all w-full justify-center ring-2 ring-transparent hover:ring-teal-300 focus:ring-4 focus:ring-teal-400">
                                <i class="fas fa-house-user text-xl"></i>
                                Add New Family
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Overlay and Card Modal -->
    <div id="cardModalOverlay" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden"></div>
    <div id="cardModal" class="fixed left-1/2 top-1/2 z-50 bg-white rounded-xl shadow-2xl p-6 transform -translate-x-1/2 -translate-y-1/2 hidden modal-flat" style="width:auto;max-width:none;min-width:unset;">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div id="cardModalTitle" class="text-xl font-bold text-blue-800"></div>
            </div>
            <button id="closeCardModal" class="text-gray-500 hover:text-red-500 text-2xl font-bold focus:outline-none" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="cardModalContent" class="text-gray-700" style="overflow:auto;max-height:80vh;">
            <div id="data-table"></div> <!-- Changed ID to data-table and removed style="display:none;" -->
            <!-- Dynamic content goes here -->
        </div>
    </div>
    <!-- Scripts -->
    <script>
        // Sidepanel toggle
        const menuBtn = document.getElementById('menuBtn');
        const sidepanel = document.getElementById('sidepanel');
        const sidepanelOverlay = document.getElementById('sidepanelOverlay');
        const closeSidepanel = document.getElementById('closeSidepanel');

        menuBtn.addEventListener('click', () => {
            sidepanel.classList.remove('-translate-x-full');
            sidepanelOverlay.classList.remove('hidden');
        });

        closeSidepanel.addEventListener('click', () => {
            sidepanel.classList.add('-translate-x-full');
            sidepanelOverlay.classList.add('hidden');
        });

        sidepanelOverlay.addEventListener('click', () => {
            sidepanel.classList.add('-translate-x-full');
            sidepanelOverlay.classList.add('hidden');
        });

        // User dropdown
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');

        userDropdownBtn.addEventListener('click', () => {
            userDropdownMenu.classList.toggle('show');
        });

        // Close user dropdown if clicked outside
        document.addEventListener('click', (e) => {
            if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });

        // --- MODAL STYLE SWITCHER REMOVED ---
        let table;
const fieldOrder = [
    "id", "first_name", "middle_name", "last_name", "suffix", "gender", "birthdate", 
    "house_no", "street", "purok", "barangay", "municipality", "province", "contact_no", 
    "is_voter", "is_senior", "is_pwd", "is_single_parent", "is_student", "is_employed", "is_out_of_school_youth"
];

const fieldTitles = {
    "id": "ID",
    "first_name": "First Name",
    "middle_name": "Middle Name",
    "last_name": "Last Name",
    "suffix": "Suffix",
    "gender": "Gender",
    "birthdate": "Birthdate",
    "house_no": "House No.",
    "street": "Street",
    "purok": "Purok",
    "barangay": "Barangay",
    "municipality": "Municipality",
    "province": "Province",
    "contact_no": "Contact No.",
    "is_voter": "Voter?",
    "is_senior": "Senior?",
    "is_pwd": "PWD?",
    "is_single_parent": "Single Parent?",
    "is_student": "Student?",
    "is_employed": "Employed?",
    "is_out_of_school_youth": "OSY?"
};

function initializeTable(type) {
    const columns = fieldOrder.map(field => {
        let columnDefinition = {
            title: fieldTitles[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()),
            field: field,
            headerFilter: "input",
            headerFilterLiveFilter: false, // Trigger filter only on button click or enter
            sorter: "string", // Default sorter, will be handled server-side
        };

        if (field === 'middle_name') {
            columnDefinition.formatter = function(cell, formatterParams, onRendered) {
                const value = cell.getValue();
                return value ? value.charAt(0).toUpperCase() + '.' : '';
            };
        } else if (field.startsWith('is_')) {
            columnDefinition.formatter = "tickCross";
            columnDefinition.formatterParams = {
                allowEmpty: true,
                tickElement: "<i class=\'fas fa-check text-green-500\'></i>",
                crossElement: "<i class=\'fas fa-times text-red-500\'></i>",
            };
            columnDefinition.headerFilter = "tickCross";
            columnDefinition.headerFilterParams = {
                tristate: true
            };
            columnDefinition.headerFilterEmptyCheck = function(value) {
                return value === null || value === undefined;
            }
        } else if (field === 'birthdate') {
            columnDefinition.formatter = function(cell, formatterParams, onRendered) {
                const value = cell.getValue();
                if (value) {
                    const date = new Date(value);
                    return (date.getMonth() + 1).toString().padStart(2, '0') + '/' +
                           date.getDate().toString().padStart(2, '0') + '/' +
                           date.getFullYear();
                }
                return '';
            };
        } else if (field === 'gender') {
            columnDefinition.formatter = function(cell, formatterParams, onRendered) {
                const value = cell.getValue();
                return value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
            };
        }
        
        if (field === 'id') {
            columnDefinition.visible = false; // Hide ID column by default
        }

        return columnDefinition;
    });

    // Add checkbox selection column
    columns.unshift({
        formatter: "rowSelection",
        titleFormatter: "rowSelection",
        hozAlign: "center",
        headerSort: false,
        cellClick: function(e, cell) {
            cell.getRow().toggleSelect();
        },
        width: 40,
        resizable: false,
        headerHozAlign: "center",
    });


    table = new Tabulator("#data-table", {
        height: "100%", // Let the modal content div handle height and scroll
        layout: "fitDataFill",
        // data: tableData, // Data will be loaded via ajaxURL
        ajaxURL: "dashboard_data.php", // URL for remote data
        ajaxParams: { type: type }, // Pass the 'type' to the backend
        ajaxConfig: "GET", // Use GET request
        pagination: "remote", // Enable remote pagination
        paginationSize: 50, // Number of rows per page
        paginationSizeSelector: [20, 50, 100, 200, true], // Page size options
        ajaxProgressiveLoad: "scroll", // Load data on scroll
        ajaxProgressiveLoadDelay: 200, // Delay before loading next page on scroll
        filterMode: "remote", // Server-side filtering
        sortMode: "remote", // Server-side sorting
        columns: columns,
        selectable: true, // Enable row selection
        // resizableColumns: false, // Already set globally
        // movableColumns: false, // Already set globally
        columnDefaults: {
            resizable: false,
            headerSort: true, // Enable sorting on all columns by default
        },
        autoResize: true, // Automatically resize table to fit container
        rowFormatter: function(row) {
            // Apply Excel-like borders
            var cells = row.getCells();
            cells.forEach(function(cell) {
                cell.getElement().style.borderRight = "1px solid #ccc";
                cell.getElement().style.borderBottom = "1px solid #ccc";
            });
            // Ensure first cell has left border and header cells have top border
            if (cells[0]) {
                cells[0].getElement().style.borderLeft = "1px solid #ccc";
            }
        },
        dataLoaded: function(data) {
            // Style header cells after data is loaded
            const headerCells = document.querySelectorAll("#data-table .tabulator-header .tabulator-col");
            headerCells.forEach(cell => {
                cell.style.borderRight = "1px solid #ccc";
                cell.style.borderBottom = "1px solid #ccc"; // For header bottom border
                cell.style.borderTop = "1px solid #ccc";
                if (cell === headerCells[0]) {
                    cell.style.borderLeft = "1px solid #ccc";
                }
            });
             // Remove rounded corners from the table
            const tableElement = document.querySelector("#data-table .tabulator");
            if (tableElement) {
                tableElement.style.borderRadius = "0px";
                tableElement.style.border = "none"; // Remove main Tabulator border if not needed
            }
            const headerElement = document.querySelector("#data-table .tabulator-header");
            if (headerElement) {
                headerElement.style.borderRadius = "0px";
                headerElement.style.borderBottom = "none"; // Remove header specific border if it causes double lines
            }
        },
    });

    // Apply box-sizing to table elements for consistent layout
    const tableElements = document.querySelectorAll("#data-table .tabulator-row, #data-table .tabulator-cell, #data-table .tabulator-header, #data-table .tabulator-col");
    tableElements.forEach(el => {
        el.style.boxSizing = "border-box";
    });
}

function openCardModal(type, title) {
    document.getElementById('cardModalTitle').innerText = title;
    document.getElementById('cardModal').classList.remove('hidden');
    
    // Initialize or re-initialize the table with the new type
    if (table) {
        // If table exists, destroy it before re-initializing
        // This is important if the 'type' changes and you need a fresh table
        // table.destroy(); 
        // For pagination, we might want to set new ajaxParams and refresh
        table.setAjaxParams({ type: type });
        table.replaceData(); // This will trigger a new AJAX request with the new params
    } else {
        initializeTable(type);
    }
}

// On page load, set default style
    </script>
</body>
</html>
