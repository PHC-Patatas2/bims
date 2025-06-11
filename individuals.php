<?php
session_start();
include 'config.php'; // Database connection

// Initialize MySQL connection (required for sidepanel/navbar queries)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Check if user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : ''; // Avoid undefined index warning

// Fetch user's full name for navbar welcome
$user_full_name = '';
$stmt = $conn->prepare('SELECT full_name FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($user_full_name);
$stmt->fetch();
$stmt->close();

// Determine filter type from URL parameter, default to 'all_residents'
$filter_type = isset($_GET['filter_type']) ? htmlspecialchars($_GET['filter_type']) : 'all_residents';
$page_title = ucwords(str_replace('_', ' ', $filter_type));

// Mapping for display names if needed (can be expanded)
$filter_type_map = [
    'all_residents' => 'All Residents',
    'members_with_family_id' => 'Residents with Family ID',
    'male' => 'Male Residents',
    'female' => 'Female Residents',
    'voter' => 'Registered Voters',
    '4ps' => '4Ps Members',
    'senior' => 'Senior Citizens',
    'pwd' => 'PWDs',
    'solo_parent' => 'Solo Parents',
    'pregnant' => 'Pregnant Women',
    'newborn' => 'Newborns',
    'minor' => 'Minors',
    // Add other filters as they come from dashboard cards
];

$page_title = isset($filter_type_map[$filter_type]) ? $filter_type_map[$filter_type] : ucwords(str_replace('_', ' ', $filter_type));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Barangay Information Management System</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link href="lib/assets/tabulator.min.css" rel="stylesheet">
    <link href="lib/assets/all.min.css" rel="stylesheet"> <!-- Font Awesome -->
    <script src="lib/assets/luxon.min.js"></script>
    <script src="lib/assets/xlsx.full.min.js"></script>
    <script src="lib/assets/jspdf.umd.min.js"></script>
    <script src="lib/assets/jspdf.plugin.autotable.min.js"></script>
    <script src="lib/assets/tabulator.min.js"></script>
    <style>
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }

        /* Tabulator Styling */
        #data-table .tabulator {
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            background-color: #fff;
            width: 100%; /* Ensure Tabulator instance takes full width of its container */
        }
        #data-table .tabulator-header {
            background-color: #f8f9fa;
            color: #212529;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 0.875rem;
        }
        #data-table .tabulator-header .tabulator-col { border-right: 1px solid #dee2e6; }
        #data-table .tabulator-header .tabulator-col:last-child { border-right: none; }
        #data-table .tabulator-header .tabulator-col .tabulator-col-content { padding: 0.5rem 0.75rem; }
        #data-table .tabulator-row { background-color: #fff; border-bottom: 1px solid #e9ecef; }
        #data-table .tabulator-row:hover { background-color: #f1f3f5; }
        #data-table .tabulator-row .tabulator-cell {
            border-right: 1px solid #e9ecef;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #data-table .tabulator-row .tabulator-cell:last-child { border-right: none; }
        #data-table .tabulator-header .tabulator-col .tabulator-header-filter input,
        #data-table .tabulator-header .tabulator-col .tabulator-header-filter select {
            padding: 0.375rem 0.75rem; font-size: 0.875rem; border-radius: 0.2rem; border: 1px solid #ced4da; margin-top: 4px;
        }
        #data-table .tabulator-cell.checkbox-cell-class { overflow: visible !important; text-overflow: clip !important; padding: 0px 8px !important; line-height: 1; }
        #data-table .tabulator-cell.checkbox-cell-class input[type="checkbox"] { margin: 0; vertical-align: middle; cursor: pointer; }
        #data-table .tabulator-header .tabulator-col-title-holder .tabulator-col-title.checkbox-header-class { overflow: visible !important; text-overflow: clip !important; }
        #data-table .tabulator-footer { background-color: #f8f9fa; padding: 0.75rem; border-top: 1px solid #dee2e6; }
        #data-table .tabulator-paginator button { color: #007bff; border: 1px solid #007bff; background-color: white; margin: 0 2px; padding: 5px 10px; border-radius: 3px; }
        #data-table .tabulator-paginator button:hover { background-color: #007bff; color: white; }
        #data-table .tabulator-paginator button:disabled { color: #6c757d; border-color: #6c757d; background-color: #e9ecef; }
        #data-table .tabulator-page-size { margin-left: 10px; padding: 5px; border-radius: 3px; border: 1px solid #ced4da; }
        .table-container { overflow-x: auto; width: 100%; }
        #data-table .tabulator-row.tabulator-selected { background-color: #e0f2fe !important; }
        @media (max-width: 1024px) {
            #data-table .tabulator { min-width: 900px; } /* Adjust as needed based on your columns */
        }
        .advanced-search-field { margin-bottom: 0.5rem; }
        .advanced-search-field label { margin-right: 0.5rem; font-weight: 500; }
        .advanced-search-field input, .advanced-search-field select {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
            border: 1px solid #ced4da;
            width: 100%; /* Full width for inputs within their grid cell */
        }
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
                $activeClass = $isActive ? 'bg-blue-600 text-white' : 'text-white';
                $hoverClass = 'hover:bg-blue-500 hover:text-white';
                echo '<a href="' . $page[0] . '" class="py-2 px-3 rounded-lg flex items-center gap-2 ' . $activeClass . ' ' . $hoverClass . '"><i class="' . $page[1] . '"></i> ' . $page[2] . '</a>';
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
            <span class="font-bold text-lg text-blue-700">Barangay Information Management System</span>
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
    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out p-4 md:px-8 md:pt-8 mt-16 flex flex-col">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h2 class="text-2xl font-bold mb-6 text-blue-900 flex items-center gap-3 z-10 relative tracking-wide">
                <a href="dashboard.php" class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 hover:bg-blue-200 text-blue-600 mr-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" title="Back to Dashboard">
                    <i class="fas fa-arrow-left"></i>
                </a>
                Residents
            </h2>
            <div class="flex items-center gap-3">
                <button id="addNewResidentBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow hover:shadow-md transition-colors duration-150 ease-in-out">
                    <i class="fas fa-plus mr-2"></i> Add Resident
                </button>
                <div class="relative">
                    <button id="exportDataBtnDropdown" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow hover:shadow-md transition-colors duration-150 ease-in-out">
                        <i class="fas fa-file-export mr-2"></i> Export <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div id="exportDropdownMenu" class="dropdown-menu absolute right-0 mt-2 py-1 w-48 bg-white rounded-md shadow-xl z-50">
                        <a href="#" id="exportCsvBtn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export Selected as CSV</a>
                        <a href="#" id="exportXlsxBtn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export Selected as XLSX</a>
                        <a href="#" id="exportPdfBtn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export Selected as PDF</a>
                         <a href="#" id="exportAllCsvBtn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 border-t mt-1 pt-1">Export All (Filtered) as CSV</a>
                        <a href="#" id="exportAllXlsxBtn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export All (Filtered) as XLSX</a>
                        <a href="#" id="exportAllPdfBtn" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export All (Filtered) as PDF</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Search Section -->
        <div id="advancedSearchContainer" class="mb-6 p-4 bg-gray-50 shadow rounded-lg border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-700 mb-3">Advanced Search</h2>
            <form id="advancedSearchForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="advanced-search-field">
                    <label for="adv_search_last_name">Last Name:</label>
                    <input type="text" id="adv_search_last_name" name="adv_search_last_name">
                </div>
                <div class="advanced-search-field">
                    <label for="adv_search_first_name">First Name:</label>
                    <input type="text" id="adv_search_first_name" name="adv_search_first_name">
                </div>
                 <div class="advanced-search-field">
                    <label for="adv_search_middle_name">Middle Name:</label>
                    <input type="text" id="adv_search_middle_name" name="adv_search_middle_name">
                </div>
                <div class="advanced-search-field">
                    <label for="adv_search_gender">Sex:</label>
                    <select id="adv_search_gender" name="adv_search_gender">
                        <option value="">Any</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="advanced-search-field">
                    <label for="adv_search_age_min">Min Age:</label>
                    <input type="number" id="adv_search_age_min" name="adv_search_age_min" min="0">
                </div>
                <div class="advanced-search-field">
                    <label for="adv_search_age_max">Max Age:</label>
                    <input type="number" id="adv_search_age_max" name="adv_search_age_max" min="0">
                </div>
                <div class="advanced-search-field">
                    <label for="adv_search_birthdate_from">Birthdate From:</label>
                    <input type="date" id="adv_search_birthdate_from" name="adv_search_birthdate_from">
                </div>
                <div class="advanced-search-field">
                    <label for="adv_search_birthdate_to">Birthdate To:</label>
                    <input type="date" id="adv_search_birthdate_to" name="adv_search_birthdate_to">
                </div>
                <div class="advanced-search-field col-span-full flex justify-end gap-3 mt-2">
                    <button type="button" id="applyAdvancedSearch" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out text-xs flex items-center">
                        <i class="fas fa-search mr-1"></i> Search
                    </button>
                    <button type="button" id="resetAdvancedSearch" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-1 px-3 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out text-xs flex items-center">
                        <i class="fas fa-trash-alt mr-1"></i> Clear Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Action Bar for Selected Rows -->
        <div id="selectedActionsBar" class="mb-4 p-3 bg-gray-200 shadow rounded-lg flex flex-wrap items-center justify-start gap-3" style="display: none;">
            <span id="selectedRowCount" class="font-semibold text-gray-700"></span>
            <button id="printSelectedBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-3 rounded-lg inline-flex items-center shadow hover:shadow-md transition-colors duration-150 ease-in-out text-sm" disabled>
                <i class="fas fa-print mr-2"></i> Print Selected
            </button>
            <button id="deleteSelectedBtn" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-3 rounded-lg inline-flex items-center shadow hover:shadow-md transition-colors duration-150 ease-in-out text-sm" disabled>
                <i class="fas fa-trash-alt mr-2"></i> Delete Selected
            </button>
            <!-- Export selected is handled by the main export dropdown now -->
        </div>

        <div class="bg-white shadow-xl rounded-lg overflow-hidden w-full table-container" style="margin-bottom: 20px;">
            <div id="data-table"></div>
        </div>
    </div>

    <!-- Add Resident Modal -->
    <div id="addResidentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
        <div class="bg-white shadow-2xl border border-gray-200 w-full max-w-xl p-3 md:p-4 relative mx-2 my-8" style="max-height:90vh; overflow-y:auto; font-size:0.92rem; scrollbar-width: none; -ms-overflow-style: none;">
            <style>
                #addResidentModal input[type="text"],
                #addResidentModal input[type="date"],
                #addResidentModal input[type="email"],
                #addResidentModal input[type="tel"],
                #addResidentModal select {
                    background-color: #fff;
                    border: 1px solid #bdbdbd;
                    color: #222;
                    border-radius: 4px;
                    width: 100%;
                    min-height: 2rem;
                    font-size: 0.92rem;
                    padding: 0.25rem 0.5rem;
                    box-sizing: border-box;
                    transition: border-color 0.2s;
                }
                #addResidentModal input[type="text"]:focus,
                #addResidentModal input[type="date"]:focus,
                #addResidentModal input[type="email"]:focus,
                #addResidentModal input[type="tel"]:focus,
                #addResidentModal select:focus {
                    border-color: #2563eb;
                    outline: none;
                    background-color: #f0f7ff;
                }
                #addResidentModal select:disabled,
                #addResidentModal input:disabled {
                    background-color: #f3f3f3;
                    color: #aaa;
                }
                /* Hide scrollbar for modal */
                #addResidentModal > div::-webkit-scrollbar {
                    display: none;
                }
                #addResidentModal > div {
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                }
            </style>
            <button id="closeAddResidentModal" class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-xl focus:outline-none" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-lg font-bold mb-2 text-gray-800 text-center">Add New Resident</h2>
            <form id="addResidentForm" method="POST" class="space-y-4">
                <!-- Personal Information Section -->
                <div class="mb-2">
                    <div class="text-base font-extrabold text-gray-800 px-2 mb-1 text-center">Personal Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div>
                            <label for="last_name" class="block text-xs font-medium text-gray-700 required-label">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                        </div>
                        <div>
                            <label for="first_name" class="block text-xs font-medium text-gray-700 required-label">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                        </div>
                        <div>
                            <label for="middle_name" class="block text-xs font-medium text-gray-700">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" class="form-input mt-0.5 py-1 px-2 text-xs" placeholder="(leave blank if none)">
                        </div>
                        <div>
                            <label for="suffix" class="block text-xs font-medium text-gray-700">Suffix</label>
                            <input type="text" name="suffix" id="suffix" class="form-input mt-0.5 py-1 px-2 text-xs" placeholder="(leave blank if none)">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-1">
                        <div>
                            <label for="gender" class="block text-xs font-medium text-gray-700 required-label">Sex / Gender</label>
                            <select name="gender" id="gender" class="form-select mt-0.5 py-1 px-2 text-xs" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label for="birthdate" class="block text-xs font-medium text-gray-700 required-label">Birthdate</label>
                            <input type="date" name="birthdate" id="birthdate" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                        </div>
                        <div>
                            <label for="civil_status" class="block text-xs font-medium text-gray-700">Civil Status</label>
                            <select name="civil_status" id="civil_status" class="form-select mt-0.5 py-1 px-2 text-xs">
                                <option value="" disabled selected>Select Civil Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Annulled">Annulled</option>
                            </select>
                        </div>
                        <div>
                            <label for="blood_type" class="block text-xs font-medium text-gray-700">Blood Type</label>
                            <select name="blood_type" id="blood_type" class="form-select mt-0.5 py-1 px-2 text-xs">
                                <option value="" disabled selected>Select Blood Type</option>
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
                            <label for="citizenship" class="block text-xs font-medium text-gray-700">Citizenship</label>
                            <input type="text" name="citizenship" id="citizenship" class="form-input mt-0.5 py-1 px-2 text-xs" value="Filipino">
                        </div>
                        <div>
                            <label for="religion" class="block text-xs font-medium text-gray-700">Religion</label>
                            <input type="text" name="religion" id="religion" class="form-input mt-0.5 py-1 px-2 text-xs">
                        </div>
                    </div>
                </div>
                <hr class="my-2 border-gray-200 border">
                <!-- Place of Birth Section -->
                <div class="mb-2">
                    <div class="text-base font-extrabold text-gray-800 px-2 mb-1 text-center">Place of Birth</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div>
                            <label for="birthplace_barangay" class="block text-xs font-medium text-gray-700 required-label">Barangay</label>
                            <input type="text" name="birthplace_barangay" id="birthplace_barangay" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                        </div>
                        <div>
                            <label for="birthplace_municipality" class="block text-xs font-medium text-gray-700 required-label">Municipality / City</label>
                            <input type="text" name="birthplace_municipality" id="birthplace_municipality" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                        </div>
                        <div>
                            <label for="birthplace_province" class="block text-xs font-medium text-gray-700 required-label">Province</label>
                            <input type="text" name="birthplace_province" id="birthplace_province" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                        </div>
                    </div>
                </div>
                <hr class="my-2 border-gray-200 border">
                <!-- Current Address Section -->
                <div class="mb-2">
                    <div class="text-base font-extrabold text-gray-800 px-2 mb-1 text-center">Current Address</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div>
                            <label for="current_purok" class="block text-xs font-medium text-gray-700 required-label">Purok</label>
                            <input type="text" name="current_purok" id="current_purok" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                        </div>
                        <div>
                            <label for="current_barangay" class="block text-xs font-medium text-gray-700 required-label">Barangay</label>
                            <input type="text" name="current_barangay" id="current_barangay" class="form-input mt-0.5 py-1 px-2 text-xs" value="<?php echo htmlspecialchars($default_barangay ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="current_municipality" class="block text-xs font-medium text-gray-700 required-label">Municipality / City</label>
                            <input type="text" name="current_municipality" id="current_municipality" class="form-input mt-0.5 py-1 px-2 text-xs" value="<?php echo htmlspecialchars($default_municipality ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="current_province" class="block text-xs font-medium text-gray-700 required-label">Province</label>
                            <input type="text" name="current_province" id="current_province" class="form-input mt-0.5 py-1 px-2 text-xs" value="<?php echo htmlspecialchars($default_province ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
                <hr class="my-2 border-gray-200 border">
                <!-- Contact Information Section -->
                <div class="mb-2">
                    <div class="text-base font-extrabold text-gray-800 px-2 mb-1 text-center">Contact Information</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div>
                            <label for="contact_number" class="block text-xs font-medium text-gray-700">Contact Number</label>
                            <input type="tel" name="contact_number" id="contact_number" class="form-input mt-0.5 py-1 px-2 text-xs" placeholder="e.g., 09123456789">
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-medium text-gray-700">Email Address</label>
                            <input type="email" name="email" id="email" class="form-input mt-0.5 py-1 px-2 text-xs" placeholder="e.g., juan.delacruz@example.com">
                        </div>
                    </div>
                </div>
                <hr class="my-2 border-gray-200 border">
                <!-- Educational Background Section -->
                <div class="mb-2">
                    <div class="text-base font-extrabold text-gray-800 px-2 mb-1 text-center">Educational Background</div>
                    <div>
                        <label for="educational_attainment" class="block text-xs font-medium text-gray-700">Educational Attainment</label>
                        <select name="educational_attainment" id="educational_attainment" class="form-select mt-0.5 py-1 px-2 text-xs">
                            <option value="" disabled selected>Select Attainment</option>
                            <option value="No Formal Education">No Formal Education</option>
                            <option value="Elementary Level">Elementary Level</option>
                            <option value="Elementary Graduate">Elementary Graduate</option>
                            <option value="High School Level">High School Level</option>
                            <option value="High School Graduate">High School Graduate</option>
                            <option value="Vocational/Trade Course">Vocational/Trade Course</option>
                            <option value="College Level">College Level</option>
                            <option value="College Graduate">College Graduate</option>
                            <option value="Post Graduate">Post Graduate</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <hr class="my-2 border-gray-200 border">
                <!-- Livelihood Section -->
                <div class="mb-2">
                    <div class="text-base font-extrabold text-gray-800 px-2 mb-1 text-center">Livelihood</div>
                    <div>
                        <label for="occupation" class="block text-xs font-medium text-gray-700">Occupation</label>
                        <input type="text" name="occupation" id="occupation" class="form-input mt-0.5 py-1 px-2 text-xs">
                    </div>
                </div>
                <hr class="my-2 border-gray-200 border">
                <!-- Other Information Section -->
                <div class="mb-2">
                    <div class="text-base font-extrabold text-gray-800 px-2 mb-1 text-center">Other Information</div>
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <div class="flex items-center">
                            <input id="is_voter" name="is_voter" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                            <label for="is_voter" class="ml-2 block text-xs text-gray-900">Registered Voter?</label>
                        </div>
                        <div class="flex items-center">
                            <input id="is_4ps_member" name="is_4ps_member" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                            <label for="is_4ps_member" class="ml-2 block text-xs text-gray-900">4Ps Member?</label>
                        </div>
                        <div class="flex items-center">
                            <input id="is_pwd" name="is_pwd" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                            <label for="is_pwd" class="ml-2 block text-xs text-gray-900">PWD?</label>
                        </div>
                        <div class="flex items-center">
                            <input id="is_solo_parent" name="is_solo_parent" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                            <label for="is_solo_parent" class="ml-2 block text-xs text-gray-900">Solo Parent?</label>
                        </div>
                        <div class="flex items-center">
                            <input id="is_pregnant" name="is_pregnant" type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                            <label for="is_pregnant" class="ml-2 block text-xs text-gray-900">Pregnant?</label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 pt-1 gap-2">
                    <button type="button" id="cancelAddResidentBtn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out text-xs">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out text-xs">
                        <i class="fas fa-save mr-1"></i> Save Resident
                    </button>
                </div>
                <div id="addResidentMessage" class="mt-2 text-xs"></div>
            </form>
        </div>
    </div>

    <script>
        // Sidepanel and Navbar toggle logic (assuming you have this in a global JS or included layout files)
        // For example:
        // const menuBtn = document.getElementById('menuBtn');
        // const sidepanel = document.getElementById('sidepanel');
        // if (menuBtn && sidepanel) { /* ... toggle logic ... */ }

        // User dropdown toggle
        const userDropdownBtn = document.getElementById('userDropdownBtn'); // Make sure this ID exists in your navbar include
        const userDropdownMenu = document.getElementById('userDropdownMenu'); // Make sure this ID exists in your navbar include
        const exportDataBtnDropdown = document.getElementById('exportDataBtnDropdown');
        const exportDropdownMenu = document.getElementById('exportDropdownMenu');

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                userDropdownMenu.classList.toggle('show');
                if (exportDropdownMenu) exportDropdownMenu.classList.remove('show');
            });
        }
        if (exportDataBtnDropdown && exportDropdownMenu) {
            exportDataBtnDropdown.addEventListener('click', (event) => {
                event.stopPropagation();
                exportDropdownMenu.classList.toggle('show');
                if (userDropdownMenu) userDropdownMenu.classList.remove('show');
            });
        }
        
        document.addEventListener('click', function(event) {
            if (userDropdownMenu && userDropdownBtn && !userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.remove('show');
            }
            if (exportDropdownMenu && exportDataBtnDropdown && !exportDataBtnDropdown.contains(event.target) && !exportDropdownMenu.contains(event.target)) {
                exportDropdownMenu.classList.remove('show');
            }
        });

        // Sidepanel toggle
        const menuBtn = document.getElementById('menuBtn');
        const sidepanel = document.getElementById('sidepanel');
        const sidepanelOverlay = document.getElementById('sidepanelOverlay');
        const closeSidepanel = document.getElementById('closeSidepanel');

        if (menuBtn && sidepanel && sidepanelOverlay && closeSidepanel) {
            menuBtn.addEventListener('click', () => {
                sidepanel.classList.remove('-translate-x-full');
                sidepanelOverlay.classList.remove('hidden');
            });
            closeSidepanel.addEventListener('click', () => {
                sidepanel.classList.add('-translate-x-full');
                sidepanelOverlay.classList.add('hidden');
            });
            sidepanelOverlay.addEventListener('click', () => {
                sidepanel.classList.add('-translate-x-full');
                sidepanelOverlay.classList.add('hidden');
            });
        }

        // Tabulator setup
        const initialFilterType = "<?php echo htmlspecialchars($filter_type, ENT_QUOTES, 'UTF-8'); ?>";
        let currentAdvancedFilters = {};

        const tableColumns = [
            { formatter: "rowSelection", titleFormatter: "rowSelection", hozAlign: "center", headerSort: false, width: 40, cssClass: "checkbox-cell-class", cellClick: (e, cell) => cell.getRow().toggleSelect(), resizable:false, download: false },
            { field: "id", visible: false, download: false }, // Keep ID for operations but not visible or in basic exports
            { title: "Last Name", field: "last_name", minWidth: 130, hozAlign: "left", tooltip: true, resizable:true },
            { title: "First Name", field: "first_name", minWidth: 130, hozAlign: "left", tooltip: true, resizable:true },
            { title: "Middle Name", field: "middle_name", minWidth: 130, hozAlign: "left", tooltip: true, resizable:true },
            { title: "Suffix", field: "suffix", minWidth: 60, hozAlign: "center", tooltip: true, resizable:true },
            { title: "Sex", field: "gender", minWidth: 80, hozAlign: "center", formatter: cell => cell.getValue() === 'male' ? 'Male' : (cell.getValue() === 'female' ? 'Female' : ''), resizable:true },
            { title: "Birthdate", field: "birthdate", minWidth: 110, hozAlign: "center", sorter: "date", tooltip: true, resizable:true },
            { title: "Age", field: "age", minWidth: 70, hozAlign: "center", formatter: cell => cell.getValue() ? cell.getValue() + " yrs" : "", resizable:true },
            { 
                title: "Actions", 
                field: "actions", 
                minWidth: 120, 
                hozAlign: "center", 
                headerSort: false, 
                resizable:false,
                download: false,
                formatter: (cell) => {
                    const id = cell.getRow().getData().id;
                    return `<button onclick="viewResident(${id})" class="text-blue-600 hover:text-blue-800 p-1 mx-0.5" title="View Details"><i class="fas fa-eye"></i></button>
                            <button onclick="editResident(${id})" class="text-green-600 hover:text-green-800 p-1 mx-0.5" title="Edit"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteSingleResident(${id}, '${cell.getRow().getData().first_name} ${cell.getRow().getData().last_name}')" class="text-red-600 hover:text-red-800 p-1 mx-0.5" title="Delete"><i class="fas fa-trash-alt"></i></button>`;
                } 
            }
        ];        
        
        const table = new Tabulator("#data-table", {
            height: "auto", // Adjust height dynamically
            layout: "fitDataStretch", // Stretch last column if space, fitData otherwise
            // layout: "fitColumns", // Alternative: columns fill available width
            selectable: "highlight",
            resizableColumns: true, 
            columnDefaults:{
                resizable: "header" // Allow resizing by dragging header
            },
            placeholder: "No Data Available",
            ajaxURL: "dashboard_data.php",
            ajaxParams: { 
                type: initialFilterType, // Initial filter from URL
                ...currentAdvancedFilters // Spread advanced filters here
            }, 
            ajaxConfig: { 
                method: "GET",
                headers: { "X-Requested-With": "XMLHttpRequest" }
            }, 
            ajaxResponse: function(url, params, response){
                if(response && Array.isArray(response.data) && typeof response.last_page === 'number'){
                    return response; 
                } else {
                    console.error("Invalid data structure from server:", response);
                    alert("Error: Could not load data. Invalid response from server.");
                    return {data: [], last_page: 0}; 
                }
            },
            pagination: true,
            paginationMode: "remote", 
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100, 500, 1000],
            filterMode: "remote", 
            sortMode: "remote", 
            columns: tableColumns,
            initialSort: [ { column: "last_name", dir: "asc" } ],
            rowSelectionChanged: function(data, rows){
                const numSelected = rows.length;
                const selectedActionsBar = document.getElementById('selectedActionsBar');
                const selectedRowCount = document.getElementById('selectedRowCount');
                const printSelectedBtn = document.getElementById('printSelectedBtn');
                const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

                if (numSelected > 0) {
                    selectedRowCount.textContent = `${numSelected} row(s) selected`;
                    selectedActionsBar.style.display = 'flex';
                    if(printSelectedBtn) printSelectedBtn.disabled = false;
                    if(deleteSelectedBtn) deleteSelectedBtn.disabled = false;
                    // Enable export selected buttons
                    document.getElementById('exportCsvBtn').classList.remove('opacity-50', 'cursor-not-allowed');
                    document.getElementById('exportXlsxBtn').classList.remove('opacity-50', 'cursor-not-allowed');
                    document.getElementById('exportPdfBtn').classList.remove('opacity-50', 'cursor-not-allowed');

                } else {
                    selectedActionsBar.style.display = 'none';
                    if(printSelectedBtn) printSelectedBtn.disabled = true;
                    if(deleteSelectedBtn) deleteSelectedBtn.disabled = true;
                     // Disable export selected buttons
                    document.getElementById('exportCsvBtn').classList.add('opacity-50', 'cursor-not-allowed');
                    document.getElementById('exportXlsxBtn').classList.add('opacity-50', 'cursor-not-allowed');
                    document.getElementById('exportPdfBtn').classList.add('opacity-50', 'cursor-not-allowed');
                }
            },
        });

        // Initialize export selected buttons as disabled
        document.getElementById('exportCsvBtn').classList.add('opacity-50', 'cursor-not-allowed');
        document.getElementById('exportXlsxBtn').classList.add('opacity-50', 'cursor-not-allowed');
        document.getElementById('exportPdfBtn').classList.add('opacity-50', 'cursor-not-allowed');


        // Advanced Search Logic
        const advancedSearchForm = document.getElementById('advancedSearchForm');
        const applyAdvancedSearchBtn = document.getElementById('applyAdvancedSearch');
        const resetAdvancedSearchBtn = document.getElementById('resetAdvancedSearch');

        if (applyAdvancedSearchBtn) {
            applyAdvancedSearchBtn.addEventListener('click', function() {
                const filters = buildAdvancedFilters();
                currentAdvancedFilters = {};
                filters.forEach(f => { currentAdvancedFilters[f.field] = f.value; });
                table.setData('dashboard_data.php', { type: initialFilterType, ...currentAdvancedFilters });
            });
        }
        if (resetAdvancedSearchBtn) {
            resetAdvancedSearchBtn.addEventListener('click', function() {
                advancedSearchForm.reset();
                currentAdvancedFilters = {};
                table.setData('dashboard_data.php', { type: initialFilterType });
            });
        }

        function buildAdvancedFilters() {
            const filters = [];
            const lastName = document.getElementById('adv_search_last_name').value.trim();
            const firstName = document.getElementById('adv_search_first_name').value.trim();
            const middleName = document.getElementById('adv_search_middle_name').value.trim();
            const gender = document.getElementById('adv_search_gender').value;
            const ageMin = document.getElementById('adv_search_age_min').value;
            const ageMax = document.getElementById('adv_search_age_max').value;
            const birthdateFrom = document.getElementById('adv_search_birthdate_from').value;
            const birthdateTo = document.getElementById('adv_search_birthdate_to').value;

            if (lastName) filters.push({ field: 'last_name', type: 'like', value: lastName });
            if (firstName) filters.push({ field: 'first_name', type: 'like', value: firstName });
            if (middleName) filters.push({ field: 'middle_name', type: 'like', value: middleName });
            if (gender) filters.push({ field: 'gender', type: '=', value: gender });
            if (ageMin) filters.push({ field: 'age', type: '>=', value: ageMin });
            if (ageMax) filters.push({ field: 'age', type: '<=', value: ageMax });
            if (birthdateFrom) filters.push({ field: 'birthdate', type: '>=', value: birthdateFrom });
            if (birthdateTo) filters.push({ field: 'birthdate', type: '<=', value: birthdateTo });
            return filters;
        }

        // Action Button Handlers
        // REMOVE the old navigation logic for addNewResidentBtn to avoid duplicate declaration and errors
        // const addNewResidentBtn = document.getElementById('addNewResidentBtn');
        // if(addNewResidentBtn) {
        //     addNewResidentBtn.addEventListener('click', () => window.location.href = 'add_individual.php');
        // }

        // Export Handlers
        function setupExportButton(buttonId, format, selectedOnly = true) {
            const btn = document.getElementById(buttonId);
            if (btn) {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (table) {
                        const filename = "<?php echo str_replace(' ', '_', $page_title); ?>_export";
                        if (selectedOnly) {
                            if (table.getSelectedData().length === 0) {
                                alert("No rows selected to export.");
                                exportDropdownMenu.classList.remove('show');
                                return;
                            }
                            // For selected, Tabulator's download function uses selected data if rows are selected
                        }
                        
                        let options = {};
                        if (format === "pdf") {
                             const allData = selectedOnly ? table.getSelectedData() : table.getData("active");
                             if (allData.length === 0) {
                                alert(selectedOnly ? "No rows selected to export." : "No data to export.");
                                exportDropdownMenu.classList.remove('show');
                                return;
                            }
                            const pdfColumns = table.getColumnDefinitions().filter(col => col.visible !== false && col.field && col.download !== false);
                            const head = [pdfColumns.map(col => col.title)];
                            const body = allData.map(row => pdfColumns.map(col => {
                                let val = row[col.field];
                                // Apply formatter if exists (basic example)
                                if (col.formatter && typeof col.formatter === 'function' && col.field === 'gender') { // Example for gender
                                     val = val === 'male' ? 'Male' : (val === 'female' ? 'Female' : '');
                                } else if (col.formatter && typeof col.formatter === 'function' && col.field === 'age') { // Example for age
                                     val = val ? val + " yrs" : "";
                                }
                                return val !== undefined && val !== null ? val : '';
                            }));
                            
                            const { jsPDF } = window.jspdf;
                            const doc = new jsPDF({ orientation: 'landscape' });
                            doc.setFontSize(18);
                            doc.text("<?php echo $page_title; ?>", 14, 12);
                            doc.setFontSize(10);
                            doc.text("Exported on: " + new Date().toLocaleDateString(), 14, 18);

                            doc.autoTable({
                                head: head,
                                body: body,
                                startY: 22,
                                headStyles: { fillColor: [22, 160, 133], textColor: 255, fontStyle: 'bold' },
                                alternateRowStyles: { fillColor: [245, 245, 245] },
                                tableLineColor: [189, 195, 199], tableLineWidth: 0.1,
                                didDrawPage: function (data) {
                                    // Footer
                                    let str = "Page " + doc.internal.getNumberOfPages();
                                    doc.setFontSize(10);
                                    doc.text(str, data.settings.margin.left, doc.internal.pageSize.height - 10);
                                }
                            });
                            doc.save(`${filename}.${format}`);
                        } else { // CSV and XLSX
                            options = format === "xlsx" ? {sheetName: "Residents"} : {};
                            table.download(format, `${filename}.${format}`, options, selectedOnly ? "selected" : "active");
                        }
                    }
                    exportDropdownMenu.classList.remove('show');
                });
            }
        }

        setupExportButton('exportCsvBtn', 'csv', true);
        setupExportButton('exportXlsxBtn', 'xlsx', true);
        setupExportButton('exportPdfBtn', 'pdf', true);
        setupExportButton('exportAllCsvBtn', 'csv', false);
        setupExportButton('exportAllXlsxBtn', 'xlsx', false);
        setupExportButton('exportAllPdfBtn', 'pdf', false);


        // Print Selected Handler
        const printSelectedBtn = document.getElementById('printSelectedBtn');
        if(printSelectedBtn){
            printSelectedBtn.addEventListener('click', () => {
                const selectedData = table.getSelectedData();
                if(selectedData.length > 0){
                    const idsToPrint = selectedData.map(item => item.id);
                    if (idsToPrint.length > 0) {
                        const url = `print_individuals.php?ids=${idsToPrint.join(',')}`;
                        window.open(url, '_blank');
                    } else {
                        alert("No valid IDs found for selected residents.");
                    }
                } else {
                    alert("No rows selected to print.");
                }
            });
        }

        // Delete Selected Handler (Batch Delete)
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        if(deleteSelectedBtn){
            deleteSelectedBtn.addEventListener('click', () => {
                const selectedRows = table.getSelectedRows();
                const selectedData = table.getSelectedData(); 

                if(selectedRows.length > 0){
                    if(confirm(`Are you sure you want to delete ${selectedRows.length} selected resident(s)? This action cannot be undone.`)){
                        const idsToDelete = selectedData.map(item => item.id);
                        
                        fetch('delete_individuals.php', { 
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', "X-Requested-With": "XMLHttpRequest" },
                            body: JSON.stringify({ ids: idsToDelete }) 
                        })
                        .then(response => response.json())
                        .then(function(data) {
                            if(data.success){
                                table.setData(); // Refresh table data
                                table.clearSelection();
                                alert(data.message || 'Successfully deleted selected resident(s).');
                            } else {
                                alert('Error deleting resident(s): ' + (data.message || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while trying to delete resident(s). Please check the console for details.');
                        });
                    }
                } else {
                    alert("No rows selected to delete.");
                }
            });
        }

        // Functions for row actions (View, Edit, Single Delete)
        window.viewResident = function(id) { // Make it global for inline onclick
            window.location.href = `view_individual.php?id=${id}`;
        }

        window.editResident = function(id) { // Make it global
            window.location.href = `edit_individual.php?id=${id}`;
        }
        
        window.deleteSingleResident = function(id, name) { // Make it global
            if (confirm(`Are you sure you want to delete resident: ${name} (ID: ${id})? This action cannot be undone.`)) {
                fetch('delete_individuals.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', "X-Requested-With": "XMLHttpRequest" },
                    body: JSON.stringify({ ids: [id] }) // Send as an array with one ID
                })
                .then(response => response.json())
                .then(function(data) {
                    if (data.success) {
                        table.setData(); // Refresh table
                        alert(data.message || `Successfully deleted resident ${name}.`);
                    } else {
                        alert(`Error deleting resident ${name}: ` + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(`An error occurred while trying to delete resident ${name}.`);
                });
            }
        }

        // Modal logic for Add Resident
        const addResidentModal = document.getElementById('addResidentModal');
        const addNewResidentBtn = document.getElementById('addNewResidentBtn');
        const closeAddResidentModal = document.getElementById('closeAddResidentModal');
        const cancelAddResidentBtn = document.getElementById('cancelAddResidentBtn');

        function openAddResidentModal() {
            addResidentModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
        function closeAddResident() {
            addResidentModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            document.getElementById('addResidentForm').reset();
            document.getElementById('addResidentMessage').innerHTML = '';
        }
        if (addNewResidentBtn) addNewResidentBtn.addEventListener('click', openAddResidentModal);
        if (closeAddResidentModal) closeAddResidentModal.addEventListener('click', closeAddResident);
        if (cancelAddResidentBtn) cancelAddResidentBtn.addEventListener('click', closeAddResident);

        // AJAX form submission
        const addResidentForm = document.getElementById('addResidentForm');
        addResidentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(addResidentForm);
            fetch('add_individual_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const msgDiv = document.getElementById('addResidentMessage');
                if (data.success) {
                    msgDiv.innerHTML = '<div class="bg-green-100 text-green-700 p-3 rounded mb-2">' + data.message + '</div>';
                    setTimeout(() => {
                        closeAddResident();
                        // Optionally refresh the residents table here
                        location.reload();
                    }, 1200);
                } else {
                    msgDiv.innerHTML = '<div class="bg-red-100 text-red-700 p-3 rounded mb-2">' + data.message + '</div>';
                }
            })
            .catch(() => {
                document.getElementById('addResidentMessage').innerHTML = '<div class="bg-red-100 text-red-700 p-3 rounded mb-2">An error occurred. Please try again.</div>';
            });
        });
    </script>
    <?php if(isset($conn)) $conn->close(); ?>
</body>
</html>
