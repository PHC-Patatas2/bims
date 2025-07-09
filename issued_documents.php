<?php
// issued_documents.php
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
    <title>Issued Documents Log - <?php echo htmlspecialchars($system_title); ?></title>
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
            $settingsActive = navActive(['officials.php', 'users.php', 'settings.php', 'logs.php']);
            $settingsId = 'settingsSubNav';
            ?>
            <div class="mt-2">
                <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $settingsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleDropdown('<?php echo $settingsId; ?>')">
                    <i class="fas fa-cogs"></i> System Settings <i class="fas fa-chevron-down ml-auto"></i>
                </button>
                <div id="<?php echo $settingsId; ?>" class="ml-6 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $settingsActive ? 'dropdown-open' : 'dropdown-closed'; ?>">
                    <?php echo navLink('officials.php', 'fas fa-user-tie', 'Officials', navActive('officials.php'));
                    ?>
                    <?php echo navLink('users.php', 'fas fa-users-cog', 'User Accounts', navActive('users.php'));
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
            <div class="flex items-center mb-4">
                <h1 class="text-2xl font-bold">Issued Documents Log</h1>
            </div>
            <!-- Search and Filter Controls -->
            <div class="bg-white rounded-lg shadow mb-6 p-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Documents</label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search by resident name, document type, or certificate number..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <div class="md:w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                        <select id="documentTypeFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="barangay_clearance">Barangay Clearance</option>
                            <option value="certificate_of_residency">Certificate of Residency</option>
                            <option value="certificate_of_indigency">Certificate of Indigency</option>
                            <option value="business_permit">Business Permit</option>
                            <option value="cedula">Cedula</option>
                        </select>
                    </div>
                    <div class="md:w-48">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <select id="dateRangeFilter" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">All Time</option>
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button onclick="exportDocuments()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-download mr-2"></i>Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Documents</p>
                            <p class="text-2xl font-bold text-gray-900" id="totalDocuments">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-calendar-day text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Today</p>
                            <p class="text-2xl font-bold text-gray-900" id="todayDocuments">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-yellow-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-calendar-week text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">This Week</p>
                            <p class="text-2xl font-bold text-gray-900" id="weekDocuments">0</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-calendar-alt text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">This Month</p>
                            <p class="text-2xl font-bold text-gray-900" id="monthDocuments">0</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Table -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Issued Documents</h3>
                </div>
                <div class="overflow-x-auto">
                    <div id="documentsTable"></div>
                </div>
            </div>

            <!-- Document Details Modal -->
            <div id="documentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                        <div class="flex items-center justify-between p-6 border-b">
                            <h3 class="text-lg font-semibold">Document Details</h3>
                            <button onclick="closeDocumentModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="p-6" id="documentDetails">
                            <!-- Details will be populated here -->
                        </div>
                        <div class="flex justify-end gap-3 p-6 border-t">
                            <button onclick="closeDocumentModal()" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Close
                            </button>
                            <button onclick="printDocument()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-print mr-2"></i>Print
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

        // Sample data for demonstration
        const sampleDocuments = [
            {
                id: 1,
                certificate_number: 'BC-2024-001',
                document_type: 'Barangay Clearance',
                resident_name: 'Juan dela Cruz',
                issued_by: 'Maria Santos',
                issued_date: '2024-07-09',
                purpose: 'Employment Requirements'
            },
            {
                id: 2,
                certificate_number: 'CR-2024-015',
                document_type: 'Certificate of Residency',
                resident_name: 'Ana Rodriguez',
                issued_by: 'Maria Santos',
                issued_date: '2024-07-08',
                purpose: 'Bank Requirements'
            },
            {
                id: 3,
                certificate_number: 'CI-2024-008',
                document_type: 'Certificate of Indigency',
                resident_name: 'Pedro Martinez',
                issued_by: 'Jose Garcia',
                issued_date: '2024-07-07',
                purpose: 'Medical Assistance'
            }
        ];

        // Initialize Tabulator table
        let documentsTable;

        function initializeTable() {
            documentsTable = new Tabulator("#documentsTable", {
                data: sampleDocuments,
                layout: "fitColumns",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [5, 10, 20, 50],
                movableColumns: true,
                resizableColumns: true,
                columns: [
                    {title: "Certificate #", field: "certificate_number", width: 150, sorter: "string"},
                    {title: "Document Type", field: "document_type", width: 180, sorter: "string"},
                    {title: "Resident Name", field: "resident_name", width: 200, sorter: "string"},
                    {title: "Issued By", field: "issued_by", width: 150, sorter: "string"},
                    {title: "Date Issued", field: "issued_date", width: 120, sorter: "date", 
                     formatter: function(cell) {
                         return new Date(cell.getValue()).toLocaleDateString();
                     }
                    },
                    {title: "Purpose", field: "purpose", width: 200, sorter: "string"},
                    {title: "Actions", formatter: function(cell) {
                        return '<button onclick="viewDocument(' + cell.getRow().getData().id + ')" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600"><i class="fas fa-eye mr-1"></i>View</button>';
                    }, width: 100, hozAlign: "center", headerSort: false}
                ]
            });
        }

        // Update statistics
        function updateStatistics() {
            const total = sampleDocuments.length;
            const today = new Date().toISOString().split('T')[0];
            const todayDocs = sampleDocuments.filter(doc => doc.issued_date === today).length;
            
            const oneWeekAgo = new Date();
            oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
            const weekDocs = sampleDocuments.filter(doc => new Date(doc.issued_date) >= oneWeekAgo).length;
            
            const oneMonthAgo = new Date();
            oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
            const monthDocs = sampleDocuments.filter(doc => new Date(doc.issued_date) >= oneMonthAgo).length;

            document.getElementById('totalDocuments').textContent = total;
            document.getElementById('todayDocuments').textContent = todayDocs;
            document.getElementById('weekDocuments').textContent = weekDocs;
            document.getElementById('monthDocuments').textContent = monthDocs;
        }

        // Search and filter functions
        function setupFilters() {
            document.getElementById('searchInput').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                documentsTable.setFilter(function(data) {
                    return data.resident_name.toLowerCase().includes(searchTerm) ||
                           data.document_type.toLowerCase().includes(searchTerm) ||
                           data.certificate_number.toLowerCase().includes(searchTerm);
                });
            });

            document.getElementById('documentTypeFilter').addEventListener('change', function() {
                const type = this.value;
                if (type) {
                    documentsTable.setFilter("document_type", "=", type);
                } else {
                    documentsTable.clearFilter();
                }
            });

            document.getElementById('dateRangeFilter').addEventListener('change', function() {
                const range = this.value;
                const today = new Date();
                
                if (range === 'today') {
                    const todayStr = today.toISOString().split('T')[0];
                    documentsTable.setFilter("issued_date", "=", todayStr);
                } else if (range === 'week') {
                    const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                    documentsTable.setFilter("issued_date", ">=", weekAgo.toISOString().split('T')[0]);
                } else if (range === 'month') {
                    const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
                    documentsTable.setFilter("issued_date", ">=", monthAgo.toISOString().split('T')[0]);
                } else if (range === 'year') {
                    const yearAgo = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
                    documentsTable.setFilter("issued_date", ">=", yearAgo.toISOString().split('T')[0]);
                } else {
                    documentsTable.clearFilter();
                }
            });
        }

        // Modal functions
        function viewDocument(id) {
            const document = sampleDocuments.find(doc => doc.id === id);
            if (document) {
                const detailsHtml = `
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Certificate Number</label>
                            <p class="text-gray-900">${document.certificate_number}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Document Type</label>
                            <p class="text-gray-900">${document.document_type}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Resident Name</label>
                            <p class="text-gray-900">${document.resident_name}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Issued By</label>
                            <p class="text-gray-900">${document.issued_by}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date Issued</label>
                            <p class="text-gray-900">${new Date(document.issued_date).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Purpose</label>
                            <p class="text-gray-900">${document.purpose}</p>
                        </div>
                    </div>
                `;
                document.getElementById('documentDetails').innerHTML = detailsHtml;
                document.getElementById('documentModal').classList.remove('hidden');
            }
        }

        function closeDocumentModal() {
            document.getElementById('documentModal').classList.add('hidden');
        }

        function printDocument() {
            // Placeholder for print functionality
            alert('Print functionality would be implemented here');
        }

        function exportDocuments() {
            // Simple CSV export
            const csvContent = "data:text/csv;charset=utf-8," 
                + "Certificate Number,Document Type,Resident Name,Issued By,Date Issued,Purpose\n"
                + sampleDocuments.map(doc => 
                    `${doc.certificate_number},${doc.document_type},${doc.resident_name},${doc.issued_by},${doc.issued_date},${doc.purpose}`
                  ).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `issued_documents_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeTable();
            updateStatistics();
            setupFilters();
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
