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
}    // AJAX endpoint for logs data
if (isset($_GET['fetch_logs'])) {
    header('Content-Type: application/json');
    $logs = [];
    $sql = "SELECT a.id, a.user_id, a.action, a.details, a.timestamp, u.first_name, u.last_name, u.username 
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
                'details' => $row['details'] ?? ''
            ];
        }
    }
    echo json_encode($logs);
    exit();
}

// AJAX endpoint for log statistics
if (isset($_GET['fetch_stats'])) {
    header('Content-Type: application/json');
    
    // Get total logs count
    $totalResult = $conn->query("SELECT COUNT(*) as total FROM audit_trail");
    $totalLogs = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
    
    // Get today's logs count
    $todayResult = $conn->query("SELECT COUNT(*) as today FROM audit_trail WHERE DATE(timestamp) = CURDATE()");
    $todayLogs = $todayResult ? $todayResult->fetch_assoc()['today'] : 0;
    
    echo json_encode([
        'total' => (int)$totalLogs,
        'today' => (int)$todayLogs
    ]);
    exit();
}

// AJAX endpoint for exporting logs to XLSX
if (isset($_GET['export_logs'])) {
    // Get filters from request
    $dateFilter = $_GET['date_filter'] ?? '';
    $userFilter = $_GET['user_filter'] ?? '';
    $searchTerm = $_GET['search_term'] ?? '';
    
    // Build SQL query with filters
    $sql = "SELECT a.id, a.user_id, a.action, a.details, a.timestamp, 
                   u.first_name, u.last_name, u.username 
            FROM audit_trail a 
            LEFT JOIN users u ON a.user_id = u.id WHERE 1=1";
    
    $params = [];
    $types = '';
    
    // Apply date filter
    if ($dateFilter) {
        switch ($dateFilter) {
            case 'today':
                $sql .= " AND DATE(a.timestamp) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND a.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $sql .= " AND a.timestamp >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
    }
    
    // Apply user filter
    if ($userFilter) {
        $sql .= " AND (u.username = ? OR CONCAT(u.first_name, ' ', u.last_name) = ?)";
        $params[] = $userFilter;
        $params[] = $userFilter;
        $types .= 'ss';
    }
    
    // Apply search filter
    if ($searchTerm) {
        $sql .= " AND (u.username LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR a.action LIKE ? OR a.details LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $types .= 'ssss';
    }
    
    $sql .= " ORDER BY a.timestamp DESC";
    
    // Execute query
    if ($params) {
        $stmt = $conn->prepare($sql);
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    // Prepare data for Excel export
    $logsData = [];
    
    // Add header row
    $logsData[] = [
        'Date & Time',
        'User', 
        'Activity',
        'Details'
    ];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format date
            $date = date('Y-m-d H:i:s', strtotime($row['timestamp']));
            
            // Format user name
            $userName = '';
            if ($row['first_name'] || $row['last_name']) {
                $userName = trim($row['first_name'] . ' ' . $row['last_name']);
            } else {
                $userName = $row['username'] ?: 'System';
            }
            
            // Make action human-readable
            $humanAction = $row['action'];
            if (stripos($humanAction, 'login') !== false) {
                $humanAction = 'User Login';
            } elseif (stripos($humanAction, 'logout') !== false) {
                $humanAction = 'User Logout';
            } elseif (stripos($humanAction, 'create') !== false || stripos($humanAction, 'add') !== false) {
                if (stripos($humanAction, 'individual') !== false || stripos($humanAction, 'resident') !== false) {
                    $humanAction = 'Added New Resident';
                } elseif (stripos($humanAction, 'official') !== false) {
                    $humanAction = 'Added New Official';
                } else {
                    $humanAction = 'Added New Record';
                }
            } elseif (stripos($humanAction, 'update') !== false || stripos($humanAction, 'edit') !== false) {
                if (stripos($humanAction, 'individual') !== false || stripos($humanAction, 'resident') !== false) {
                    $humanAction = 'Updated Resident';
                } elseif (stripos($humanAction, 'official') !== false) {
                    $humanAction = 'Updated Official';
                } else {
                    $humanAction = 'Updated Record';
                }
            } elseif (stripos($humanAction, 'delete') !== false || stripos($humanAction, 'remove') !== false) {
                if (stripos($humanAction, 'individual') !== false || stripos($humanAction, 'resident') !== false) {
                    $humanAction = 'Deleted Resident';
                } elseif (stripos($humanAction, 'official') !== false) {
                    $humanAction = 'Deleted Official';
                } else {
                    $humanAction = 'Deleted Record';
                }
            } elseif (stripos($humanAction, 'generate') !== false) {
                $humanAction = 'Generated Document';
            } elseif (stripos($humanAction, 'certificate') !== false) {
                $humanAction = 'Certificate Issued';
            }
            
            // Convert JSON details to human-readable format using same logic as modal
            $humanDetails = $row['details'];
            if ($humanDetails && (strpos($humanDetails, '{') === 0 || strpos($humanDetails, '[') === 0)) {
                $decoded = json_decode($humanDetails, true);
                if ($decoded) {
                    $readableDetails = '';
                    
                    if (is_array($decoded) && !array_key_exists(0, $decoded)) {
                        // Associative array
                        $detailParts = [];
                        foreach ($decoded as $key => $value) {
                            // Skip redundant fields that are already shown in other columns
                            if (in_array($key, ['timestamp', 'user_id', 'username', 'first_name', 'last_name'])) {
                                continue;
                            }
                            
                            // Convert field names to human-readable labels (same as modal)
                            $label = str_replace('_', ' ', $key);
                            $label = ucwords($label);
                            if ($key === 'id') $label = 'ID';
                            if ($key === 'first_name') $label = 'First Name';
                            if ($key === 'last_name') $label = 'Last Name';
                            if ($key === 'purok') $label = 'Purok';
                            if ($key === 'gender') $label = 'Gender';
                            if ($key === 'religion') $label = 'Religion';
                            if ($key === 'civil_status') $label = 'Civil Status';
                            if ($key === 'birth_date') $label = 'Birth Date';
                            if ($key === 'phone_number') $label = 'Phone Number';
                            if ($key === 'email_address') $label = 'Email Address';
                            if ($key === 'certificate_type') $label = 'Certificate Type';
                            if ($key === 'purpose') $label = 'Purpose';
                            
                            $displayValue = is_array($value) ? implode(', ', $value) : $value;
                            $detailParts[] = $label . ': ' . $displayValue;
                        }
                        $readableDetails = implode(' | ', $detailParts);
                    } else {
                        // Regular array
                        $readableDetails = implode(', ', $decoded);
                    }
                    $humanDetails = $readableDetails ?: $humanDetails;
                }
            }
            
            // Add row to data array - no truncation needed for Excel
            $logsData[] = [
                $date,
                $userName,
                $humanAction,
                $humanDetails
            ];
        }
    }
    
    // Generate filename with filters info
    $filename = 'system_logs_' . date('Y-m-d_H-i-s');
    if ($dateFilter) {
        $filename .= '_' . $dateFilter;
    }
    if ($userFilter) {
        $filename .= '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $userFilter);
    }
    $filename .= '.xlsx';
    
    // Return JSON data for client-side Excel generation
    header('Content-Type: application/json');
    echo json_encode([
        'data' => $logsData,
        'filename' => $filename,
        'metadata' => [
            'generated_on' => date('Y-m-d H:i:s'),
            'date_filter' => $dateFilter ? ucfirst($dateFilter) : 'All Time',
            'user_filter' => $userFilter ?: 'All Users',
            'search_term' => $searchTerm ?: 'None',
            'total_records' => count($logsData) - 1 // Subtract header row
        ]
    ]);
    exit();
}

