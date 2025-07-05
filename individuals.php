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
    <title>Individuals - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #2563eb #353535;
            padding-right: 6px;
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
        .custom-scrollbar { overflow-y: scroll; }
        .custom-scrollbar::-webkit-scrollbar { background: #353535; }
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        /* --- Tabulator cell centering fix (revised) --- */
        .tabulator .tabulator-cell.tab-center {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center !important;
            vertical-align: middle !important;
            height: 100%;
        }
        .tabulator .tabulator-header .tabulator-col {
            text-align: center !important;
        }
        .tabulator .tabulator-cell {
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* Custom style for select dropdown options */
        select {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #22223b;
            border-radius: 0.375rem;
            transition: border-color 0.2s;
        }
        select:focus {
            border-color: #2563eb;
            outline: none;
            box-shadow: 0 0 0 2px #2563eb33;
        }
        select option {
            background: #f1f5f9;
            color: #22223b;
            font-size: 0.97em;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Add Resident Modal -->
    <div id="addResidentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white shadow-lg w-full max-w-md mx-4 p-4 relative scale-95 transition-transform duration-300 border border-gray-300" style="border-radius:0; box-shadow:0 8px 32px 0 rgba(60,60,60,0.10); font-size:0.92rem;">
            <button id="closeAddResidentModal" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-2xl focus:outline-none" title="Close">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-lg font-bold mb-3 text-blue-700 text-center w-full">Add New Resident</h2>
            <form id="addResidentForm" class="space-y-3">
                <hr class="mb-2 border-gray-300">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold mb-1">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" required class="w-full border rounded px-2 py-1.5 text-sm focus:ring focus:ring-blue-200" placeholder="e.g., Juan">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Middle Name</label>
                        <input type="text" name="middle_name" class="w-full border rounded px-2 py-1.5 text-sm focus:ring focus:ring-blue-200" placeholder="(optional)">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" required class="w-full border rounded px-2 py-1.5 text-sm focus:ring focus:ring-blue-200" placeholder="e.g., Dela Cruz">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Suffix</label>
                        <input type="text" name="suffix" class="w-full border rounded px-2 py-1.5 text-sm focus:ring focus:ring-blue-200" placeholder="e.g., III, Jr., Sr., II">
                    </div>
                </div>
                <div class="col-span-1 md:col-span-2"><hr class="my-2 border-gray-300"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold mb-1">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" required class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="">Select...</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Birthdate <span class="text-red-500">*</span></label>
                        <input type="date" name="birthdate" required class="w-full border rounded px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Civil Status <span class="text-red-500">*</span></label>
                        <select name="civil_status" required class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="">Select...</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Annulled">Annulled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Blood Type</label>
                        <select name="blood_type" class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="">Select...</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="Unknown">Unknown</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Religion <span class="text-red-500">*</span></label>
                        <select name="religion" required class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="">Select...</option>
                            <option value="Roman Catholic">Roman Catholic</option>
                            <option value="Iglesia ni Cristo">Iglesia ni Cristo</option>
                            <option value="Evangelical (Born Again)">Evangelical (Born Again)</option>
                            <option value="Seventh-day Adventist">Seventh-day Adventist</option>
                            <option value="Jehovah’s Witnesses">Jehovah’s Witnesses</option>
                            <option value="Baptist">Baptist</option>
                            <option value="United Church of Christ in the Philippines (UCCP)">United Church of Christ in the Philippines (UCCP)</option>
                            <option value="Islam (Muslim)">Islam (Muslim)</option>
                            <option value="Aglipayan / Philippine Independent Church">Aglipayan / Philippine Independent Church</option>
                            <option value="Pentecostal">Pentecostal</option>
                            <option value="Methodist">Methodist</option>
                            <option value="Lutheran">Lutheran</option>
                            <option value="Orthodox Christian">Orthodox Christian</option>
                            <option value="Church of Jesus Christ of Latter-day Saints (Mormon)">Church of Jesus Christ of Latter-day Saints (Mormon)</option>
                            <option value="Buddhist">Buddhist</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Judaism (Jewish)">Judaism (Jewish)</option>
                            <option value="No Religion / Atheist / Agnostic">No Religion / Atheist / Agnostic</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Residing Purok <span class="text-red-500">*</span></label>
                        <select name="purok_id" id="purokSelect" required class="w-full border rounded px-2 py-1.5 text-sm">
                            <option value="">Loading...</option>
                        </select>
                    </div>
                </div>
                <hr class="my-2 border-gray-300">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-1">
                    <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="is_pwd" value="1" class="accent-blue-600 scale-90">PWD</label>
                    <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="is_voter" value="1" class="accent-blue-600 scale-90">Voter</label>
                    <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="is_4ps" value="1" class="accent-blue-600 scale-90">4Ps</label>
                    <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="is_pregnant" value="1" class="accent-blue-600 scale-90">Pregnant</label>
                    <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="is_solo_parent" value="1" class="accent-blue-600 scale-90">Solo Parent</label>
                </div>
                <div class="col-span-1 md:col-span-2"><hr class="my-2 border-gray-300"></div>
                <div class="mt-4"></div>
                <div class="flex justify-end gap-2 mt-2">
                    <button type="button" id="cancelAddResident" class="px-3 py-1.5 rounded bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold text-sm">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 rounded bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm">Save Resident</button>
                </div>
                <div id="addResidentMsg" class="mt-2 text-center text-sm"></div>
            </form>
        </div>
    </div>
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
                    <i class="fas fa-users"></i> People Management <i class="fas fa-chevron-down ml-auto"></i>
                </button>
                <div id="<?php echo $peopleId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $peopleActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php
                    echo navLink('individuals.php', 'fas fa-user', 'Individuals', navActive('individuals.php'));
                    echo navLink('households.php', 'fas fa-home', 'Households', navActive('households.php'));
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
                <div id="<?php echo $docsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $docsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php
                    echo navLink('certificate.php', 'fas fa-certificate', 'Certificates', navActive('certificate.php'));
                    echo navLink('reports.php', 'fas fa-chart-bar', 'Reports', navActive('reports.php'));
                    echo navLink('issued_documents.php', 'fas fa-file-signature', 'Issued Documents', navActive('issued_documents.php'));
                    ?>
                </div>
            </div>
            <?php
            $settingsActive = navActive(['officials.php', 'users.php', 'settings.php', 'logs.php']);
            $settingsId = 'settingsSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $settingsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $settingsId; ?>')">
                    <i class="fas fa-cogs"></i> System Settings <i class="fas fa-chevron-down ml-auto"></i>
                </button>
                <div id="<?php echo $settingsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $settingsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php
                    echo navLink('officials.php', 'fas fa-user-tie', 'Officials', navActive('officials.php'));
                    echo navLink('users.php', 'fas fa-users-cog', 'Users', navActive('users.php'));
                    echo navLink('settings.php', 'fas fa-sliders-h', 'Settings', navActive('settings.php'));
                    echo navLink('logs.php', 'fas fa-clipboard-list', 'Logs', navActive('logs.php'));
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
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="userDropdownMenu" class="dropdown-menu mt-2">
                <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100 text-gray-700"><i class="fas fa-user mr-2"></i>Manage Profile</a>
                <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 text-red-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>
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
                if (el) {
                    if (dropId === id) {
                        el.classList.toggle('dropdown-open');
                        el.classList.toggle('dropdown-closed');
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
        document.addEventListener('click', (e) => {
            if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
    </script>
    <div style="height:64px;"></div>
    <!-- Main content -->
    <div class="flex-1 p-4 md:p-8">
        <div class="flex items-center mb-4">
            <h1 class="text-2xl font-bold">All Residents</h1>
        </div>
        <!-- Action Bar (New Style) -->
        <div class="flex items-center justify-between bg-white p-4 rounded-lg shadow-sm mb-4">
            <div>
                <a href="#" id="add-resident-btn" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white font-semibold shadow-sm transition-all hover:bg-blue-700 focus:ring-4 focus:ring-blue-300">
                    <i class="fas fa-plus"></i>
                    <span>Add New Resident</span>
                </a>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" placeholder="Search by name..." class="rounded-lg border border-gray-300 outline outline-2 outline-gray-300 pl-10 pr-4 py-2 focus:border-blue-500 focus:ring-blue-500 focus:outline-gray-400">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <a href="#" id="advanced-filter-btn" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 font-semibold shadow-sm transition-all hover:bg-gray-50">
                    <i class="fas fa-filter"></i>
                    <span>Filters</span>
                </a>
                <a href="#" id="export-btn" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 font-semibold shadow-sm transition-all hover:bg-gray-50">
                    <i class="fas fa-file-export"></i>
                    <span>Export</span>
                </a>
            </div>
        </div>
        <!-- Residents Table -->
        <div id="residents-table" class="bg-white rounded-lg shadow overflow-x-auto w-full"></div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/js/tabulator.min.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/css/tabulator.min.css" crossorigin="anonymous" />
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var table = new Tabulator("#residents-table", {
                ajaxURL: "fetch_individuals.php",
                ajaxConfig: "GET",
                layout: "fitColumns", // ensures columns fill the table equally
                height: 500,
                responsiveLayout: true,
                pagination: "local",
                paginationSize: 10,
                columnDefaults: {
                    resizable: false,
                },
                columns: [
                    {title: "First Name", field: "first_name", headerSort: false, hozAlign: "center", vertAlign: "middle", cssClass: "tab-center"},
                    {title: "Middle Name", field: "middle_name", headerSort: false, hozAlign: "center", vertAlign: "middle", cssClass: "tab-center"},
                    {title: "Last Name", field: "last_name", headerSort: false, hozAlign: "center", vertAlign: "middle", cssClass: "tab-center"},
                    {title: "Suffix", field: "suffix", headerSort: false, hozAlign: "center", vertAlign: "middle", cssClass: "tab-center", width: 60},
                    {title: "Gender", field: "gender", headerSort: false, hozAlign: "center", vertAlign: "middle", cssClass: "tab-center", width: 80},
                    {title: "Birthdate", field: "birthdate", headerSort: false, hozAlign: "center", vertAlign: "middle", cssClass: "tab-center", width: 110},
                    {title: "Residing Purok", field: "purok", headerSort: false, hozAlign: "center", vertAlign: "middle", cssClass: "tab-center", width: 140,
                        formatter: function(cell) {
                            // Only show the word 'Purok' and the number, hide anything in parentheses
                            var value = cell.getValue() || '';
                            // Remove anything in parentheses and trim
                            value = value.replace(/\s*\([^)]*\)/g, '').trim();
                            return value;
                        }
                    },
                    {title: "Action", field: "action", headerSort: false, formatter: function(cell, formatterParams, onRendered) {
                        return `
                            <div style=\"width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; margin: 0; padding: 0;\">
                                <a href=\"#\" title=\"View\" class=\"text-blue-600 hover:text-blue-800 p-2 rounded-full transition m-0\"><i class=\"fas fa-eye\"></i></a>
                                <span class=\"w-px h-5 bg-gray-300 mx-1 m-0\"></span>
                                <a href=\"#\" title=\"Edit\" class=\"text-green-600 hover:text-green-800 p-2 rounded-full transition m-0\"><i class=\"fas fa-pen\"></i></a>
                                <span class=\"w-px h-5 bg-gray-300 mx-1 m-0\"></span>
                                <a href=\"#\" title=\"Delete\" class=\"text-red-600 hover:text-red-800 p-2 rounded-full transition m-0\"><i class=\"fas fa-trash\"></i></a>
                            </div>
                        `;
                    }, hozAlign: "center", vertAlign: "middle", cssClass: "tab-center action-bg-gray", width: 180, frozen: true, resizable: false}
                ],
                headerFilterPlaceholder: "",
                tooltips: false,
                // removed renderComplete for More button
                placeholder: function(){
                    return `
                        <div class=\"flex flex-col items-center justify-center h-96 w-full text-center text-gray-500\">
                            <i class='fas fa-users text-6xl mb-4 text-blue-400'></i>
                            <div class=\"text-2xl font-semibold mb-2\">No Residents Found</div>
                            <div class=\"mb-4\">Get started by <a href=\"#\" id=\"add-resident-link-empty\" class=\"text-blue-600 hover:underline font-semibold\">adding the first resident</a> to the system.</div>
                        </div>
                    `;
                },
                rowFormatter: function(row) {
                    // Add center alignment to all cells
                    row.getElement().querySelectorAll('.tabulator-cell.tab-center').forEach(function(cell) {
                        cell.style.textAlign = 'center';
                        cell.style.verticalAlign = 'middle';
                    });
                }
            });
        });
        </script>
    </div>
    <script>
        // Add event listener for the placeholder link to open the Add Resident modal
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'add-resident-link-empty') {
                e.preventDefault();
                // Trigger the same action as the main Add New Resident button
                var btn = document.getElementById('add-resident-btn');
                if (btn) btn.click();
            }
        });
    </script>
    <script>
