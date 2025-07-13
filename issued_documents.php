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

// AJAX endpoint for fetching issued documents
if (isset($_GET['fetch_documents'])) {
    header('Content-Type: application/json');
    
    $sql = "SELECT 
                cr.id,
                cr.certificate_type,
                cr.purpose,
                cr.requested_at as issued_date,
                cr.status,
                cr.certificate_number,
                cr.processed_by,
                CONCAT(i.first_name, ' ', 
                       COALESCE(i.middle_name, ''), ' ', 
                       i.last_name, ' ', 
                       COALESCE(i.suffix, '')) as resident_name,
                CONCAT(u.first_name, ' ', u.last_name) as issued_by
            FROM certificate_requests cr 
            LEFT JOIN individuals i ON cr.individual_id = i.id 
            LEFT JOIN users u ON cr.processed_by = u.id
            WHERE cr.status = 'Issued'
            ORDER BY cr.requested_at DESC";
    
    $result = $conn->query($sql);
    $documents = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Format certificate type for display
            $certificate_type_formatted = ucwords(str_replace('_', ' ', $row['certificate_type']));
            
            // Generate certificate number if not present
            $cert_number = $row['certificate_number'] ?: 'CERT-' . strtoupper($row['certificate_type']) . '-' . date('Y') . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
            
            $documents[] = [
                'id' => $row['id'],
                'certificate_number' => $cert_number,
                'resident_name' => trim($row['resident_name']),
                'document_type' => $certificate_type_formatted,
                'purpose' => $row['purpose'] ?: 'N/A',
                'issued_date' => $row['issued_date'],
                'status' => $row['status'],
                'issued_by' => $row['issued_by'] ?: 'System'
            ];
        }
    }
    
    echo json_encode($documents);
    exit();
}

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
                            <option value="clearance">Barangay Clearance</option>
                            <option value="residency">Certificate of Residency</option>
                            <option value="indigency">Certificate of Indigency</option>
                            <option value="first_time_job_seeker">First Time Job Seeker</option>
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
        let documentsData = [];

        // Fetch real data from the database
        async function fetchDocuments() {
            try {
                const response = await fetch('issued_documents.php?fetch_documents=1');
                const data = await response.json();
                
                // Calculate request counts for each document
                const processedData = data.map(doc => {
                    // Count how many times this resident requested this document type
                    const requestCount = data.filter(d => 
                        d.resident_name === doc.resident_name && 
                        d.document_type === doc.document_type
                    ).length;
                    
                    return {
                        ...doc,
                        request_count: requestCount
                    };
                });
                
                documentsData = processedData;
                
                // Update table with processed data
                if (documentsTable) {
                    documentsTable.setData(documentsData);
                }
                
                // Update statistics
                updateStatistics();
            } catch (error) {
                console.error('Error fetching documents:', error);
                showNotification('Error loading documents', 'error');
            }
        }

        // Initialize Tabulator table
        let documentsTable;

        function initializeTable() {
            documentsTable = new Tabulator("#documentsTable", {
                data: [],
                layout: "fitColumns",
                pagination: "local",
                paginationSize: 10,
                paginationSizeSelector: [5, 10, 20, 50],
                movableColumns: true,
                resizableColumns: true,
                placeholder: "Loading documents...",
                columns: [
                    {title: "Certificate #", field: "certificate_number", sorter: "string"},
                    {title: "Document Type", field: "document_type", sorter: "string"},
                    {title: "Resident Name", field: "resident_name", sorter: "string"},
                    {title: "Request Count", field: "request_count", sorter: "number", width: 120,
                     formatter: function(cell) {
                         const count = cell.getValue();
                         const colorClass = count > 1 ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-600';
                         return `<span class="px-2 py-1 text-xs rounded-full ${colorClass} font-medium">${count}${count > 1 ? ' times' : ' time'}</span>`;
                     }
                    },
                    {title: "Date Issued", field: "issued_date", sorter: "date", 
                     formatter: function(cell) {
                         return new Date(cell.getValue()).toLocaleDateString();
                     }
                    },
                    {title: "Time Issued", field: "issued_date", sorter: "date", width: 110,
                     formatter: function(cell) {
                         return new Date(cell.getValue()).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                     }
                    },
                    {title: "Status", field: "status", sorter: "string",
                     formatter: function(cell) {
                         const status = cell.getValue();
                         const colorClass = status === 'Issued' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                         return `<span class="px-2 py-1 text-xs rounded-full ${colorClass}">${status}</span>`;
                     }
                    }
                ]
            });
        }

        // Update statistics
        function updateStatistics() {
            const total = documentsData.length;
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            
            // Count documents issued today
            const todayDocs = documentsData.filter(doc => {
                const docDateStr = doc.issued_date.split(' ')[0]; // Get just the date part (YYYY-MM-DD)
                return docDateStr === todayStr;
            }).length;
            
            // Count documents issued this week
            const oneWeekAgo = new Date();
            oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
            const weekDocs = documentsData.filter(doc => {
                const docDate = new Date(doc.issued_date);
                return docDate >= oneWeekAgo && docDate <= today;
            }).length;
            
            // Count documents issued this month
            const oneMonthAgo = new Date();
            oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
            const monthDocs = documentsData.filter(doc => {
                const docDate = new Date(doc.issued_date);
                return docDate >= oneMonthAgo && docDate <= today;
            }).length;

            document.getElementById('totalDocuments').textContent = total;
            document.getElementById('todayDocuments').textContent = todayDocs;
            document.getElementById('weekDocuments').textContent = weekDocs;
            document.getElementById('monthDocuments').textContent = monthDocs;
        }

        // Search and filter functions
        function setupFilters() {
            function applyFilters() {
                const searchTerm = document.getElementById('searchInput').value.toLowerCase();
                const documentType = document.getElementById('documentTypeFilter').value;
                
                documentsTable.setFilter(function(data) {
                    // Check search term match
                    const searchMatch = !searchTerm || 
                        data.resident_name.toLowerCase().includes(searchTerm) ||
                        data.document_type.toLowerCase().includes(searchTerm) ||
                        data.certificate_number.toLowerCase().includes(searchTerm);
                    
                    // Check document type match
                    const typeMatch = !documentType || 
                        data.document_type.toLowerCase().replace(/\s+/g, '_').includes(documentType) ||
                        data.document_type.toLowerCase().includes(documentType.replace(/_/g, ' '));
                    
                    // Both conditions must be true
                    return searchMatch && typeMatch;
                });
            }

            document.getElementById('searchInput').addEventListener('input', applyFilters);
            document.getElementById('documentTypeFilter').addEventListener('change', applyFilters);
        }

        // Modal functions
        function closeDocumentModal() {
            document.getElementById('documentModal').classList.add('hidden');
        }

        function exportDocuments() {
            // Simple CSV export
            const csvContent = "data:text/csv;charset=utf-8," 
                + "Certificate Number,Document Type,Resident Name,Request Count,Date Issued,Time Issued,Status\n"
                + documentsData.map(doc => {
                    const issuedDate = new Date(doc.issued_date);
                    const dateOnly = issuedDate.toLocaleDateString();
                    const timeOnly = issuedDate.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                    return `${doc.certificate_number},${doc.document_type},${doc.resident_name},${doc.request_count},${dateOnly},${timeOnly},${doc.status}`;
                  }).join("\n");

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
            setupFilters();
            fetchDocuments(); // Fetch real data from database
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

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 p-4 rounded-xl shadow-xl z-50 transform translate-x-full transition-all duration-300 ${
                type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' : 
                type === 'error' ? 'bg-gradient-to-r from-red-500 to-red-600 text-white' : 
                'bg-gradient-to-r from-blue-500 to-blue-600 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-3">
                        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium">${message}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Trigger animation
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }
    </script>
</body>
</html>