// Get user information for navigation
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

// Get system title for navigation
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 10px 20px -5px #0002; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <?php include 'navigation.php'; ?>
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
                        <i class="fas fa-download mr-2"></i>Export to Excel
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
                    populateUserFilter();
                })
                .catch(error => {
                    console.error('Error fetching logs:', error);
                });
        }
        
        // Fetch statistics from database (separate from table filtering)
        function fetchLogStatistics() {
            fetch('logs.php?fetch_stats=1')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalLogs').textContent = data.total;
                    document.getElementById('todayLogs').textContent = data.today;
                })
                .catch(error => {
                    console.error('Error fetching statistics:', error);
                    document.getElementById('totalLogs').textContent = '0';
                    document.getElementById('todayLogs').textContent = '0';
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
                            
                            // Make activities more human-readable with proper grammar
                            if (action.toLowerCase().includes('login')) {
                                iconClass = 'fas fa-sign-in-alt text-green-500';
                                description = 'User logged into the system';
                            } else if (action.toLowerCase().includes('logout')) {
                                iconClass = 'fas fa-sign-out-alt text-gray-500';
                                description = 'User logged out of the system';
                            } else if (action.toLowerCase().includes('create') || action.toLowerCase().includes('add')) {
                                iconClass = 'fas fa-plus-circle text-green-500';
                                if (action.toLowerCase().includes('individual') || action.toLowerCase().includes('resident')) {
                                    description = 'Added new resident';
                                } else if (action.toLowerCase().includes('official')) {
                                    description = 'Added new official';
                                } else if (action.toLowerCase().includes('certificate')) {
                                    description = 'Generated certificate';
                                } else {
                                    description = 'Added new record';
                                }
                            } else if (action.toLowerCase().includes('update') || action.toLowerCase().includes('edit')) {
                                iconClass = 'fas fa-edit text-yellow-500';
                                if (action.toLowerCase().includes('individual') || action.toLowerCase().includes('resident')) {
                                    description = 'Updated resident information';
                                } else if (action.toLowerCase().includes('official')) {
                                    description = 'Updated official information';
                                } else if (action.toLowerCase().includes('settings')) {
                                    description = 'Updated system settings';
                                } else {
                                    description = 'Updated record';
                                }
                            } else if (action.toLowerCase().includes('delete') || action.toLowerCase().includes('remove')) {
                                iconClass = 'fas fa-trash text-red-500';
                                if (action.toLowerCase().includes('individual') || action.toLowerCase().includes('resident')) {
                                    description = 'Deleted resident record';
                                } else if (action.toLowerCase().includes('official')) {
                                    description = 'Deleted official record';
                                } else {
                                    description = 'Deleted record';
                                }
                            } else if (action.toLowerCase().includes('generate')) {
                                iconClass = 'fas fa-file-pdf text-purple-500';
                                description = 'Generated document';
                            } else if (action.toLowerCase().includes('certificate')) {
                                iconClass = 'fas fa-certificate text-yellow-600';
                                description = 'Issued certificate';
                            } else if (action.toLowerCase().includes('announcement')) {
                                iconClass = 'fas fa-bullhorn text-blue-600';
                                description = 'Sent announcement';
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
            }

            searchInput.addEventListener('input', applyFilters);
            dateRangeFilter.addEventListener('change', applyFilters);
            userFilter.addEventListener('change', applyFilters);
        }

        // View log details in modal
        function viewLogDetails(logId) {
            const log = logsData.find(l => l.id == logId);
            if (!log) return;

            // Make action human-readable
            let humanAction = log.action || 'No action specified';
            if (humanAction.toLowerCase().includes('login')) {
                humanAction = 'User logged into the system';
            } else if (humanAction.toLowerCase().includes('logout')) {
                humanAction = 'User logged out of the system';
            } else if (humanAction.toLowerCase().includes('create') || humanAction.toLowerCase().includes('add')) {
                humanAction = 'Added new record to the system';
            } else if (humanAction.toLowerCase().includes('update') || humanAction.toLowerCase().includes('edit')) {
                humanAction = 'Updated existing record';
            } else if (humanAction.toLowerCase().includes('delete') || humanAction.toLowerCase().includes('remove')) {
                humanAction = 'Deleted record from the system';
            } else if (humanAction.toLowerCase().includes('generate')) {
                humanAction = 'Generated a document';
            } else if (humanAction.toLowerCase().includes('certificate')) {
                humanAction = 'Issued a certificate';
            }

            // Make details human-readable
            let humanDetails = log.details;
            if (humanDetails && (humanDetails.startsWith('{') || humanDetails.startsWith('['))) {
                try {
                    const decoded = JSON.parse(humanDetails);
                    let readableDetails = '';
                    
                    if (Array.isArray(decoded)) {
                        readableDetails = decoded.join(', ');
                    } else if (typeof decoded === 'object') {
                        const detailParts = [];
                        for (const [key, value] of Object.entries(decoded)) {
                            // Convert field names to human-readable labels
                            let label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            if (key === 'id') label = 'ID';
                            if (key === 'first_name') label = 'First Name';
                            if (key === 'last_name') label = 'Last Name';
                            if (key === 'purok') label = 'Purok';
                            if (key === 'gender') label = 'Gender';
                            if (key === 'religion') label = 'Religion';
                            if (key === 'civil_status') label = 'Civil Status';
                            if (key === 'birth_date') label = 'Birth Date';
                            if (key === 'phone_number') label = 'Phone Number';
                            if (key === 'email_address') label = 'Email Address';
                            if (key === 'certificate_type') label = 'Certificate Type';
                            if (key === 'purpose') label = 'Purpose';
                            
                            let displayValue = Array.isArray(value) ? value.join(', ') : value;
                            detailParts.push(`${label}: ${displayValue}`);
                        }
                        readableDetails = detailParts.join(' | ');
                    }
                    humanDetails = readableDetails || humanDetails;
                } catch (e) {
                    // If JSON parsing fails, keep original details
                }
            }

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
                        <p class="text-sm">${humanAction}</p>
                    </div>
                    ${humanDetails ? `
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-yellow-800 mb-2">Details of Changes</h4>
                            <p class="text-sm">${humanDetails}</p>
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
            // Get current filter values
            const searchTerm = document.getElementById('searchLogs').value;
            const dateFilter = document.getElementById('dateRangeFilter').value;
            const userFilter = document.getElementById('userFilter').value;
            
            // Build export URL with filters
            let exportUrl = 'logs.php?export_logs=1';
            
            if (searchTerm) {
                exportUrl += '&search_term=' + encodeURIComponent(searchTerm);
            }
            if (dateFilter) {
                exportUrl += '&date_filter=' + encodeURIComponent(dateFilter);
            }
            if (userFilter) {
                exportUrl += '&user_filter=' + encodeURIComponent(userFilter);
            }
            
            // Show loading state
            const exportBtn = event.target;
            const originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating Excel...';
            exportBtn.disabled = true;
            
            // Fetch data and generate Excel file
            fetch(exportUrl)
                .then(response => response.json())
                .then(data => {
                    // Create workbook and worksheet
                    const wb = XLSX.utils.book_new();
                    
                    // Create worksheet with metadata info
                    const metadataRows = [
                        ['System Activity Logs Report'],
                        [''],
                        ['Generated on:', data.metadata.generated_on],
                        ['Date Filter:', data.metadata.date_filter],
                        ['User Filter:', data.metadata.user_filter],
                        ['Search Term:', data.metadata.search_term],
                        ['Total Records:', data.metadata.total_records],
                        ['']
                    ];
                    
                    // Combine metadata and data
                    const allData = [...metadataRows, ...data.data];
                    
                    // Create worksheet
                    const ws = XLSX.utils.aoa_to_sheet(allData);
                    
                    // Set column widths
                    ws['!cols'] = [
                        { width: 20 }, // Date & Time
                        { width: 25 }, // User
                        { width: 30 }, // Activity
                        { width: 60 }  // Details
                    ];
                    
                    // Style the header row (row 9, 0-indexed = 8)
                    const headerRowIndex = 8;
                    const headerStyle = {
                        font: { bold: true },
                        fill: { fgColor: { rgb: "C8DCFF" } }
                    };
                    
                    // Apply header styles
                    ['A', 'B', 'C', 'D'].forEach(col => {
                        const cellRef = col + (headerRowIndex + 1);
                        if (!ws[cellRef]) ws[cellRef] = {};
                        ws[cellRef].s = headerStyle;
                    });
                    
                    // Style the title row
                    if (!ws['A1']) ws['A1'] = {};
                    ws['A1'].s = {
                        font: { bold: true, size: 16 },
                        alignment: { horizontal: 'center' }
                    };
                    
                    // Merge title cell across all columns
                    ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: 3 } }];
                    
                    // Add worksheet to workbook
                    XLSX.utils.book_append_sheet(wb, ws, 'System Logs');
                    
                    // Generate and download file
                    XLSX.writeFile(wb, data.filename);
                    
                    // Reset button after successful export
                    setTimeout(() => {
                        exportBtn.innerHTML = originalText;
                        exportBtn.disabled = false;
                    }, 1000);
                })
                .catch(error => {
                    console.error('Error exporting logs:', error);
                    alert('Error generating Excel file. Please try again.');
                    
                    // Reset button on error
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                });
        }

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetchLogs();
            fetchLogStatistics(); // Fetch statistics separately
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