// Modal open/close logic
function openAddResidentModal() {
    const modal = document.getElementById('addResidentModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
        modal.classList.remove('opacity-0');
        const inner = modal.querySelector('div.bg-white');
        if(inner) inner.classList.add('scale-100');
        if(inner) inner.classList.remove('scale-95');
    }, 10);
}
function closeAddResidentModal() {
    const modal = document.getElementById('addResidentModal');
    modal.classList.remove('opacity-100');
    modal.classList.add('opacity-0');
    const inner = modal.querySelector('div.bg-white');
    if(inner) inner.classList.remove('scale-100');
    if(inner) inner.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('addResidentForm').reset();
        document.getElementById('addResidentMsg').textContent = '';
    }, 300);
}
document.getElementById('add-resident-btn').addEventListener('click', function(e) {
    e.preventDefault();
    openAddResidentModal();
});
document.getElementById('closeAddResidentModal').addEventListener('click', closeAddResidentModal);
document.getElementById('cancelAddResident').addEventListener('click', closeAddResidentModal);
// Also open modal from empty state link
if(document.getElementById('add-resident-link-empty')) {
    document.getElementById('add-resident-link-empty').addEventListener('click', function(e) {
        e.preventDefault();
        openAddResidentModal();
    });
}
// Load purok options via AJAX
function loadPurokOptions() {
    var purokSelect = document.getElementById('purokSelect');
    purokSelect.innerHTML = '<option value="">Loading...</option>';
    fetch('fetch_puroks.php')
        .then(res => res.json())
        .then(data => {
            purokSelect.innerHTML = '<option value="">Select...</option>';
            data.forEach(function(purok) {
                purokSelect.innerHTML += `<option value="${purok.id}">${purok.name}</option>`;
            });
        })
        .catch(() => {
            purokSelect.innerHTML = '<option value="">Error loading puroks</option>';
        });
}
document.getElementById('add-resident-btn').addEventListener('click', loadPurokOptions);
// Also load puroks when opening from empty state
if(document.getElementById('add-resident-link-empty')) {
    document.getElementById('add-resident-link-empty').addEventListener('click', loadPurokOptions);
}
// AJAX submit
const addResidentForm = document.getElementById('addResidentForm');
addResidentForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(addResidentForm);
    fetch('add_individual.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const msg = document.getElementById('addResidentMsg');
        if(data.success) {
            msg.textContent = 'Resident added successfully!';
            msg.className = 'mt-2 text-center text-green-600 text-sm';
            setTimeout(() => { closeAddResidentModal(); location.reload(); }, 1200);
        } else {
            msg.textContent = data.error || 'Failed to add resident.';
            msg.className = 'mt-2 text-center text-red-600 text-sm';
        }
    })
    .catch(() => {
        const msg = document.getElementById('addResidentMsg');
        msg.textContent = 'Failed to add resident.';
        msg.className = 'mt-2 text-center text-red-600 text-sm';
    });
});
</script>
<script>
// View Resident logic
// This must be after Tabulator and modal logic

