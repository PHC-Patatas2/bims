<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    // Log the detailed error for yourself
    error_log('Database connection failed: ' . $conn->connect_error);
    // Show a friendly message to the user
    include 'error_page.php';
    exit();
}
$user_id = $_SESSION['user_id'];

$user_first_name = '';
$user_last_name = '';
$stmt = $conn->prepare('SELECT first_name, last_name FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($user_first_name, $user_last_name);
$stmt->fetch();
$stmt->close();
$user_full_name = trim($user_first_name . ' ' . $user_last_name);

// Fetch system title from system_settings table
$system_title = 'Resident Information and Certification Management System'; // default fallback
$title_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='system_title' LIMIT 1");
if ($title_result && $title_row = $title_result->fetch_assoc()) {
    if (!empty($title_row['setting_value'])) {
        $system_title = $title_row['setting_value'];
    }
}

// Fetch dashboard stats from database (optimized single query, no newborns, correct minors/seniors logic)
$stats = [
    'total_residents' => null,
    'total_male' => null,
    'total_female' => null,
    'total_voters' => null,
    'total_minors' => null,
    'total_seniors' => null,
    'total_pwd' => null,
    'total_solo_parents' => null,
    'total_4ps' => null,
];

$sql = "SELECT
    COUNT(*) AS total_residents,
    IFNULL(SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END), 0) AS total_male,
    IFNULL(SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END), 0) AS total_female,
    IFNULL(SUM(is_voter), 0) AS total_voters,
    IFNULL(SUM(CASE WHEN birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) <= 17 THEN 1 ELSE 0 END), 0) AS total_minors,
    IFNULL(SUM(CASE WHEN birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 THEN 1 ELSE 0 END), 0) AS total_seniors,
    IFNULL(SUM(is_pwd), 0) AS total_pwd,
    IFNULL(SUM(is_solo_parent), 0) AS total_solo_parents,
    IFNULL(SUM(is_4ps), 0) AS total_4ps
FROM individuals";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    foreach ($stats as $key => $_) {
        if (isset($row[$key])) {
            $stats[$key] = (int)$row[$key];
        }
    }
}

