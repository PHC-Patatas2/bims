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
        <div class="flex items-center gap-2">            <button id="menuBtn" class="h-8 w-8 mr-2 flex items-center justify-center text-blue-700 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <span class="font-bold text-lg text-blue-700"><?php echo htmlspecialchars($system_title); ?></span>
        </div>
        <div class="relative flex items-center gap-2">
            <span class="hidden sm:inline text-gray-700 font-medium">Welcome,</span>
            <button id="userDropdownBtn" class="focus:outline-none flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-100">
                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($user_full_name); ?></span>
                <i class="fas fa-chevron-down text-sm text-gray-600"></i>
            </button>
            <div id="userDropdownMenu" class="dropdown-menu mt-2">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-list text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Activity Logs</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalLogs">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-clock text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Today's Activities</p>
                            <p class="text-2xl font-bold text-gray-900" id="todayLogs">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Controls -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Activities</label>
                        <div class="relative">
                            <input type="text" id="searchLogs" placeholder="Search by user or activity..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <select id="dateRangeFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                        <select id="userFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Users</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">System Activity History</h3>
                    <p class="text-sm text-gray-600 mt-1">View all system activities and user actions</p>
                </div>
                <div class="overflow-x-auto" style="height: 600px;">
                    <div id="logsTable"></div>
                </div>
            </div>

            <!-- Activity Details Modal -->
            <div id="logModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[80vh] overflow-y-auto">
                        <div class="flex items-center justify-between p-6 border-b">
                            <div>
                                <h3 class="text-lg font-semibold">Activity Details</h3>
                                <p class="text-sm text-gray-600">Detailed information about this system activity</p>
                            </div>
                            <button onclick="closeLogModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div class="p-6" id="logDetails">
                            <!-- Log details will be populated here -->
                        </div>
                        <div class="flex justify-end p-6 border-t bg-gray-50">
                            <button onclick="closeLogModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-check mr-2"></i>Got it
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
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
                if (dropId !== id) {
                    const otherEl = document.getElementById(dropId);
                    if (otherEl && otherEl.classList.contains('dropdown-open')) {
                        otherEl.classList.remove('dropdown-open');
                        otherEl.classList.add('dropdown-closed');
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
                paginationSize: 50,
                paginationSizeSelector: [25, 50, 100],
                movableColumns: false,
                resizableRows: false,
                height: "100%",
                initialSort: [
                    {column: "timestamp", dir: "desc"}
                ],
                columns: [
                    {
                        title: "Date & Time", 
                        field: "timestamp", 
                        width: 200,
                        formatter: function(cell) {
                            const date = new Date(cell.getValue());
                            return `
                                <div class="text-sm">
                                    <div class="font-medium">${date.toLocaleDateString()}</div>
                                    <div class="text-gray-500">${date.toLocaleTimeString()}</div>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "User", 
                        field: "user_name", 
                        width: 150,
                        formatter: function(cell) {
                            const userName = cell.getValue() || 'System';
                            return `<span class="font-medium text-blue-700">${userName}</span>`;
                        }
                    },
                    {
                        title: "Activity", 
                        field: "action", 
                        widthGrow: 2,
                        formatter: function(cell) {
                            const action = cell.getValue() || '';
                            let iconClass = 'fas fa-info-circle text-blue-500';
                            let description = action;
                            
                            // Make activities more human-readable
                            if (action.toLowerCase().includes('login')) {
                                iconClass = 'fas fa-sign-in-alt text-green-500';
                                description = 'User logged into the system';
                            } else if (action.toLowerCase().includes('logout')) {
                                iconClass = 'fas fa-sign-out-alt text-gray-500';
                                description = 'User logged out of the system';
                            } else if (action.toLowerCase().includes('create') || action.toLowerCase().includes('add')) {
                                iconClass = 'fas fa-plus-circle text-green-500';
                                description = action.replace(/create|add/gi, 'Added new');
                            } else if (action.toLowerCase().includes('update') || action.toLowerCase().includes('edit')) {
                                iconClass = 'fas fa-edit text-yellow-500';
                                description = action.replace(/update|edit/gi, 'Updated');
                            } else if (action.toLowerCase().includes('delete') || action.toLowerCase().includes('remove')) {
                                iconClass = 'fas fa-trash text-red-500';
                                description = action.replace(/delete|remove/gi, 'Removed');
                            }
                            
                            return `
                                <div class="flex items-center gap-2">
                                    <i class="${iconClass}"></i>
                                    <span class="text-sm">${description}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        title: "Actions", 
                        field: "id", 
                        width: 120,
                        headerSort: false,
                        formatter: function(cell) {
                            return `
                                <button onclick="viewLogDetails(${cell.getValue()})" 
                                        class="bg-blue-500 text-white px-3 py-1 rounded text-xs hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>View Details
                                </button>
                            `;
                        }
                    }
                ]
            });
        }

        // Update statistics
        function updateLogStatistics() {
            const total = logsData.length;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const todayLogs = logsData.filter(log => {
                const logDate = new Date(log.timestamp);
                logDate.setHours(0, 0, 0, 0);
                return logDate.getTime() === today.getTime();
            }).length;

            document.getElementById('totalLogs').textContent = total;
            document.getElementById('todayLogs').textContent = todayLogs;
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
                    }

                    if (startDate) {
                        filteredData = filteredData.filter(log => new Date(log.timestamp) >= startDate);
                    }
                }

                logsTable.setData(filteredData);
                updateFilteredStatistics(filteredData);
            }

            searchInput.addEventListener('input', applyFilters);
            dateRangeFilter.addEventListener('change', applyFilters);
            userFilter.addEventListener('change', applyFilters);
        }
        // Update statistics for filtered data
        function updateFilteredStatistics(data) {
            const total = data.length;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const todayFiltered = data.filter(log => {
                const logDate = new Date(log.timestamp);
                logDate.setHours(0, 0, 0, 0);
                return logDate.getTime() === today.getTime();
            }).length;

            document.getElementById('totalLogs').textContent = total;
            document.getElementById('todayLogs').textContent = todayFiltered;
        }

        // View log details in modal
        function viewLogDetails(logId) {
            const log = logsData.find(l => l.id == logId);
            if (!log) return;

            const detailsHtml = `
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-blue-800 mb-2">When</h4>
                            <p class="text-sm">${new Date(log.timestamp).toLocaleString()}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-green-800 mb-2">Who</h4>
                            <p class="text-sm">${log.user_name || 'System'}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-800 mb-2">What Happened</h4>
                        <p class="text-sm">${log.action || 'No action specified'}</p>
                    </div>
                    ${log.details ? `
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-yellow-800 mb-2">Additional Details</h4>
                            <p class="text-sm">${log.details}</p>
                        </div>
                    ` : ''}
                    ${log.ip_address ? `
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-purple-800 mb-2">Technical Information</h4>
                            <p class="text-sm"><strong>IP Address:</strong> ${log.ip_address}</p>
                            ${log.user_agent ? `<p class="text-sm mt-1"><strong>Browser:</strong> ${log.user_agent}</p>` : ''}
                        </div>
                    ` : ''}
                </div>
            `;

            document.getElementById('logDetails').innerHTML = detailsHtml;
            document.getElementById('logModal').classList.remove('hidden');
        }

        // Close log modal
        function closeLogModal() {
            document.getElementById('logModal').classList.add('hidden');
        }

        // Export logs functionality
        function exportLogs() {
            alert('Export functionality will be implemented soon!');
        }

        // Clear old logs functionality
        function clearOldLogs() {
            if (confirm('Are you sure you want to clear old logs? This action cannot be undone.')) {
                alert('Clear old logs functionality will be implemented soon!');
            }
        }

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetchLogs();
            setupSearchAndFilters();
            
            // Close modal when clicking overlay
            document.getElementById('logModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeLogModal();
                }
            });
        });
    </script>
</body>
</html>