document.addEventListener('click', function(e) {
    var target = e.target;
    // If the click is on the eye icon or its parent anchor
    if (target.classList.contains('fa-eye') && target.closest('a')) {
        target = target.closest('a');
    }
    if (target && target.matches('a[title="View"]')) {
        e.preventDefault();
        // Find the row's data-id (Tabulator row data)
        var rowElem = target.closest('.tabulator-row');
        if (!rowElem) return;
        var rowIndex = Array.from(rowElem.parentNode.children).indexOf(rowElem);
        var table = Tabulator.findTable('#residents-table')[0];
        if (!table) return;
        var row = table.getRows()[rowIndex];
        if (!row) return;
        var data = row.getData();
        // Fetch full resident data from backend (in case not all fields are present)
        var residentId = data.resident_id || data.id;
        if (!residentId) return;
        fetch('fetch_individual.php?id=' + encodeURIComponent(residentId))
            .then(res => res.json())
            .then(resp => {
                if (!resp || !resp.success || !resp.resident) {
                    alert('Failed to load resident data.');
                    return;
                }
                var d = resp.resident;
                var form = document.getElementById('addResidentForm');
                // Set values
                form.first_name.value = d.first_name || '';
                form.middle_name.value = d.middle_name || '';
                form.last_name.value = d.last_name || '';
                form.suffix.value = d.suffix || '';
                form.gender.value = d.gender || '';
                form.birthdate.value = d.birthdate || '';
                form.civil_status.value = d.civil_status || '';
                form.blood_type.value = d.blood_type || '';
                form.religion.value = d.religion || '';
                // Purok (wait for options to load)
                var purokSelect = document.getElementById('purokSelect');
                function setPurok() {
                    purokSelect.value = d.purok_id || '';
                }
                if (purokSelect.options.length > 1) {
                    setPurok();
                } else {
                    fetch('fetch_puroks.php').then(res => res.json()).then(puroks => {
                        purokSelect.innerHTML = '<option value="">Select...</option>';
                        puroks.forEach(function(purok) {
                            purokSelect.innerHTML += `<option value="${purok.id}">${purok.name}</option>`;
                        });
                        setPurok();
                    });
                }
                // Checkboxes
                form.is_pwd.checked = !!parseInt(d.is_pwd);
                form.is_voter.checked = !!parseInt(d.is_voter);
                form.is_4ps.checked = !!parseInt(d.is_4ps);
                form.is_pregnant.checked = !!parseInt(d.is_pregnant);
                form.is_solo_parent.checked = !!parseInt(d.is_solo_parent);
                // Disable all fields
                Array.from(form.elements).forEach(function(el) {
                    el.disabled = true;
                });
                // Show modal
                openAddResidentModal();
                // Hide Save, show only Close
                document.getElementById('cancelAddResident').style.display = 'none';
                var saveBtn = form.querySelector('button[type="submit"]');
                if (saveBtn) saveBtn.style.display = 'none';
                // Add a Close button if not present
                var closeBtn = form.querySelector('.view-close-btn');
                if (!closeBtn) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = 'Close';
                    btn.className = 'view-close-btn px-4 py-1.5 rounded bg-gray-400 hover:bg-gray-500 text-white font-bold text-sm ml-2';
                    btn.onclick = function() { closeAddResidentModal(); };
                    form.querySelector('.flex.justify-end').appendChild(btn);
                } else {
                    closeBtn.style.display = '';
                }
            })
            .catch(() => { alert('Failed to load resident data.'); });
    }
});
// Restore form on modal close
function restoreAddResidentForm() {
    var form = document.getElementById('addResidentForm');
    Array.from(form.elements).forEach(function(el) {
        el.disabled = false;
    });
    document.getElementById('cancelAddResident').style.display = '';
    var saveBtn = form.querySelector('button[type="submit"]');
    if (saveBtn) saveBtn.style.display = '';
    var closeBtn = form.querySelector('.view-close-btn');
    if (closeBtn) closeBtn.style.display = 'none';
}
var closeAddResidentModalOrig = closeAddResidentModal;
closeAddResidentModal = function() {
    restoreAddResidentForm();
    closeAddResidentModalOrig();
}
</script>
</body>
</html>