<?php
// users.php
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
    <title>User Accounts - <?php echo htmlspecialchars($system_title); ?></title>
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
        .dropdown-open { max-height: 500px; opacity: 1; pointer-events: auto; overflow: hidden; }
        .dropdown-closed { max-height: 0; opacity: 0; pointer-events: none; overflow: hidden; }
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
                    <i class="fas fa-users"></i> People Management
                    <i class="fas fa-chevron-down ml-auto text-xs transition-transform duration-300 dropdown-arrow <?php echo $peopleActive ? 'rotate-180' : ''; ?>" data-arrow="<?php echo $peopleId; ?>"></i>
                </button>
                <div id="<?php echo $peopleId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $peopleActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php echo navLink('individuals.php', 'fas fa-user', 'Residents', navActive('individuals.php'), 'rounded'); ?>
                </div>
            </div>

            <?php
            $docsActive = navActive(['certificate.php', 'reports.php', 'issued_documents.php']);
            $docsId = 'docsSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $docsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $docsId; ?>')">
                    <i class="fas fa-file-alt"></i> Barangay Documents
                    <i class="fas fa-chevron-down ml-auto text-xs transition-transform duration-300 dropdown-arrow <?php echo $docsActive ? 'rotate-180' : ''; ?>" data-arrow="<?php echo $docsId; ?>"></i>
                </button>
                <div id="<?php echo $docsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $docsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php echo navLink('certificate.php', 'fas fa-stamp', 'Issue Certificate', navActive('certificate.php'), 'rounded'); ?>
                    <?php echo navLink('reports.php', 'fas fa-chart-bar', 'Generate Reports', navActive('reports.php'), 'rounded'); ?>
                    <?php echo navLink('issued_documents.php', 'fas fa-history', 'Issued Documents Log', navActive('issued_documents.php'), 'rounded'); ?>
                </div>
            </div>

            <?php
            $settingsActive = navActive(['officials.php', 'users.php', 'settings.php', 'logs.php']);
            $settingsId = 'settingsSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $settingsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $settingsId; ?>')">
                    <i class="fas fa-cogs"></i> System Settings
                    <i class="fas fa-chevron-down ml-auto text-xs transition-transform duration-300 dropdown-arrow <?php echo $settingsActive ? 'rotate-180' : ''; ?>" data-arrow="<?php echo $settingsId; ?>"></i>
                </button>
                <div id="<?php echo $settingsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $settingsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php echo navLink('officials.php', 'fas fa-user-tie', 'Officials Management', navActive('officials.php'), 'rounded'); ?>
                    <?php echo navLink('users.php', 'fas fa-users-cog', 'User Accounts', navActive('users.php'), 'rounded'); ?>
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
        <div class="flex items-center gap-2">
            <button id="menuBtn" class="h-8 w-8 mr-2 flex items-center justify-center text-blue-700 focus:outline-none">
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
    <div class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <!-- Header with Add Button -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">User Accounts</h1>
                    <p class="text-gray-600">Manage system user accounts and permissions</p>
                </div>
                <button onclick="openUserModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add User
                </button>
            </div>

            <!-- User Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalUsers">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Active Users</p>
                            <p class="text-2xl font-bold text-gray-900" id="activeUsers">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-yellow-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-user-shield text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Administrators</p>
                            <p class="text-2xl font-bold text-gray-900" id="adminUsers">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search by name, username, or email..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <div class="md:w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select id="roleFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Roles</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Staff">Staff</option>
                            <option value="Viewer">Viewer</option>
                        </select>
                    </div>
                    <div class="md:w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="statusFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">User Accounts</h3>
                </div>
                <div class="overflow-x-auto">
                    <div id="usersTable"></div>
                </div>
            </div>
        </div>
    </div>
    
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

        // Dropdown logic for sidepanel
        function toggleDropdown(id) {
            const dropdowns = ['peopleSubNav', 'docsSubNav', 'settingsSubNav'];
            dropdowns.forEach(function(dropId) {
                const el = document.getElementById(dropId);
                if (el) {
                    if (dropId === id) {
                        el.classList.toggle('dropdown-open');
                        el.classList.toggle('dropdown-closed');
                        const arrow = document.querySelector(`[data-arrow="${id}"]`);
                        if (arrow) arrow.classList.toggle('rotate-180');
                    } else {
                        el.classList.remove('dropdown-open');
                        el.classList.add('dropdown-closed');
                        const arrow = document.querySelector(`[data-arrow="${dropId}"]`);
                        if (arrow) arrow.classList.remove('rotate-180');
                    }
                }
            });
        }

        // Sample users data
        let users = [
            {
                id: 1,
                first_name: 'Admin',
                last_name: 'User',
                username: 'admin',
                email: 'admin@example.com',
                role: 'Administrator',
                status: 'Active',
                created_at: '2024-01-01'
            },
            {
                id: 2,
                first_name: 'Maria',
                last_name: 'Santos',
                username: 'maria.santos',
                email: 'maria@example.com',
                role: 'Staff',
                status: 'Active',
                created_at: '2024-02-15'
            },
            {
                id: 3,
                first_name: 'Juan',
                last_name: 'Cruz',
                username: 'juan.cruz',
                email: 'juan@example.com',
                role: 'Viewer',
                status: 'Inactive',
                created_at: '2024-03-20'
            }
        ];

        let usersTable;

        // Initialize Tabulator table
        function initializeTable() {
            usersTable = new Tabulator("#usersTable", {
                data: users,
                layout: "fitColumns",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [5, 10, 20],
                movableColumns: true,
                resizableColumns: true,
                columns: [
                    {title: "Name", field: "first_name", width: 200, sorter: "string",
                     formatter: function(cell) {
                         const data = cell.getRow().getData();
                         return `${data.first_name} ${data.last_name}`;
                     }
                    },
                    {title: "Username", field: "username", width: 150, sorter: "string"},
                    {title: "Email", field: "email", width: 200, sorter: "string"},
                    {title: "Role", field: "role", width: 120, sorter: "string"},
                    {title: "Status", field: "status", width: 100, sorter: "string",
                     formatter: function(cell) {
                         const status = cell.getValue();
                         const color = status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                         return `<span class="px-2 py-1 rounded-full text-xs ${color}">${status}</span>`;
                     }
                    },
                    {title: "Created", field: "created_at", width: 120, sorter: "date", 
                     formatter: function(cell) {
                         return new Date(cell.getValue()).toLocaleDateString();
                     }
                    },
                    {title: "Actions", formatter: function(cell) {
                        const id = cell.getRow().getData().id;
                        return `
                            <button onclick="editUser(${id})" class="bg-blue-500 text-white px-2 py-1 rounded text-sm hover:bg-blue-600 mr-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="resetUserPassword(${id})" class="bg-yellow-500 text-white px-2 py-1 rounded text-sm hover:bg-yellow-600 mr-1">
                                <i class="fas fa-key"></i>
                            </button>
                            <button onclick="deleteUser(${id})" class="bg-red-500 text-white px-2 py-1 rounded text-sm hover:bg-red-600">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                     }, width: 150, hozAlign: "center", headerSort: false}
                ]
            });
        }

        // Update statistics
        function updateStatistics() {
            const total = users.length;
            const active = users.filter(user => user.status === 'Active').length;
            const admins = users.filter(user => user.role === 'Administrator').length;

            document.getElementById('totalUsers').textContent = total;
            document.getElementById('activeUsers').textContent = active;
            document.getElementById('adminUsers').textContent = admins;
        }

        // Filter functions
        function setupFilters() {
            document.getElementById('searchInput').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                usersTable.setFilter(function(data) {
                    return data.first_name.toLowerCase().includes(searchTerm) ||
                           data.last_name.toLowerCase().includes(searchTerm) ||
                           data.username.toLowerCase().includes(searchTerm) ||
                           data.email.toLowerCase().includes(searchTerm);
                });
            });

            document.getElementById('roleFilter').addEventListener('change', function() {
                const role = this.value;
                if (role) {
                    usersTable.setFilter("role", "=", role);
                } else {
                    usersTable.clearFilter();
                }
            });

            document.getElementById('statusFilter').addEventListener('change', function() {
                const status = this.value;
                if (status) {
                    usersTable.setFilter("status", "=", status);
                } else {
                    usersTable.clearFilter();
                }
            });
        }

        // Mock functions (replace with actual implementations)
        function openUserModal() {
            alert('Add User modal would open here');
        }

        function editUser(id) {
            const user = users.find(u => u.id === id);
            if (user) {
                alert(`Edit user: ${user.first_name} ${user.last_name}`);
            }
        }

        function resetUserPassword(id) {
            const user = users.find(u => u.id === id);
            if (user) {
                alert(`Reset password for: ${user.first_name} ${user.last_name}`);
            }
        }

        function deleteUser(id) {
            const user = users.find(u => u.id === id);
            if (user && confirm(`Are you sure you want to delete ${user.first_name} ${user.last_name}?`)) {
                users = users.filter(u => u.id !== id);
                usersTable.setData(users);
                updateStatistics();
                showNotification('User deleted successfully!', 'success');
            }
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation' : 'info'} mr-2"></i>
                    ${message}
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }

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

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeTable();
            updateStatistics();
            setupFilters();
        });
    </script>
</body>
</html>
