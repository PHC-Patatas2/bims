<?php
// officials.php
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
    <title>Officials Management - <?php echo htmlspecialchars($system_title); ?></title>
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
                <div id="<?php echo $peopleId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $peopleActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
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
                <div id="<?php echo $docsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $docsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
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
                <div id="<?php echo $settingsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $settingsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
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
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="userDropdownMenu" class="dropdown-menu mt-2">
                <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100 text-gray-700"><i class="fas fa-user mr-2"></i>Manage Profile</a>
                <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 text-red-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>
    <div class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <!-- Header with Add Button -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Officials Management</h1>
                    <p class="text-gray-600">Manage barangay officials and their positions</p>
                </div>
                <button onclick="openOfficialModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Add Official
                </button>
            </div>

            <!-- Officials Grid (Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6" id="officialsGrid">
                <?php
                // Fetch officials from barangay_officials table (using your actual structure)
                $officials = [];
                $officials_result = $conn->query("SELECT id, first_name, middle_initial, last_name, suffix, position FROM barangay_officials ORDER BY FIELD(position, 'Punong Barangay', 'Barangay Secretary', 'Barangay Treasurer', 'Sangguniang Barangay Member'), id ASC");
                if ($officials_result) {
                    while ($row = $officials_result->fetch_assoc()) {
                        $officials[] = $row;
                    }
                }
                foreach ($officials as $official) {
                    // Build full name
                    $full_name = htmlspecialchars($official['first_name']);
                    if (!empty($official['middle_initial'])) $full_name .= ' ' . htmlspecialchars($official['middle_initial']) . '.';
                    $full_name .= ' ' . htmlspecialchars($official['last_name']);
                    if (!empty($official['suffix'])) $full_name .= ', ' . htmlspecialchars($official['suffix']);
                    // Icon by position
                    $icon = 'fa-user';
                    if (stripos($official['position'], 'Punong Barangay') !== false) $icon = 'fa-user-tie';
                    elseif (stripos($official['position'], 'Secretary') !== false) $icon = 'fa-user-pen';
                    elseif (stripos($official['position'], 'Treasurer') !== false) $icon = 'fa-coins';
                    elseif (stripos($official['position'], 'Kagawad') !== false || stripos($official['position'], 'Sangguniang Barangay Member') !== false) $icon = 'fa-user-friends';
                    // Card
                ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 flex flex-col justify-between">
                    <div class="flex items-center mb-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas <?php echo $icon; ?> text-blue-600 text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900"><?php echo $full_name; ?></h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($official['position']); ?></p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-2">
                        <button onclick="editOfficial(<?php echo (int)$official['id']; ?>)" class="text-blue-600 hover:text-blue-800 p-2" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteOfficial(<?php echo (int)$official['id']; ?>)" class="text-red-600 hover:text-red-800 p-2" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php } ?>
                <?php if (empty($officials)) { ?>
                    <div class="col-span-full text-center text-gray-500 py-12">No officials found.</div>
                <?php } ?>
            </div>

            <!-- Officials Table removed as requested -->

            <!-- Add/Edit Official Modal -->
            <div id="officialModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div class="flex items-center justify-between p-6 border-b">
                            <h3 class="text-lg font-semibold" id="modalTitle">Add Official</h3>
                            <button onclick="closeOfficialModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form id="officialForm" class="p-6">
                            <input type="hidden" id="officialId">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" id="officialName" required 
                                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                                <select id="officialPosition" required 
                                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Position</option>
                                    <option value="Barangay Captain">Barangay Captain</option>
                                    <option value="Barangay Kagawad">Barangay Kagawad</option>
                                    <option value="Barangay Secretary">Barangay Secretary</option>
                                    <option value="Barangay Treasurer">Barangay Treasurer</option>
                                    <option value="SK Chairman">SK Chairman</option>
                                    <option value="SK Kagawad">SK Kagawad</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Term Start</label>
                                <input type="date" id="termStart" required 
                                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Term End</label>
                                <input type="date" id="termEnd" required 
                                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                                <input type="tel" id="contactNumber" 
                                       class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select id="officialStatus" 
                                        class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </form>
                        <div class="flex justify-end gap-3 p-6 border-t">
                            <button onclick="closeOfficialModal()" type="button" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button onclick="saveOfficial()" type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Save Official
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Confirm Delete</h3>
                                    <p class="text-sm text-gray-600">Are you sure you want to delete this official?</p>
                                </div>
                            </div>
                            <p class="text-gray-700 mb-6" id="deleteOfficialName"></p>
                        </div>
                        <div class="flex justify-end gap-3 p-6 border-t">
                            <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Delete
                            </button>
                        </div>
                    </div>
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

        // Fill officials from PHP (database)
        let officials = [
            <?php
            $officials_result = $conn->query("SELECT id, first_name, middle_initial, last_name, suffix, position FROM barangay_officials ORDER BY FIELD(position, 'Punong Barangay', 'Barangay Secretary', 'Barangay Treasurer', 'Sangguniang Barangay Member'), id ASC");
            if ($officials_result) {
                $first = true;
                while ($row = $officials_result->fetch_assoc()) {
                    if (!$first) echo ",\n"; else $first = false;
                    $full_name = addslashes($row['first_name']);
                    if (!empty($row['middle_initial'])) $full_name .= ' ' . addslashes($row['middle_initial']) . '.';
                    $full_name .= ' ' . addslashes($row['last_name']);
                    if (!empty($row['suffix'])) $full_name .= ', ' . addslashes($row['suffix']);
                    echo json_encode([
                        'id' => (int)$row['id'],
                        'name' => $full_name,
                        'position' => $row['position'],
                        // No term_start, term_end, contact_number, status in schema
                    ]);
                }
            }
            ?>
        ];

        let officialsTable;
        let editingOfficialId = null;
        let deletingOfficialId = null;

        // Initialize Tabulator table
        function initializeTable() {
            officialsTable = new Tabulator("#officialsTable", {
                data: officials,
                layout: "fitColumns",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [5, 10, 20],
                movableColumns: true,
                resizableColumns: true,
                columns: [
                    {title: "Name", field: "name", width: 200, sorter: "string"},
                    {title: "Position", field: "position", width: 180, sorter: "string"},
                    {title: "Term Start", field: "term_start", width: 120, sorter: "date", 
                     formatter: function(cell) {
                         return new Date(cell.getValue()).toLocaleDateString();
                     }
                    },
                    {title: "Term End", field: "term_end", width: 120, sorter: "date", 
                     formatter: function(cell) {
                         return new Date(cell.getValue()).toLocaleDateString();
                     }
                    },
                    {title: "Contact", field: "contact_number", width: 150, sorter: "string"},
                    {title: "Status", field: "status", width: 100, sorter: "string",
                     formatter: function(cell) {
                         const status = cell.getValue();
                         const color = status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                         return `<span class="px-2 py-1 rounded-full text-xs ${color}">${status}</span>`;
                     }
                    },
                    {title: "Actions", formatter: function(cell) {
                        const id = cell.getRow().getData().id;
                        return `
                            <button onclick="editOfficial(${id})" class="bg-blue-500 text-white px-2 py-1 rounded text-sm hover:bg-blue-600 mr-1">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteOfficial(${id})" class="bg-red-500 text-white px-2 py-1 rounded text-sm hover:bg-red-600">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                     }, width: 120, hozAlign: "center", headerSort: false}
                ]
            });
        }

        // Render officials grid
        function renderOfficialsGrid() {
            const grid = document.getElementById('officialsGrid');
            grid.innerHTML = '';

            officials.forEach(official => {
                const card = document.createElement('div');
                card.className = 'bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6';
                card.innerHTML = `
                    <div class="flex items-center mb-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user text-blue-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">${official.name}</h3>
                            <p class="text-sm text-gray-600">${official.position}</p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button onclick="editOfficial(${official.id})" class="text-blue-600 hover:text-blue-800 p-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteOfficial(${official.id})" class="text-red-600 hover:text-red-800 p-2">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        // Modal functions
        function openOfficialModal(id = null) {
            editingOfficialId = id;
            const modal = document.getElementById('officialModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('officialForm');

            if (id) {
                // Edit mode
                title.textContent = 'Edit Official';
                const official = officials.find(o => o.id === id);
                if (official) {
                    document.getElementById('officialId').value = official.id;
                    document.getElementById('officialName').value = official.name;
                    document.getElementById('officialPosition').value = official.position;
                    document.getElementById('termStart').value = official.term_start;
                    document.getElementById('termEnd').value = official.term_end;
                    document.getElementById('contactNumber').value = official.contact_number;
                    document.getElementById('officialStatus').value = official.status;
                }
            } else {
                // Add mode
                title.textContent = 'Add Official';
                form.reset();
            }

            modal.classList.remove('hidden');
        }

        function closeOfficialModal() {
            document.getElementById('officialModal').classList.add('hidden');
            document.getElementById('officialForm').reset();
            editingOfficialId = null;
        }

        function saveOfficial() {
            const name = document.getElementById('officialName').value;
            const position = document.getElementById('officialPosition').value;
            const termStart = document.getElementById('termStart').value;
            const termEnd = document.getElementById('termEnd').value;
            const contactNumber = document.getElementById('contactNumber').value;
            const status = document.getElementById('officialStatus').value;

            if (!name || !position || !termStart || !termEnd) {
                alert('Please fill in all required fields');
                return;
            }

            if (editingOfficialId) {
                // Update existing official
                const index = officials.findIndex(o => o.id === editingOfficialId);
                if (index !== -1) {
                    officials[index] = {
                        ...officials[index],
                        name,
                        position,
                        term_start: termStart,
                        term_end: termEnd,
                        contact_number: contactNumber,
                        status
                    };
                }
            } else {
                // Add new official
                const newId = Math.max(...officials.map(o => o.id)) + 1;
                officials.push({
                    id: newId,
                    name,
                    position,
                    term_start: termStart,
                    term_end: termEnd,
                    contact_number: contactNumber,
                    status
                });
            }

            // Refresh displays
            renderOfficialsGrid();
            officialsTable.setData(officials);
            closeOfficialModal();
            showNotification(editingOfficialId ? 'Official updated successfully!' : 'Official added successfully!', 'success');
        }

        function editOfficial(id) {
            openOfficialModal(id);
        }

        function deleteOfficial(id) {
            deletingOfficialId = id;
            const official = officials.find(o => o.id === id);
            if (official) {
                document.getElementById('deleteOfficialName').textContent = `${official.name} (${official.position})`;
                document.getElementById('deleteModal').classList.remove('hidden');
            }
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deletingOfficialId = null;
        }

        function confirmDelete() {
            if (deletingOfficialId) {
                officials = officials.filter(o => o.id !== deletingOfficialId);
                renderOfficialsGrid();
                officialsTable.setData(officials);
                closeDeleteModal();
                showNotification('Official deleted successfully!', 'success');
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

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeTable();
            renderOfficialsGrid();
        });
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
    </script>
</body>
</html>
