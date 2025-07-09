<?php
// logs.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    include 'error_page.php';
    exit();
}

// AJAX endpoint for logs data
if (isset($_GET['fetch_logs'])) {
    header('Content-Type: application/json');
    $logs = [];
    $sql = "SELECT a.id, a.user_id, a.action, a.details, a.ip_address, a.timestamp, u.first_name, u.last_name, u.username 
            FROM audit_trail a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.timestamp DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = [
                'id' => (int)$row['id'],
                'timestamp' => $row['timestamp'],
                'user' => $row['username'] ?? $row['user_id'],
                'user_name' => isset($row['first_name']) && isset($row['last_name']) && ($row['first_name'] || $row['last_name'])
                    ? trim($row['first_name'] . ' ' . $row['last_name'])
                    : ($row['username'] ?? $row['user_id']),
                'action' => $row['action'],
                'level' => 'INFO', // Default to INFO
                'details' => $row['details'] ?? '',
                'ip_address' => $row['ip_address'] ?? ''
            ];
        }
    }
    echo json_encode($logs);
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
$system_title = 'Resident Information and Certification Management System';
$title_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='system_title' LIMIT 1");
if ($title_result && $title_row = $title_result->fetch_assoc()) {
    if (!empty($title_row['setting_value'])) {
        $system_title = $title_row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/css/tabulator.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/js/tabulator.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        .custom-scrollbar { scrollbar-width: thin; scrollbar-color: #2563eb #353535; padding-right: 6px; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #2563eb; border-radius: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #353535; }
        .custom-scrollbar { overflow-y: scroll; }
        .custom-scrollbar::-webkit-scrollbar { background: #353535; }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 10px 20px -5px #0002; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        .sidebar-border { border-right: 1px solid #e5e7eb; }
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
            $barangay_logo = 'img/logo.png';
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
            $peopleActive = navActive(['individuals.php']);
            $peopleId = 'peopleSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $peopleActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $peopleId; ?>')">
                    <i class="fas fa-users"></i> People Management <i class="fas fa-chevron-down ml-auto"></i>
                </button>
                <div id="<?php echo $peopleId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out dropdown-closed">
                    <?php echo navLink('individuals.php', 'fas fa-user', 'Individuals', navActive('individuals.php'));
                    ?>
                </div>
            </div>
            <?php
            $docsActive = navActive(['certificate.php', 'reports.php', 'issued_documents.php']);
            $docsId = 'docsSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $docsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $docsId; ?>')">
                    <i class="fas fa-file-alt"></i> Barangay Documents <i class="fas fa-chevron-down ml-auto"></i>
                </button>
                <div id="<?php echo $docsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out dropdown-closed">
                    <?php echo navLink('certificate.php', 'fas fa-stamp', 'Issue Certificate', navActive('certificate.php'));
                    ?>
                    <?php echo navLink('reports.php', 'fas fa-chart-bar', 'Reports', navActive('reports.php'));
                    ?>
                    <?php echo navLink('issued_documents.php', 'fas fa-history', 'Issued Documents', navActive('issued_documents.php'));
                    ?>
                </div>
            </div>
            <?php
            $settingsActive = navActive(['officials.php', 'settings.php', 'logs.php']);
            $settingsId = 'settingsSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $settingsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $settingsId; ?>')">
                    <i class="fas fa-cogs"></i> System Settings <i class="fas fa-chevron-down ml-auto"></i>
                </button>
                <div id="<?php echo $settingsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out dropdown-closed">
                    <?php echo navLink('officials.php', 'fas fa-user-tie', 'Officials', navActive('officials.php'));
                    ?>
                    <?php echo navLink('settings.php', 'fas fa-cog', 'General Settings', navActive('settings.php'));
                    ?>
                    <?php echo navLink('logs.php', 'fas fa-clipboard-list', 'Logs', navActive('logs.php'));
                    ?>
                </div>
            </div>
        </nav>
    </div>
    <!-- Overlay -->
    <div id="sidepanelOverlay" class="fixed inset-0 bg-black bg-opacity-30 z-30 hidden"></div>
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 z-30 bg-white shadow flex items-center justify-between h-16 px-4 md:px-8">
        <div class="flex items-center gap-2">
            <button id="menuBtn" class="h-8 w-8 mr-2 flex items-center justify-center text-blue-700 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <span class="font-bold text-lg text-blue-700"><?php echo htmlspecialchars($system_title); ?></span>
        </div>
        <div class="relative flex items-center gap-2">
            <span class="hidden sm:inline text-gray-700 font-medium">Welcome, <?php echo htmlspecialchars($user_full_name); ?></span>
            <button id="userDropdownBtn" class="focus:outline-none flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-100">
                <i class="fas fa-user-circle text-2xl text-gray-600"></i>
                <i class="fas fa-chevron-down text-sm text-gray-600"></i>
            </button>
            <div id="userDropdownMenu" class="dropdown-menu">
                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg mx-2 my-1">
                    <i class="fas fa-user mr-2"></i>Profile
                </a>
                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg mx-2 my-1">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    <!-- Main Content -->
    <main class="flex-1 pt-16">
        <div class="p-6 max-w-7xl mx-auto">
            <!-- Logs Header and Filters -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">System Logs</h1>
                    <p class="text-gray-600">Monitor system activities and user actions</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="exportLogs()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Export Logs
                    </button>
                    <button onclick="clearOldLogs()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Clear Old Logs
                    </button>
                </div>
            </div>

            <!-- Log Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-list text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Logs</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalLogs">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-info-circle text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Info Logs</p>
                            <p class="text-2xl font-bold text-gray-900" id="infoLogs">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-yellow-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Warnings</p>
                            <p class="text-2xl font-bold text-gray-900" id="warningLogs">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-red-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Errors</p>
                            <p class="text-2xl font-bold text-gray-900" id="errorLogs">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Controls -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Logs</label>
                        <div class="relative">
                            <input type="text" id="searchLogs" placeholder="Search by user, action, or details..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Log Level</label>
                        <select id="logLevelFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Levels</option>
                            <option value="INFO">Info</option>
                            <option value="WARNING">Warning</option>
                            <option value="ERROR">Error</option>
                            <option value="DEBUG">Debug</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <select id="dateRangeFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                        <select id="userFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Users</option>
                        </select>
                    </div>
                </div>
                <div id="customDateRange" class="mt-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                            <input type="date" id="fromDate" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                            <input type="date" id="toDate" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">System Activity Logs</h3>
                </div>
                <div class="overflow-x-auto">
                    <div id="logsTable"></div>
                </div>
            </div>

            <!-- Log Details Modal -->
            <div id="logModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                        <div class="flex items-center justify-between p-6 border-b">
                            <h3 class="text-lg font-semibold">Log Details</h3>
                            <button onclick="closeLogModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="p-6" id="logDetails">
                            <!-- Log details will be populated here -->
                        </div>
                        <div class="flex justify-end p-6 border-t">
                            <button onclick="closeLogModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Sidebar functionality
        const sidepanel = document.getElementById('sidepanel');
        const overlay = document.getElementById('sidepanelOverlay');
        const menuBtn = document.getElementById('menuBtn');
        const closeSidepanel = document.getElementById('closeSidepanel');
        
        function openSidepanel() {
            sidepanel.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }
        
        function closeSidepanelFunc() {
            sidepanel.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
        
        menuBtn.addEventListener('click', openSidepanel);
        closeSidepanel.addEventListener('click', closeSidepanelFunc);
        overlay.addEventListener('click', closeSidepanelFunc);
        
        // Dropdown functionality
        function toggleDropdown(id) {
            const elements = document.querySelectorAll('[id$="SubNav"]');
            elements.forEach(el => {
                if (el.id !== id) {
                    if (el.classList.contains('dropdown-open')) {
                        el.classList.remove('dropdown-open');
                        el.classList.add('dropdown-closed');
                    }
                }
            });
            const targetEl = document.getElementById(id);
            if (targetEl) {
                if (targetEl.classList.contains('dropdown-open')) {
                    targetEl.classList.remove('dropdown-open');
                    targetEl.classList.add('dropdown-closed');
                } else {
                    targetEl.classList.remove('dropdown-closed');
                    targetEl.classList.add('dropdown-open');
                }
            }
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

        // Logs Management JavaScript
        let logsTable;
        let logsData = [];
        
        // Fetch logs from database
        function fetchLogs() {
            fetch('logs.php?fetch_logs=1')
                .then(response => response.json())
                .then(data => {
                    logsData = data;
                    if (logsTable) {
                        logsTable.setData(logsData);
                    } else {
                        initializeLogsTable();
                    }
                    updateLogStatistics();
                    populateUserFilter();
                })
                .catch(error => {
                    console.error('Error fetching logs:', error);
                });
        }

        // Initialize logs table
        function initializeLogsTable() {
            logsTable = new Tabulator("#logsTable", {
                data: logsData,
                layout: "fitColumns",
                responsiveLayout: "hide",
                pagination: "local",
                paginationSize: 25,
                paginationSizeSelector: [10, 25, 50, 100],
                movableColumns: true,
                resizableRows: false,
                initialSort: [
                    {column: "timestamp", dir: "desc"}
                ],
                columns: [
                    {
                        title: "Date", 
                        field: "timestamp", 
                        width: 120,
                        formatter: function(cell) {
                            const date = new Date(cell.getValue());
                            return date.toLocaleDateString();
                        }
                    },
                    {
                        title: "Time", 
                        field: "timestamp", 
                        width: 120,
                        formatter: function(cell) {
                            const date = new Date(cell.getValue());
                            return date.toLocaleTimeString();
                        }
                    },
                    {
                        title: "User", 
                        field: "user_name", 
                        width: 150,
                        formatter: function(cell) {
                            return `<span class="font-medium">${cell.getValue()}</span>`;
                        }
                    },
                    {
                        title: "Action", 
                        field: "action", 
                        width: 150,
                        formatter: function(cell) {
                            const action = cell.getValue() || '';
                            let badgeClass = 'bg-blue-100 text-blue-800';
                            if (action.toUpperCase().includes('DELETE')) badgeClass = 'bg-red-100 text-red-800';
                            else if (action.toUpperCase().includes('CREATE')) badgeClass = 'bg-green-100 text-green-800';
                            else if (action.toUpperCase().includes('UPDATE')) badgeClass = 'bg-yellow-100 text-yellow-800';
                            else if (action.toUpperCase().includes('LOGIN')) badgeClass = 'bg-purple-100 text-purple-800';
                            else if (action.toUpperCase().includes('LOGOUT')) badgeClass = 'bg-gray-100 text-gray-800';
                            
                            return `<span class="px-2 py-1 rounded-full text-xs font-medium ${badgeClass}">${action}</span>`;
                        }
                    },
                    {
                        title: "Level", 
                        field: "level", 
                        width: 100,
                        formatter: function(cell) {
                            const level = cell.getValue();
                            let badgeClass = 'bg-gray-100 text-gray-800';
                            if (level === 'ERROR') badgeClass = 'bg-red-100 text-red-800';
                            else if (level === 'WARNING') badgeClass = 'bg-yellow-100 text-yellow-800';
                            else if (level === 'INFO') badgeClass = 'bg-green-100 text-green-800';
                            else if (level === 'DEBUG') badgeClass = 'bg-blue-100 text-blue-800';
                            
                            return `<span class="px-2 py-1 rounded-full text-xs font-medium ${badgeClass}">${level}</span>`;
                        }
                    }
                ],
                rowClick: function(e, row) {
                    viewLogDetails(row.getData().id);
                }
            });
        }

        // Update statistics
        function updateLogStatistics() {
            const total = logsData.length;
            const info = logsData.filter(log => log.level === 'INFO').length;
            const warnings = logsData.filter(log => log.level === 'WARNING').length;
            const errors = logsData.filter(log => log.level === 'ERROR').length;

            document.getElementById('totalLogs').textContent = total;
            document.getElementById('infoLogs').textContent = info;
            document.getElementById('warningLogs').textContent = warnings;
            document.getElementById('errorLogs').textContent = errors;
        }

        // Populate user filter with actual users from logs
        function populateUserFilter() {
            const userFilter = document.getElementById('userFilter');
            const users = [...new Set(logsData.map(log => log.user_name))].filter(user => user);
            
            // Clear existing options except "All Users"
            userFilter.innerHTML = '<option value="">All Users</option>';
            
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user;
                option.textContent = user;
                userFilter.appendChild(option);
            });
        }

        // Search functionality
        function setupSearchAndFilters() {
            const searchInput = document.getElementById('searchLogs');
            const levelFilter = document.getElementById('logLevelFilter');
            const dateRangeFilter = document.getElementById('dateRangeFilter');
            const userFilter = document.getElementById('userFilter');

            function applyFilters() {
                let filteredData = logsData;

                // Search filter
                const searchTerm = searchInput.value.toLowerCase();
                if (searchTerm) {
                    filteredData = filteredData.filter(log => 
                        (log.user_name && log.user_name.toLowerCase().includes(searchTerm)) ||
                        (log.action && log.action.toLowerCase().includes(searchTerm)) ||
                        (log.details && log.details.toLowerCase().includes(searchTerm))
                    );
                }

                // Level filter
                const level = levelFilter.value;
                if (level) {
                    filteredData = filteredData.filter(log => log.level === level);
                }

                // User filter
                const user = userFilter.value;
                if (user) {
                    filteredData = filteredData.filter(log => log.user_name === user);
                }

                // Date range filter
                const dateRange = dateRangeFilter.value;
                if (dateRange) {
                    const now = new Date();
                    let startDate;

                    switch(dateRange) {
                        case 'today':
                            startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                            break;
                        case 'week':
                            startDate = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                            break;
                        case 'month':
                            startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                            break;
                        case 'custom':
                            const fromDate = document.getElementById('fromDate').value;
                            const toDate = document.getElementById('toDate').value;
                            if (fromDate && toDate) {
                                filteredData = filteredData.filter(log => {
                                    const logDate = new Date(log.timestamp);
                                    return logDate >= new Date(fromDate) && logDate <= new Date(toDate + ' 23:59:59');
                                });
                            }
                            break;
                    }

                    if (startDate && dateRange !== 'custom') {
                        filteredData = filteredData.filter(log => new Date(log.timestamp) >= startDate);
                    }
                }

                logsTable.setData(filteredData);
                updateFilteredStatistics(filteredData);
            }

            searchInput.addEventListener('input', applyFilters);
            levelFilter.addEventListener('change', applyFilters);
            dateRangeFilter.addEventListener('change', applyFilters);
            userFilter.addEventListener('change', applyFilters);
            document.getElementById('fromDate').addEventListener('change', applyFilters);
            document.getElementById('toDate').addEventListener('change', applyFilters);

            // Custom date range toggle
            dateRangeFilter.addEventListener('change', function() {
                const customDateRange = document.getElementById('customDateRange');
                if (this.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                }
            });
        }

        // Update statistics for filtered data
        function updateFilteredStatistics(data) {
            const total = data.length;
            const info = data.filter(log => log.level === 'INFO').length;
            const warnings = data.filter(log => log.level === 'WARNING').length;
            const errors = data.filter(log => log.level === 'ERROR').length;

            document.getElementById('totalLogs').textContent = total;
            document.getElementById('infoLogs').textContent = info;
            document.getElementById('warningLogs').textContent = warnings;
            document.getElementById('errorLogs').textContent = errors;
        }

        // View log details
        function viewLogDetails(logId) {
            const log = logsData.find(l => l.id === logId);
            if (!log) return;

            const modal = document.getElementById('logModal');
            const detailsContainer = document.getElementById('logDetails');

            const levelBadge = {
                'INFO': 'bg-green-100 text-green-800',
                'WARNING': 'bg-yellow-100 text-yellow-800',
                'ERROR': 'bg-red-100 text-red-800',
                'DEBUG': 'bg-blue-100 text-blue-800'
            }[log.level] || 'bg-gray-100 text-gray-800';

            detailsContainer.innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Timestamp</label>
                            <p class="text-sm text-gray-900">${new Date(log.timestamp).toLocaleString()}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Log Level</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${levelBadge}">
                                ${log.level}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                            <p class="text-sm text-gray-900">${log.user_name} (ID: ${log.user})</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                            <p class="text-sm text-gray-900">${log.action}</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">${log.ip_address || 'N/A'}</code>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional Details</label>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-sm text-gray-700">${log.details || 'No additional details'}</p>
                        </div>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
        }

        // Close log modal
        function closeLogModal() {
            document.getElementById('logModal').classList.add('hidden');
        }

        // Export logs
        function exportLogs() {
            showNotification('Preparing log export...', 'info');
            
            setTimeout(() => {
                // Create CSV content
                const headers = ['Timestamp', 'User', 'Action', 'Level', 'Details', 'IP Address'];
                const csvContent = [
                    headers.join(','),
                    ...logsData.map(log => [
                        log.timestamp,
                        `"${log.user_name}"`,
                        `"${log.action}"`,
                        log.level,
                        `"${(log.details || '').replace(/"/g, '""')}"`,
                        log.ip_address || ''
                    ].join(','))
                ].join('\n');

                // Create and download file
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `system_logs_${new Date().toISOString().split('T')[0]}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                showNotification('Logs exported successfully!', 'success');
            }, 1000);
        }

        // Clear old logs
        function clearOldLogs() {
            if (confirm('Are you sure you want to clear logs older than 30 days? This action cannot be undone.')) {
                showNotification('Clearing old logs...', 'info');
                
                // Make AJAX request to clear logs
                fetch('logs.php?clear_old_logs=1', {method: 'POST'})
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(`Cleared ${data.count} old log entries.`, 'success');
                            fetchLogs(); // Refresh the logs
                        } else {
                            showNotification('Error clearing logs.', 'error');
                        }
                    })
                    .catch(() => {
                        showNotification('Error clearing logs.', 'error');
                    });
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = {
                'success': 'bg-green-500',
                'error': 'bg-red-500',
                'warning': 'bg-yellow-500',
                'info': 'bg-blue-500'
            }[type] || 'bg-blue-500';

            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.remove('translate-x-full', 'opacity-0');
            }, 100);

            setTimeout(() => {
                notification.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Close modal when clicking outside
        document.getElementById('logModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogModal();
            }
        });

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetchLogs();
            setupSearchAndFilters();
        });
    </script>
</body>
</html>
