<?php
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

// Fetch system title from system_settings table
$system_title = 'Resident Information and Certification Management System'; // default fallback
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
    <title>Residents - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/css/tabulator.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/js/tabulator.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #2563eb #353535;
            padding-right: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #2563eb; border-radius: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #353535; }
        .custom-scrollbar { overflow-y: scroll; }
        .custom-scrollbar::-webkit-scrollbar { background: #353535; }
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
            $peopleActive = navActive(['individuals.php', 'households.php']);
            $peopleId = 'peopleSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $peopleActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $peopleId; ?>')">
                    <i class="fas fa-users"></i> People Management
                    <i class="fas fa-chevron-down ml-auto text-xs transition-transform duration-300 dropdown-arrow <?php echo $peopleActive ? 'rotate-180' : ''; ?>" data-arrow="<?php echo $peopleId; ?>"></i>
                </button>
                <div id="<?php echo $peopleId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $peopleActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php echo navLink('individuals.php', 'fas fa-user', 'Residents', navActive('individuals.php'), 'rounded'); ?>
                    <?php echo navLink('households.php', 'fas fa-home', 'Households', navActive('households.php'), 'rounded'); ?>
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
            <!-- Top Bar: Page Title Only -->
            <div class="flex items-center mb-6 mt-2">
                <h1 class="text-2xl md:text-3xl font-bold text-blue-900 tracking-tight">Residents</h1>
            </div>

            <!-- Residents Toolbar -->
            <div class="flex items-center justify-between bg-white p-4 rounded-lg shadow-sm mb-4">
                <div>
                    <a href="#" id="add-resident-btn" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white font-semibold shadow-sm transition-all hover:bg-blue-700 focus:ring-4 focus:ring-blue-300">
                        <i class="fas fa-plus"></i>
                        <span>Add New Resident</span>
                    </a>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <input type="text" id="search-input" placeholder="Search by name..." class="rounded-lg border-gray-300 pl-10 pr-4 py-2 focus:border-blue-500 focus:ring-blue-500" autocomplete="off">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <a href="#" id="advanced-filter-btn" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 font-semibold shadow-sm transition-all hover:bg-gray-50">
                        <i class="fas fa-filter"></i>
                        <span>Filters</span>
                    </a>
                    <a href="print_individuals.php?export=excel" id="export-btn" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 font-semibold shadow-sm transition-all hover:bg-gray-50">
                        <i class="fas fa-file-export"></i>
                        <span>Export</span>
                    </a>
                </div>
            </div>
            <!-- Residents Table (Tabulator.js) -->
            <div id="residents-table" class="bg-white rounded-lg shadow-sm overflow-x-auto"></div>
            <script>
            // Fetch residents data from backend (AJAX)
            async function fetchResidents() {
                const response = await fetch('fetch_residents.php');
                if (!response.ok) throw new Error('Failed to fetch residents');
                return await response.json();
            }

            // Tabulator columns for Residents Table
            const columns = [
                {title: "Last Name", field: "last_name"},
                {title: "First Name", field: "first_name"},
                {title: "Middle Name", field: "middle_name"},
                {title: "Gender", field: "gender"},
                {title: "Birthdate", field: "birthdate"},
                {title: "Civil Status", field: "civil_status"},
                {title: "Purok", field: "current_purok"},
                {
                    title: "State",
                    field: "state",
                    formatter: function(cell, formatterParams, onRendered) {
                        // Placeholder: will be set later (e.g., PWD, Married, etc.)
                        return cell.getValue() || "";
                    },
                },
                {title: "Actions", field: "actions", hozAlign: "center", headerSort: false, formatter: function(cell, formatterParams, onRendered) {
                    const id = cell.getRow().getData().id;
                    return `
                        <div class='flex gap-1 justify-center'>
                            <button class='edit-btn text-blue-600 hover:text-blue-800' title='Edit' data-id='${id}'><i class='fas fa-edit'></i></button>
                            <button class='delete-btn text-red-600 hover:text-red-800' title='Delete' data-id='${id}'><i class='fas fa-trash'></i></button>
                            <button class='more-btn text-gray-600 hover:text-gray-800' title='More' data-id='${id}'><i class='fas fa-ellipsis-h'></i></button>
                        </div>
                    `;
                }},
            ];

            // Initialize Tabulator after DOM loads
            document.addEventListener('DOMContentLoaded', async function() {
                let tableData = [];
                try {
                    tableData = await fetchResidents();
                } catch (e) {
                    tableData = [];
                }
                new Tabulator("#residents-table", {
                    data: tableData,
                    layout: "fitDataFill",
                    responsiveLayout: "collapse",
                    columns: columns,
                    pagination: true,
                    paginationSize: 20,
                    movableColumns: true,
                    height: "600px",
                    placeholder: "No residents found."
                });
            });
            </script>
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
        // Dropdown logic for sidepanel (only one open at a time)
        function toggleDropdown(id) {
            const dropdowns = ['peopleSubNav', 'docsSubNav', 'settingsSubNav'];
            dropdowns.forEach(function(dropId) {
                const el = document.getElementById(dropId);
                const arrow = document.querySelector('.dropdown-arrow[data-arrow="' + dropId + '"]');
                if (el) {
                    if (dropId === id) {
                        if (el.classList.contains('dropdown-open')) {
                            el.classList.remove('dropdown-open');
                            el.classList.add('dropdown-closed');
                            if (arrow) arrow.classList.remove('rotate-180');
                        } else {
                            el.classList.remove('dropdown-closed');
                            el.classList.add('dropdown-open');
                            if (arrow) arrow.classList.add('rotate-180');
                        }
                    } else {
                        el.classList.remove('dropdown-open');
                        el.classList.add('dropdown-closed');
                        if (arrow) arrow.classList.remove('rotate-180');
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
        document.addEventListener('click', (e) => {
            if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
    </script>
</body>
</html>