// Fetch purok list and stats from normalized purok table
$purok_list = [];
$purok_stats = [];
$purok_result = $conn->query("SELECT id, name FROM purok ORDER BY id ASC");
if ($purok_result) {
    while ($row = $purok_result->fetch_assoc()) {
        $purok_list[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
        $purok_stats[$row['id']] = 0; // default to 0
    }
}
if (!empty($purok_list)) {
    // Get resident count per purok using purok_id
    $purok_ids = array_column($purok_list, 'id');
    $purok_id_in = implode(',', array_map('intval', $purok_ids));
    $purok_query = "SELECT purok_id, COUNT(*) as total FROM individuals WHERE purok_id IN ($purok_id_in) GROUP BY purok_id";
    $purok_result2 = $conn->query($purok_query);
    if ($purok_result2) {
        while ($row = $purok_result2->fetch_assoc()) {
            $purok_stats[$row['purok_id']] = (int)$row['total'];
        }
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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Dashboard - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Comprehensive console warning suppression for development environment
        // TODO: For production, replace with proper Tailwind CSS installation
        if (typeof console !== 'undefined') {
            const originalWarn = console.warn;
            const originalError = console.error;
            
            console.warn = function(...args) {
                const message = args.join(' ');
                if (!message.includes('should not be used in production') && 
                    !message.includes('cdn.tailwindcss.com')) {
                    originalWarn.apply(console, args);
                }
            };
            
            console.error = function(...args) {
                const message = args.join(' ');
                if (!message.includes('Failed to find a valid digest') && 
                    !message.includes('integrity attribute')) {
                    originalError.apply(console, args);
                }
            };
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css?v=<?php echo time(); ?>" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/css/tabulator.min.css?v=<?php echo time(); ?>" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js?v=<?php echo time(); ?>" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/js/tabulator.min.js?v=<?php echo time(); ?>" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        /* Custom thin scrollbar for sidepanel */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #2563eb #353535;
            padding-right: 6px; /* Always reserve space for scrollbar */
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #2563eb;
            border-radius: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #353535;
        }
        /* Always show scrollbar track to prevent layout shift */
        .custom-scrollbar {
            overflow-y: scroll;
        }
        .custom-scrollbar::-webkit-scrollbar {
            background: #353535;
        }
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
    <div id="sidepanel" class="fixed top-0 left-0 h-full w-80 shadow-lg z-40 transform -translate-x-full transition-transform duration-300 ease-in-out sidebar-border overflow-y-auto custom-scrollbar" style="background-color: #454545;">
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
        <nav class="flex flex-col p-4 gap-2 text-white">
            <?php
            // --- Sidepanel Navigation Refactored ---
            $current = basename($_SERVER['PHP_SELF']);
            function navActive($pages) {
                global $current;
                return in_array($current, (array)$pages);
            }
            function navLink($href, $icon, $label, $active, $extra = '') {
                $classes = $active ? 'bg-blue-600 text-white font-bold shadow-md' : 'text-white';
                return '<a href="' . $href . '" class="py-2 px-3 rounded-lg flex items-center gap-2 ' . $classes . ' hover:bg-blue-500 hover:text-white ' . $extra . '"><i class="' . $icon . '"></i> ' . $label . '</a>';
            }
            echo navLink('dashboard.php', 'fas fa-tachometer-alt', 'Dashboard', navActive('dashboard.php'));

            // People Management
            $peopleActive = navActive(['individuals.php']);
            $peopleId = 'peopleSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $peopleActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $peopleId; ?>')">
                    <i class="fas fa-users"></i> People Management
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </button>
                <div id="<?php echo $peopleId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $peopleActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php echo navLink('individuals.php', 'fas fa-user', 'Residents', navActive('individuals.php'), 'rounded'); ?>
                </div>
            </div>

            <?php
            // Barangay Documents
            $docsActive = navActive(['certificate.php', 'reports.php', 'issued_documents.php']);
            $docsId = 'docsSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $docsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $docsId; ?>')">
                    <i class="fas fa-file-alt"></i> Barangay Documents
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </button>
                <div id="<?php echo $docsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $docsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php echo navLink('certificate.php', 'fas fa-stamp', 'Issue Certificate', navActive('certificate.php'), 'rounded'); ?>
                    <?php echo navLink('reports.php', 'fas fa-chart-bar', 'Generate Reports', navActive('reports.php'), 'rounded'); ?>
                    <?php echo navLink('issued_documents.php', 'fas fa-history', 'Issued Documents Log', navActive('issued_documents.php'), 'rounded'); ?>
                </div>
            </div>

            <?php
            // System Settings
            $settingsActive = navActive(['officials.php', 'settings.php', 'logs.php']);
            $settingsId = 'settingsSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $settingsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $settingsId; ?>')">
                    <i class="fas fa-cogs"></i> System Settings
                    <i class="fas fa-chevron-down ml-auto text-xs"></i>
                </button>
                <div id="<?php echo $settingsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $settingsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php echo navLink('officials.php', 'fas fa-user-tie', 'Officials Management', navActive('officials.php'), 'rounded'); ?>
                    <?php echo navLink('settings.php', 'fas fa-cog', 'General Settings', navActive('settings.php'), 'rounded'); ?>
                    <?php echo navLink('logs.php', 'fas fa-clipboard-list', 'Logs', navActive('logs.php'), 'rounded'); ?>
                </div>
            </div>
            
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
            <?php
            // A reusable function to generate perfectly styled stat cards.
            function render_stat_card($title, $value, $icon_class, $color_theme, $link)
            {
                // Define color classes based on the theme
                $colors = [
                    'blue'    => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
                    'teal'    => ['bg' => 'bg-teal-100', 'text' => 'text-teal-600'],
                    'pink'    => ['bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
                    'indigo'  => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
                    'orange'  => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
                    'yellow'  => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
                    'purple'  => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
                    'red'     => ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
                    'green'   => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
                ];
                $color_classes = $colors[$color_theme] ?? $colors['blue']; // Default to blue if color not found

                // The number part of the card
                $count_html = '<div class="text-3xl font-bold text-gray-800">' . (int)$value . '</div>';
                ?>
                <div class="bg-white rounded-xl shadow-md p-4 flex flex-col justify-between min-h-[120px]">
                    <div class="flex justify-between items-start">
                        <!-- Text content on the left -->
                        <div class="flex flex-col">
                            <?php echo $count_html; ?>
                            <div class="text-base text-gray-600 font-medium mt-2"><?php echo htmlspecialchars($title); ?></div>
                        </div>
                        <!-- Icon on the right -->
                        <div class="flex items-center justify-center">
                            <span class="inline-flex items-center justify-center w-14 h-14 rounded-full <?php echo $color_classes['bg']; ?>">
                                <i class="<?php echo $icon_class; ?> text-3xl <?php echo $color_classes['text']; ?>"></i>
                            </span>
                        </div>
                    </div>
                    <!-- "View more info" link at the bottom, aligned right -->
                    <div class="flex justify-end mt-4">
                        <a href="<?php echo $link; ?>" class="<?php echo $color_classes['text']; ?> hover:underline text-sm font-semibold flex items-center gap-1 transition-colors">
                            <span>View more info</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php
            }

            // Array holding all the data for our stat cards
            $cards = [
                ['title' => 'Total Population', 'value' => $stats['total_residents'], 'icon' => 'fas fa-users', 'color' => 'blue', 'link' => 'individuals.php'],
                ['title' => 'Male Residents', 'value' => $stats['total_male'], 'icon' => 'fas fa-mars', 'color' => 'teal', 'link' => 'individuals.php?filter_type=male'],
                ['title' => 'Female Residents', 'value' => $stats['total_female'], 'icon' => 'fas fa-venus', 'color' => 'pink', 'link' => 'individuals.php?filter_type=female'],
                ['title' => 'Registered Voters', 'value' => $stats['total_voters'], 'icon' => 'fas fa-vote-yea', 'color' => 'indigo', 'link' => 'individuals.php?filter_type=voter'],
                ['title' => '4Ps Beneficiaries', 'value' => $stats['total_4ps'], 'icon' => 'fas fa-hand-holding-heart', 'color' => 'orange', 'link' => 'individuals.php?filter_type=4ps'],
                ['title' => 'Senior Citizens', 'value' => $stats['total_seniors'], 'icon' => 'fas fa-person-cane', 'color' => 'yellow', 'link' => 'individuals.php?filter_type=senior'],
                ['title' => 'Registered PWDs', 'value' => $stats['total_pwd'], 'icon' => 'fas fa-wheelchair', 'color' => 'purple', 'link' => 'individuals.php?filter_type=pwd'],
                ['title' => 'Solo Parents', 'value' => $stats['total_solo_parents'], 'icon' => 'fas fa-user-shield', 'color' => 'red', 'link' => 'individuals.php?filter_type=solo_parent'],
                ['title' => 'Children & Youth', 'value' => $stats['total_minors'], 'icon' => 'fas fa-child', 'color' => 'green', 'link' => 'individuals.php?filter_type=minor'],
            ];
            ?>

            <!-- Stat cards (3x3) on the left, Recent Activity on the right, both 520px height -->
            <div class="w-full flex flex-row gap-6 mb-6 justify-end">
                <!-- Stat Cards Grid -->
                <div class="grid grid-cols-3 grid-rows-3 gap-4 flex-1" style="height: 520px;">
                    <?php
                    // Define the 9 cards in the desired order
                    $stat_cards = [
                        ['title' => 'Total Population', 'value' => $stats['total_residents'], 'icon' => 'fas fa-users', 'color' => 'blue', 'link' => 'individuals.php'],
                        ['title' => 'Male Residents', 'value' => $stats['total_male'], 'icon' => 'fas fa-mars', 'color' => 'teal', 'link' => 'individuals.php?filter_type=male'],
                        ['title' => 'Female Residents', 'value' => $stats['total_female'], 'icon' => 'fas fa-venus', 'color' => 'pink', 'link' => 'individuals.php?filter_type=female'],
                        ['title' => 'Children & Youth', 'value' => $stats['total_minors'], 'icon' => 'fas fa-child', 'color' => 'green', 'link' => 'individuals.php?filter_type=minor'],
                        ['title' => 'Registered Voters', 'value' => $stats['total_voters'], 'icon' => 'fas fa-vote-yea', 'color' => 'indigo', 'link' => 'individuals.php?filter_type=voter'],
                        ['title' => 'Senior Citizens', 'value' => $stats['total_seniors'], 'icon' => 'fas fa-person-cane', 'color' => 'yellow', 'link' => 'individuals.php?filter_type=senior'],
                        ['title' => 'Registered PWDs', 'value' => $stats['total_pwd'], 'icon' => 'fas fa-wheelchair', 'color' => 'purple', 'link' => 'individuals.php?filter_type=pwd'],
                        ['title' => '4Ps Beneficiaries', 'value' => $stats['total_4ps'], 'icon' => 'fas fa-hand-holding-heart', 'color' => 'orange', 'link' => 'individuals.php?filter_type=4ps'],
                        ['title' => 'Solo Parents', 'value' => $stats['total_solo_parents'], 'icon' => 'fas fa-user-shield', 'color' => 'red', 'link' => 'individuals.php?filter_type=solo_parent'],
                    ];
                    foreach ($stat_cards as $card) {
                        render_stat_card($card['title'], $card['value'], $card['icon'], $card['color'], $card['link']);
                    }
                    ?>
                </div>
                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow p-4 border border-blue-200 transition relative overflow-hidden flex flex-col w-full max-w-xs" style="height: 520px;">
                    <div class="absolute right-6 top-6 opacity-10 text-blue-400 text-7xl pointer-events-none select-none">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="flex items-center gap-3 mb-4 z-10 relative tracking-wide">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100">
                            <i class="fas fa-history text-blue-500 text-2xl"></i>
                        </span>
                        <h2 class="text-lg font-bold text-blue-900">Recent Activity</h2>
                    </div>
                    <div class="flex-1 overflow-y-auto z-10 relative">
                        <?php
                        // Fetch recent activity from audit_trail (latest 10)
                        $recent_activities = [];
                        $activity_sql = "SELECT a.*, u.first_name, u.last_name FROM audit_trail a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.timestamp DESC LIMIT 10";
                        $activity_result = $conn->query($activity_sql);
                        if ($activity_result) {
                            while ($act = $activity_result->fetch_assoc()) {
                                $recent_activities[] = $act;
                            }
                        }

                        // Helper: human readable time ago
                        function timeAgo($datetime) {
                            $timestamp = strtotime($datetime);
                            $diff = time() - $timestamp;
                            if ($diff < 60) return $diff . ' sec ago';
                            $mins = floor($diff / 60);
                            if ($mins < 60) return $mins . ' min ago';
                            $hours = floor($mins / 60);
                            if ($hours < 24) return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                            $days = floor($hours / 24);
                            if ($days < 7) return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                            return date('M d, Y h:i A', $timestamp);
                        }

                        // Icon map for actions (customize as needed)
                        $action_icons = [
                            'add' => ['icon' => 'fa-user-plus', 'bg' => 'bg-green-100', 'color' => 'text-green-600'],
                            'update' => ['icon' => 'fa-user-edit', 'bg' => 'bg-red-100', 'color' => 'text-red-600'],
                            'delete' => ['icon' => 'fa-user-times', 'bg' => 'bg-gray-200', 'color' => 'text-gray-600'],
                            'login' => ['icon' => 'fa-sign-in-alt', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                            'logout' => ['icon' => 'fa-sign-out-alt', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                            'certificate' => ['icon' => 'fa-file-alt', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                            'report' => ['icon' => 'fa-chart-bar', 'bg' => 'bg-green-100', 'color' => 'text-green-600'],
                            'id' => ['icon' => 'fa-id-card', 'bg' => 'bg-yellow-100', 'color' => 'text-yellow-600'],
                            // fallback
                            'default' => ['icon' => 'fa-history', 'bg' => 'bg-purple-100', 'color' => 'text-purple-600'],
                        ];

                        echo '<ul class="flex flex-col gap-2 text-gray-700 text-xs">';
                        if (count($recent_activities) === 0) {
                            echo '<li class="text-center text-gray-400 py-6">No recent activity found.</li>';
                        } else {
                            foreach ($recent_activities as $activity) {
                                $user = trim(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''));
                                $action = strtolower($activity['action']);
                                $icon_info = $action_icons['default'];
                                foreach ($action_icons as $key => $info) {
                                    if (strpos($action, $key) !== false) {
                                        $icon_info = $info;
                                        break;
                                    }
                                }
                                $time_ago = timeAgo($activity['timestamp']);
                                $details = $activity['details'] ? htmlspecialchars($activity['details']) : '';
                                echo '<li class="recent-activity-item group flex flex-col" data-time="' . htmlspecialchars($time_ago) . '">';
                                echo '<div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2 shadow-sm border border-gray-200 relative overflow-hidden recent-activity-hover">';
                                echo '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full ' . $icon_info['bg'] . '"><i class="fas ' . $icon_info['icon'] . ' ' . $icon_info['color'] . ' text-xs"></i></span>';
                                echo '<span class="break-words flex-1">';
                                if ($user) {
                                    echo '<strong class="font-semibold">' . htmlspecialchars($user) . '</strong> ';
                                }
                                echo htmlspecialchars($activity['action']);
                                if ($details) {
                                    echo ': <span class="text-gray-500">' . $details . '</span>';
                                }
                                echo '</span>';
                                echo '</div>';
                                echo '</li>';
                            }
                        }
                        echo '</ul>';
                        ?>
                    </div>
                    <!-- See more button at the bottom -->
                    <div class="flex justify-center mt-3">
                        <a href="#" class="inline-flex items-center gap-1 px-3 py-1 rounded text-blue-600 hover:bg-blue-50 hover:underline text-xs font-semibold transition">
                            <span>See more</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            </div>
            <!-- Below: Number of Individuals per Purok and Quick Actions -->
            <div class="w-full flex flex-row gap-6 mb-6 px-4 md:px-8">
                <!-- Number of Individuals per Purok -->
                <div class="bg-white rounded-xl shadow p-4 border border-blue-200 flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100">
                            <i class="fas fa-map-marker-alt text-blue-500 text-lg"></i>
                        </span>
                        <h2 class="text-base font-bold text-blue-900">Number of Individuals per Purok</h2>
                    </div>
                    <div class="flex flex-col gap-2">
                        <?php foreach ($purok_list as $purok): ?>
                            <div class="flex items-center justify-between px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($purok['name']); ?></span>
                                <span class="text-xl font-bold text-blue-700"><?php echo isset($purok_stats[$purok['id']]) ? $purok_stats[$purok['id']] : 0; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow p-4 border border-blue-200 w-full max-w-xs flex flex-col min-w-[260px]">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100">
                            <i class="fas fa-bolt text-green-500 text-lg"></i>
                        </span>
                        <h2 class="text-base font-bold text-green-900">Quick Actions</h2>
                    </div>
                    <div class="flex flex-col gap-2 mt-2">
                        <a href="certificate.php" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold transition">
                            <i class="fas fa-file-alt"></i> Print Barangay Certificate
                        </a>
                        <a href="reports.php" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-green-50 hover:bg-green-100 text-green-700 font-semibold transition">
                            <i class="fas fa-chart-bar"></i> Generate Demographic Reports
                        </a>
                        <a href="individuals.php" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-yellow-50 hover:bg-yellow-100 text-yellow-700 font-semibold transition">
                            <i class="fas fa-file-excel"></i> Export Resident List
                        </a>
                        <button onclick="openAnnouncementModal()" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 font-semibold transition w-full text-left">
                            <i class="fas fa-bullhorn"></i> Send Announcement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Overlay and Card Modal -->
    <!-- Floating tooltip for recent activity time -->
    <div id="recent-activity-tooltip" style="display:none;position:fixed;z-index:9999;pointer-events:none;" class="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded shadow whitespace-nowrap transition-opacity duration-100 opacity-0"></div>
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
    <!-- Announcement Modal -->
    <div id="announcementModalOverlay" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden"></div>
    <div id="announcementModal" class="fixed left-1/2 top-1/2 z-50 bg-white rounded-xl shadow-2xl p-6 transform -translate-x-1/2 -translate-y-1/2 hidden modal-flat" style="width:600px;max-width:90vw;">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-100">
                    <i class="fas fa-bullhorn text-purple-500 text-lg"></i>
                </span>
                <div class="text-xl font-bold text-purple-800">Send Announcement</div>
            </div>
            <button id="closeAnnouncementModal" class="text-gray-500 hover:text-red-500 text-2xl font-bold focus:outline-none" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="announcementForm" class="space-y-4">
            <div>
                <label for="emailSubject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input type="text" id="emailSubject" name="subject" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                       placeholder="Enter email subject">
            </div>
            <div>
                <label for="emailMessage" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                <textarea id="emailMessage" name="message" required rows="6"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-vertical" 
                          placeholder="Enter your announcement message"></textarea>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <div class="flex items-center gap-2 text-blue-700">
                    <i class="fas fa-info-circle"></i>
                    <span class="font-medium">Recipients:</span>
                </div>
                <p class="text-sm text-blue-600 mt-1">This announcement will be sent to all residents who have email addresses in the system.</p>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeAnnouncementModal()" 
                        class="px-4 py-2 text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    Send Announcement
                </button>
            </div>
        </form>
        <div id="announcementProgress" class="hidden">
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                <p class="mt-2 text-gray-600">Sending emails...</p>
                <div id="progressText" class="text-sm text-gray-500 mt-1"></div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Floating tooltip for recent activity time
        (function() {
            const tooltip = document.getElementById('recent-activity-tooltip');
            let active = false;
            document.querySelectorAll('.recent-activity-item .recent-activity-hover').forEach(function(item) {
                item.addEventListener('mouseenter', function(e) {
                    const li = item.closest('.recent-activity-item');
                    if (li && li.dataset.time) {
                        tooltip.textContent = li.dataset.time;
                        tooltip.style.display = 'block';
                        tooltip.style.opacity = '1';
                        active = true;
                    }
                });
                item.addEventListener('mousemove', function(e) {
                    if (active) {
                        // Offset tooltip so it doesn't cover the activity
                        const offsetX = 18;
                        const offsetY = 8;
                        tooltip.style.left = (e.clientX + offsetX) + 'px';
                        tooltip.style.top = (e.clientY + offsetY) + 'px';
                    }
                });
                item.addEventListener('mouseleave', function() {
                    tooltip.style.display = 'none';
                    tooltip.style.opacity = '0';
                    active = false;
                });
            });
        })();
        // Sidepanel toggle
        const menuBtn = document.getElementById('menuBtn');
        const sidepanel = document.getElementById('sidepanel');
        const sidepanelOverlay = document.getElementById('sidepanelOverlay');
        const closeSidepanel = document.getElementById('closeSidepanel');


        function openSidepanel() {
            sidepanel.classList.remove('-translate-x-full');
            sidepanelOverlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
        function closeSidepanelFn() {
            sidepanel.classList.add('-translate-x-full');
            sidepanelOverlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        menuBtn.addEventListener('click', openSidepanel);
        closeSidepanel.addEventListener('click', closeSidepanelFn);
        sidepanelOverlay.addEventListener('click', closeSidepanelFn);

        // Dropdown logic for sidepanel (only one open at a time)
        function toggleDropdown(id) {
            const dropdowns = ['peopleSubNav', 'docsSubNav', 'settingsSubNav'];
            dropdowns.forEach(function(dropId) {
                const el = document.getElementById(dropId);
                if (el) {
                    if (dropId === id) {
                        if (el.classList.contains('dropdown-open')) {
                            el.classList.remove('dropdown-open');
                            el.classList.add('dropdown-closed');
                        } else {
                            el.classList.remove('dropdown-closed');
                            el.classList.add('dropdown-open');
                        }
                    } else {
                        el.classList.remove('dropdown-open');
                        el.classList.add('dropdown-closed');
                    }
                }
            });
        }
        // Dropdown open/close effect styles
        const style = document.createElement('style');
        style.innerHTML = `
        .dropdown-open {
            max-height: 500px;
            opacity: 1;
            pointer-events: auto;
            overflow: hidden;
        }
        .dropdown-closed {
            max-height: 0;
            opacity: 0;
            pointer-events: none;
            overflow: hidden;
        }
        `;
        document.head.appendChild(style);

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

// Announcement Modal Functions
function openAnnouncementModal() {
    document.getElementById('announcementModal').classList.remove('hidden');
    document.getElementById('announcementModalOverlay').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeAnnouncementModal() {
    document.getElementById('announcementModal').classList.add('hidden');
    document.getElementById('announcementModalOverlay').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    // Reset form
    document.getElementById('announcementForm').reset();
    document.getElementById('announcementForm').classList.remove('hidden');
    document.getElementById('announcementProgress').classList.add('hidden');
}

// Close modal when clicking overlay
document.getElementById('announcementModalOverlay').addEventListener('click', closeAnnouncementModal);

// Handle form submission
document.getElementById('announcementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const subject = document.getElementById('emailSubject').value;
    const message = document.getElementById('emailMessage').value;
    
    if (!subject.trim() || !message.trim()) {
        alert('Please fill in all required fields.');
        return;
    }
    
    // Show progress
    document.getElementById('announcementForm').classList.add('hidden');
    document.getElementById('announcementProgress').classList.remove('hidden');
    
    // Send announcement
    const formData = new FormData();
    formData.append('subject', subject);
    formData.append('message', message);
    
    fetch('send_announcement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Announcement sent successfully to ${data.count} recipients!`);
            closeAnnouncementModal();
        } else {
            alert('Error sending announcement: ' + data.message);
            document.getElementById('announcementForm').classList.remove('hidden');
            document.getElementById('announcementProgress').classList.add('hidden');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the announcement.');
        document.getElementById('announcementForm').classList.remove('hidden');
        document.getElementById('announcementProgress').classList.add('hidden');
    });
});
    </script>
</body>
</html>
