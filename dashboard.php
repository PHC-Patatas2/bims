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
                    !message.includes('cdn.tailwindcss.com') &&
                    !message.includes('Tailwind CSS')) {
                    originalWarn.apply(console, args);
                }
            };
            
            console.error = function(...args) {
                const message = args.join(' ');
                if (!message.includes('Failed to find a valid digest') && 
                    !message.includes('integrity attribute') &&
                    !message.includes('Unexpected token') &&
                    !message.includes('not valid JSON')) {
                    originalError.apply(console, args);
                }
            };
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css?v=<?php echo time(); ?>" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js?v=<?php echo time(); ?>" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <style>
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 10px 20px -5px #0002; }
        .stat-card .icon { transition: transform 0.3s; }
        .stat-card:hover .icon { transform: scale(1.1); }
        .calendar-day-today { background: #2563eb; color: #fff; border-radius: 50%; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Include Navigation -->
    <?php include 'navigation.php'; ?>
    
    <!-- Main Content -->
    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <?php
            // A reusable function to generate perfectly styled stat cards.
            function render_stat_card($title, $value, $icon_class, $color_theme, $filter_type)
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
                
                // Determine the URL based on filter type
                $url = 'individuals.php';
                if ($filter_type !== 'all') {
                    $url .= '?filter_type=' . urlencode($filter_type);
                }
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
                        <a href="<?php echo $url; ?>" 
                           class="<?php echo $color_classes['text']; ?> hover:underline text-sm font-semibold flex items-center gap-1 transition-colors">
                            <span>View more info</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php
            }
            ?>

            <!-- Stat cards (3x3) on the left, Recent Activity on the right, both 520px height -->
            <div class="w-full flex flex-row gap-6 mb-6 justify-end">
                <!-- Stat Cards Grid -->
                <div class="grid grid-cols-3 grid-rows-3 gap-4 flex-1" style="height: 520px;">
                    <?php
                    // Define the 9 cards in the desired order
                    $stat_cards = [
                        ['title' => 'Total Population', 'value' => $stats['total_residents'], 'icon' => 'fas fa-users', 'color' => 'blue', 'filter_type' => 'all'],
                        ['title' => 'Male Residents', 'value' => $stats['total_male'], 'icon' => 'fas fa-mars', 'color' => 'teal', 'filter_type' => 'male'],
                        ['title' => 'Female Residents', 'value' => $stats['total_female'], 'icon' => 'fas fa-venus', 'color' => 'pink', 'filter_type' => 'female'],
                        ['title' => 'Children & Youth', 'value' => $stats['total_minors'], 'icon' => 'fas fa-child', 'color' => 'green', 'filter_type' => 'minor'],
                        ['title' => 'Registered Voters', 'value' => $stats['total_voters'], 'icon' => 'fas fa-vote-yea', 'color' => 'indigo', 'filter_type' => 'voter'],
                        ['title' => 'Senior Citizens', 'value' => $stats['total_seniors'], 'icon' => 'fas fa-person-cane', 'color' => 'yellow', 'filter_type' => 'senior'],
                        ['title' => 'Registered PWDs', 'value' => $stats['total_pwd'], 'icon' => 'fas fa-wheelchair', 'color' => 'purple', 'filter_type' => 'pwd'],
                        ['title' => '4Ps Beneficiaries', 'value' => $stats['total_4ps'], 'icon' => 'fas fa-hand-holding-heart', 'color' => 'orange', 'filter_type' => '4ps'],
                        ['title' => 'Solo Parents', 'value' => $stats['total_solo_parents'], 'icon' => 'fas fa-user-shield', 'color' => 'red', 'filter_type' => 'solo_parent'],
                    ];
                    foreach ($stat_cards as $card) {
                        render_stat_card($card['title'], $card['value'], $card['icon'], $card['color'], $card['filter_type']);
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
                        // Fetch recent activity from audit_trail (latest 10) with UTC timestamps
                        $recent_activities = [];
                        $activity_sql = "SELECT a.*, u.first_name, u.last_name, UNIX_TIMESTAMP(a.timestamp) as unix_timestamp FROM audit_trail a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.timestamp DESC LIMIT 10";
                        $activity_result = $conn->query($activity_sql);
                        if ($activity_result) {
                            while ($act = $activity_result->fetch_assoc()) {
                                $recent_activities[] = $act;
                            }
                        }

                        // Helper: get timestamp for JavaScript processing (ensure UTC)
                        function getTimestampForJS($unix_timestamp) {
                            if (empty($unix_timestamp)) {
                                return 'null';
                            }
                            
                            // Unix timestamp is already in UTC, just convert to milliseconds for JavaScript
                            return $unix_timestamp * 1000;
                        }

                        // Helper: format audit log activity for user-friendly display
                        function formatActivityMessage($action, $details, $user_name) {
                            $action_lower = strtolower($action);
                            $parsed_details = json_decode($details, true);
                            
                            // Handle different action types with user-friendly messages
                            if (strpos($action_lower, 'login') !== false) {
                                if ($parsed_details && isset($parsed_details['username'])) {
                                    return "Logged in as " . htmlspecialchars($parsed_details['username']);
                                }
                                return "Logged in";
                            }
                            
                            if (strpos($action_lower, 'logout') !== false) {
                                return "Logged out";
                            }
                            
                            if (strpos($action_lower, 'add') !== false && strpos($action_lower, 'individual') !== false) {
                                if ($parsed_details && isset($parsed_details['resident_name'])) {
                                    return "Added resident: " . htmlspecialchars($parsed_details['resident_name']);
                                }
                                return "Added a new resident";
                            }
                            
                            if (strpos($action_lower, 'edit') !== false && strpos($action_lower, 'individual') !== false) {
                                if ($parsed_details && isset($parsed_details['resident_name'])) {
                                    return "Updated resident: " . htmlspecialchars($parsed_details['resident_name']);
                                }
                                return "Updated a resident";
                            }
                            
                            if (strpos($action_lower, 'delete') !== false && strpos($action_lower, 'individual') !== false) {
                                if ($parsed_details && isset($parsed_details['resident_name'])) {
                                    return "Deleted resident: " . htmlspecialchars($parsed_details['resident_name']);
                                }
                                return "Deleted a resident";
                            }
                            
                            if (strpos($action_lower, 'certificate') !== false && strpos($action_lower, 'generated') !== false) {
                                if ($parsed_details && isset($parsed_details['resident_name'])) {
                                    $cert_type = isset($parsed_details['certificate_type']) ? $parsed_details['certificate_type'] : 'Certificate';
                                    return "Issued " . htmlspecialchars($cert_type) . " for: " . htmlspecialchars($parsed_details['resident_name']);
                                }
                                return "Issued a certificate";
                            }
                            
                            if (strpos($action_lower, 'add') !== false && strpos($action_lower, 'official') !== false) {
                                if ($parsed_details && isset($parsed_details['name'])) {
                                    return "Added official: " . htmlspecialchars($parsed_details['name']);
                                }
                                return "Added a new official";
                            }
                            
                            if (strpos($action_lower, 'update') !== false && strpos($action_lower, 'official') !== false) {
                                if ($parsed_details && isset($parsed_details['name'])) {
                                    return "Updated official: " . htmlspecialchars($parsed_details['name']);
                                }
                                return "Updated an official";
                            }
                            
                            if (strpos($action_lower, 'delete') !== false && strpos($action_lower, 'official') !== false) {
                                if ($parsed_details && isset($parsed_details['name'])) {
                                    return "Deleted official: " . htmlspecialchars($parsed_details['name']);
                                }
                                return "Deleted an official";
                            }
                            
                            if (strpos($action_lower, 'settings') !== false || strpos($action_lower, 'save') !== false) {
                                return "Updated system settings";
                            }
                            
                            if (strpos($action_lower, 'password') !== false && strpos($action_lower, 'reset') !== false) {
                                return "Changed password";
                            }
                            
                            if (strpos($action_lower, 'announcement') !== false) {
                                if ($parsed_details && isset($parsed_details['title'])) {
                                    return "Sent announcement: " . htmlspecialchars($parsed_details['title']);
                                }
                                return "Sent an announcement";
                            }
                            
                            // For test entries and unknown actions, try to make them more readable
                            if (strpos($action_lower, 'test') !== false) {
                                return "System test performed";
                            }
                            
                            // Default: clean up the action name and return it
                            $clean_action = ucfirst(str_replace(['_', '-'], ' ', $action));
                            return $clean_action;
                        }

                        // Icon map for actions (customize as needed)
                        $action_icons = [
                            'add' => ['icon' => 'fa-user-plus', 'bg' => 'bg-green-100', 'color' => 'text-green-600'],
                            'update' => ['icon' => 'fa-user-edit', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                            'edit' => ['icon' => 'fa-user-edit', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                            'delete' => ['icon' => 'fa-user-times', 'bg' => 'bg-red-100', 'color' => 'text-red-600'],
                            'login' => ['icon' => 'fa-sign-in-alt', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                            'logout' => ['icon' => 'fa-sign-out-alt', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                            'certificate' => ['icon' => 'fa-file-alt', 'bg' => 'bg-green-100', 'color' => 'text-green-600'],
                            'report' => ['icon' => 'fa-chart-bar', 'bg' => 'bg-purple-100', 'color' => 'text-purple-600'],
                            'announcement' => ['icon' => 'fa-bullhorn', 'bg' => 'bg-orange-100', 'color' => 'text-orange-600'],
                            'password' => ['icon' => 'fa-key', 'bg' => 'bg-yellow-100', 'color' => 'text-yellow-600'],
                            'settings' => ['icon' => 'fa-cog', 'bg' => 'bg-gray-100', 'color' => 'text-gray-600'],
                            'official' => ['icon' => 'fa-user-tie', 'bg' => 'bg-indigo-100', 'color' => 'text-indigo-600'],
                            'test' => ['icon' => 'fa-flask', 'bg' => 'bg-pink-100', 'color' => 'text-pink-600'],
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
                                $timestamp_js = getTimestampForJS($activity['unix_timestamp']);
                                
                                // Use the formatting function to get a clean, user-friendly message
                                $formatted_message = formatActivityMessage($activity['action'], $activity['details'], $user);
                                
                                echo '<li class="recent-activity-item group flex flex-col" data-timestamp="' . $timestamp_js . '">';
                                echo '<div class="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2 shadow-sm border border-gray-200 relative overflow-hidden recent-activity-hover">';
                                echo '<span class="inline-flex items-center justify-center w-6 h-6 rounded-full ' . $icon_info['bg'] . '"><i class="fas ' . $icon_info['icon'] . ' ' . $icon_info['color'] . ' text-xs"></i></span>';
                                echo '<div class="break-words flex-1">';
                                echo '<div class="flex items-center justify-between">';
                                echo '<span>';
                                if ($user) {
                                    echo '<strong class="font-semibold">' . htmlspecialchars($user) . '</strong> ';
                                }
                                echo $formatted_message;
                                echo '</span>';
                                echo '<span class="time-display text-xs text-gray-400 ml-2 whitespace-nowrap"></span>';
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                                echo '</li>';
                            }
                        }
                        echo '</ul>';
                        ?>
                    </div>
                    <!-- See more button at the bottom -->
                    <div class="flex justify-center mt-3">
                        <a href="logs.php" class="inline-flex items-center gap-1 px-3 py-1 rounded text-blue-600 hover:bg-blue-50 hover:underline text-xs font-semibold transition">
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
    <!-- Floating tooltip for recent activity time -->
    <div id="recent-activity-tooltip" style="display:none;position:fixed;z-index:9999;pointer-events:none;" class="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded shadow whitespace-nowrap transition-opacity duration-100 opacity-0"></div>
    
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
            <button onclick="closeAnnouncementModal()" class="text-gray-500 hover:text-red-500 text-2xl font-bold focus:outline-none" aria-label="Close">
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
        // JavaScript-based time ago calculation for accurate timezone handling
        function timeAgo(timestamp) {
            if (!timestamp || timestamp === 'null') {
                return 'Unknown time';
            }
            
            const now = new Date().getTime();
            const diff = Math.floor((now - timestamp) / 1000); // difference in seconds
            
            // Handle future timestamps (might be due to timezone differences)
            if (diff < 0) {
                const absDiff = Math.abs(diff);
                if (absDiff <= 3600) { // within 1 hour, probably timezone issue
                    return 'just now';
                } else {
                    // For larger differences, show the actual date
                    return new Date(timestamp).toLocaleDateString() + ' ' + new Date(timestamp).toLocaleTimeString();
                }
            }
            
            if (diff < 60) {
                return diff === 0 ? 'just now' : diff + ' sec ago';
            }
            
            const mins = Math.floor(diff / 60);
            if (mins < 60) {
                return mins + ' min ago';
            }
            
            const hours = Math.floor(mins / 60);
            if (hours < 24) {
                return hours + ' hour' + (hours > 1 ? 's' : '') + ' ago';
            }
            
            const days = Math.floor(hours / 24);
            if (days < 7) {
                return days + ' day' + (days > 1 ? 's' : '') + ' ago';
            }
            
            // For older dates, show formatted date
            return new Date(timestamp).toLocaleDateString() + ' ' + new Date(timestamp).toLocaleTimeString();
        }
        
        // Update recent activity times and setup tooltips
        function updateRecentActivityTimes() {
            const tooltip = document.getElementById('recent-activity-tooltip');
            let active = false;
            
            document.querySelectorAll('.recent-activity-item').forEach(function(item) {
                const timestamp = parseInt(item.dataset.timestamp);
                if (timestamp && timestamp !== 'null') {
                    const timeStr = timeAgo(timestamp);
                    item.dataset.timeAgo = timeStr;
                    
                    // Update the visible time display
                    const timeDisplay = item.querySelector('.time-display');
                    if (timeDisplay) {
                        timeDisplay.textContent = timeStr;
                    }
                    
                    // Setup hover events for this item (for detailed tooltip)
                    const hoverElement = item.querySelector('.recent-activity-hover');
                    if (hoverElement) {
                        // Remove existing listeners to avoid duplicates
                        hoverElement.removeEventListener('mouseenter', hoverElement._mouseEnterHandler);
                        hoverElement.removeEventListener('mousemove', hoverElement._mouseMoveHandler);
                        hoverElement.removeEventListener('mouseleave', hoverElement._mouseLeaveHandler);
                        
                        // Create new handlers
                        hoverElement._mouseEnterHandler = function(e) {
                            const detailedTime = new Date(timestamp).toLocaleString();
                            tooltip.textContent = detailedTime;
                            tooltip.style.display = 'block';
                            tooltip.style.opacity = '1';
                            active = true;
                        };
                        
                        hoverElement._mouseMoveHandler = function(e) {
                            if (active) {
                                const offsetX = 18;
                                const offsetY = 8;
                                tooltip.style.left = (e.clientX + offsetX) + 'px';
                                tooltip.style.top = (e.clientY + offsetY) + 'px';
                            }
                        };
                        
                        hoverElement._mouseLeaveHandler = function() {
                            tooltip.style.display = 'none';
                            tooltip.style.opacity = '0';
                            active = false;
                        };
                        
                        // Add new listeners
                        hoverElement.addEventListener('mouseenter', hoverElement._mouseEnterHandler);
                        hoverElement.addEventListener('mousemove', hoverElement._mouseMoveHandler);
                        hoverElement.addEventListener('mouseleave', hoverElement._mouseLeaveHandler);
                    }
                }
            });
        }
        
        // Initialize time calculations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateRecentActivityTimes();
            
            // Update times every minute
            setInterval(updateRecentActivityTimes, 60000);
        });

        // --- MODAL STYLE SWITCHER REMOVED ---

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
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Response is not JSON');
        }
        return response.json();
    })
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
        alert('An error occurred while sending the announcement. Please check the email configuration and try again.');
        document.getElementById('announcementForm').classList.remove('hidden');
        document.getElementById('announcementProgress').classList.add('hidden');
    });
});
    </script>
</body>
</html>
