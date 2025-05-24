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

// Fake user info for demo
$username = 'Jennifer De Leon';
$role = 'secretary';
// Fake system title and barangay name
$system_title = 'Barangay Information Management System';
$barangay_name = 'Barangay SUCOL';
// Fake dashboard stats
$total_residents = 1200;
$total_families = 350;
$total_male = 600;
$total_female = 600;
$total_seniors = 150;
$total_pwd = 30;
$total_voters = 900;
$total_4ps = 80;
// Barangay officials from database
$officials = [];
$officials_result = $conn->query("SELECT first_name, middle_initial, last_name, position, photo FROM barangay_officials ORDER BY FIELD(position, 'Punong Barangay', 'Sangguniang Barangay Member'), id");
if ($officials_result) {
    while ($row = $officials_result->fetch_assoc()) {
        // Format name: First M. Last (proper casing)
        $first = ucwords(strtolower($row['first_name']));
        $mi = strtoupper($row['middle_initial']);
        $last = ucwords(strtolower($row['last_name']));
        $name = $first;
        if ($mi) {
            $name .= ' ' . $mi . '.';
        }
        $name .= ' ' . $last;
        $officials[] = [
            'name' => $name,
            'position' => $row['position'],
            'photo' => $row['photo'] ?? 'default.png',
        ];
    }
}
// Fake calendar events
$calendar_events = [
    ['event_date' => date('Y-m-d'), 'title' => 'Barangay Assembly', 'note' => 'Prepare documents'],
    ['event_date' => date('Y-m-d', strtotime('+2 days')), 'title' => 'Clean-up Drive', 'note' => 'Coordinate with volunteers'],
    ['event_date' => date('Y-m-d', strtotime('+5 days')), 'title' => 'Health Check-up', 'note' => 'Invite health workers'],
];
$today = date('Y-m-d');
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
                $activeClass = $isActive ? 'border-l-4 border-blue-500 text-blue-400 font-bold bg-transparent' : 'border-l-4 border-transparent text-white';
                $hoverClass = 'hover:border-blue-400 hover:text-blue-400 hover:bg-transparent';
                echo '<a href="' . $page[0] . '" class="py-2 px-3 rounded-none flex items-center gap-2 ' . $activeClass . ' ' . $hoverClass . '"><i class="' . $page[1] . '"></i> ' . $page[2] . '</a>';
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
                <?php if ($role === 'admin'): ?>
                <a href="system_settings.php" class="block px-4 py-2 hover:bg-gray-100 text-gray-700"><i class="fas fa-cogs mr-2"></i>Customize System</a>
                <?php endif; ?>
                <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 text-red-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 mb-4">
                <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-4 m-2 flex flex-col justify-between min-h-[150px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold mb-0.5"><?php echo $total_residents; ?></div>
                            <div class="mb-2 text-base">Total Residents</div>
                        </div>
                        <i class="fas fa-users icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-4 m-2 flex flex-col justify-between min-h-[150px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold mb-0.5"><?php echo $total_families; ?></div>
                            <div class="mb-2 text-base">Total Families</div>
                        </div>
                        <i class="fas fa-people-roof icon text-6xl opacity-30"></i>
                    </div>
                    <a href="families.php" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-sky-500 to-sky-600 text-white rounded-xl shadow-lg p-4 m-2 flex flex-col justify-between min-h-[150px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold mb-0.5"><?php echo $total_male; ?></div>
                            <div class="mb-2 text-base">Male Residents</div>
                        </div>
                        <i class="fas fa-mars icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?gender=male" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-pink-500 to-pink-600 text-white rounded-xl shadow-lg p-4 m-2 flex flex-col justify-between min-h-[150px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold mb-0.5"><?php echo $total_female; ?></div>
                            <div class="mb-2 text-base">Female Residents</div>
                        </div>
                        <i class="fas fa-venus icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?gender=female" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-yellow-400 to-yellow-500 text-gray-900 rounded-xl shadow-lg p-4 m-2 flex flex-col justify-between min-h-[150px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold mb-0.5"><?php echo $total_seniors; ?></div>
                            <div class="mb-2 text-base">Senior Citizens</div>
                        </div>
                        <i class="fas fa-person-cane icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?senior=1" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-4 m-2 flex flex-col justify-between min-h-[150px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold mb-0.5"><?php echo $total_pwd; ?></div>
                            <div class="mb-2 text-base">PWDs</div>
                        </div>
                        <i class="fas fa-wheelchair icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?pwd=1" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-xl shadow-lg p-4 m-2 flex flex-col justify-between min-h-[150px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold mb-0.5"><?php echo $total_voters; ?></div>
                            <div class="mb-2 text-base">Registered Voters</div>
                        </div>
                        <i class="fas fa-vote-yea icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?voter=1" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="stat-card bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl shadow-lg p-4 m-2 flex flex-col justify-between min-h-[150px]">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold mb-0.5"><?php echo $total_4ps; ?></div>
                            <div class="mb-2 text-base">4Ps Members</div>
                        </div>
                        <i class="fas fa-hand-holding-heart icon text-6xl opacity-30"></i>
                    </div>
                    <a href="individuals.php?fourps=1" class="mt-4 text-blue-100 hover:text-white text-sm font-medium flex items-center gap-1 self-end transition-colors">
                        <span>View more info</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <!-- Two columns: Officials (left), Calendar (right) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Officials Section -->
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6 m-2 min-h-[420px]">
                    <h2 class="text-lg font-bold text-blue-700 mb-2 text-center">Barangay Officials</h2>
                    <?php
                    // Separate captain and kagawads
                    $captain = null;
                    $kagawads = [];
                    $others = [];
                    foreach ($officials as $off) {
                        if (stripos($off['position'], 'Punong Barangay') !== false) {
                            $captain = $off;
                        } elseif (stripos($off['position'], 'Kagawad') !== false || stripos($off['position'], 'Sangguniang Barangay Member') !== false) {
                            $kagawads[] = $off;
                        } else {
                            $others[] = $off;
                        }
                    }
                    ?>
                    <div class="flex flex-col items-center justify-center h-full mb-4">
                        <?php if ($captain): ?>
                        <div class="flex flex-col items-center mb-4">
                            <img src="img/officials/<?php echo htmlspecialchars($captain['photo'] ?? 'default.png'); ?>" class="w-16 h-16 rounded-full border-2 border-blue-700 object-cover mb-1" style="aspect-ratio: 1/1;" alt="<?php echo htmlspecialchars($captain['name']); ?>">
                            <div class="font-bold text-blue-800 text-base"><?php echo htmlspecialchars($captain['name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($captain['position']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php for ($row = 0; $row < 2; $row++): ?>
                        <div class="flex justify-center gap-6 mb-2">
                            <?php for ($col = 0; $col < 4; $col++): 
                                $idx = $row * 4 + $col;
                                if (isset($kagawads[$idx])): $k = $kagawads[$idx]; ?>
                                <div class="flex flex-col items-center">
                                    <img src="img/officials/<?php echo htmlspecialchars($k['photo'] ?? 'default.png'); ?>" class="w-12 h-12 rounded-full border object-cover mb-1" alt="<?php echo htmlspecialchars($k['name']); ?>">
                                    <div class="font-semibold text-gray-800 text-xs text-center"><?php echo htmlspecialchars($k['name']); ?></div>
                                    <div class="text-[11px] text-gray-500 text-center"><?php echo htmlspecialchars($k['position']); ?></div>
                                </div>
                                <?php endif; endfor; ?>
                        </div>
                        <?php endfor; ?>
                        <?php if (!empty($others)): ?>
                        <div class="flex flex-wrap justify-center gap-4 mt-2">
                            <?php foreach ($others as $off): ?>
                            <div class="flex flex-col items-center">
                                <img src="img/officials/<?php echo htmlspecialchars($off['photo'] ?? 'default.png'); ?>" class="w-10 h-10 rounded-full border object-cover mb-1" alt="<?php echo htmlspecialchars($off['name']); ?>">
                                <div class="font-semibold text-gray-800 text-xs text-center"><?php echo htmlspecialchars($off['name']); ?></div>
                                <div class="text-[11px] text-gray-500 text-center"><?php echo htmlspecialchars($off['position']); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Calendar Section Only -->
                <div class="bg-white rounded-lg shadow-lg p-4 md:p-6 m-2 min-h-[420px] flex flex-col">
                    <h2 id="calendarTitle" class="text-lg font-bold text-blue-700 text-center w-full mb-2"></h2>
                    <div id="calendar" class="flex-1"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Dropdown menu
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        userDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdownMenu.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!userDropdownMenu.contains(e.target) && !userDropdownBtn.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
        // Simple calendar rendering (current month, highlight today)
        function renderCalendar() {
            const calendar = document.getElementById('calendar');
            const calendarTitle = document.getElementById('calendarTitle');
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth();
            const today = now.getDate();
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            // Set the calendar title to current month and year
            const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            if (calendarTitle) {
                calendarTitle.textContent = monthNames[month] + ' ' + year;
            }
            // Build table-based calendar
            let html = '<table class="w-full h-full table-fixed border-collapse select-none">';
            html += '<thead><tr class="text-xs font-semibold">';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => html += `<th class="py-1 w-1/7">${d}</th>`);
            html += '</tr></thead><tbody>';
            let day = 1;
            for (let row = 0; row < 6; row++) {
                html += '<tr class="h-[calc(100%/6)]">';
                for (let col = 0; col < 7; col++) {
                    if ((row === 0 && col < firstDay) || day > daysInMonth) {
                        html += '<td class="border border-gray-200 w-1/7 h-[calc(100%/6)] align-top text-center"></td>';
                    } else {
                        const isToday = (day === today);
                        html += `<td class="border border-gray-200 w-1/7 h-[calc(100%/6)] align-middle text-center p-0 ${isToday ? 'bg-blue-600 text-white font-bold rounded' : ''}">
        <div class='flex items-center justify-center h-full w-full'><span class="text-base md:text-lg font-bold">${day}</span></div>
    </td>`;
                        day++;
                    }
                }
                html += '</tr>';
                if (day > daysInMonth) break;
            }
            html += '</tbody></table>';
            calendar.innerHTML = html;
        }
        renderCalendar();
        // Sidepanel open/close (fix: use translate-x-0 for open, -translate-x-full for closed)
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
    </script>
</body>
</html>
