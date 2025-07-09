<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    // Log the detailed error for yourself
    error_log('Database connection failed: ' . $conn->connect_error);
    // Show a friendly message to the user
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
    <title>Document Generation - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Custom thin scrollbar for sidepanel */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #2563eb #353535;
            padding-right: 6px; /* Always reserve space for scrollbar */
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
        /* Always show scrollbar track to prevent layout shift */
        .custom-scrollbar {
            overflow-y: scroll;
        }
        .custom-scrollbar::-webkit-scrollbar {
            background: #353535;
        }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        
        /* Certificate card styles */
        .certificate-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }
        .form-radio {
            accent-color: #667eea;
            width: 1rem;
            height: 1rem;
        }
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
            // Fetch barangay logo from 'system_settings' table
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
        <nav class="flex flex-col p-4 gap-2 text-white">
            <?php
            // --- Sidepanel Navigation ---
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

            // People Management
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
            // Barangay Documents
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
            // System Settings
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

    <!-- Main Content -->
    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full max-w-7xl px-4 md:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <i class="fas fa-certificate text-blue-600 text-xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-800">Document Generation</h1>
                </div>
                <p class="text-gray-600">Generate barangay certificates, clearances, and identification documents for residents</p>
            </div>

            <!-- Certificate Types Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Barangay Clearance -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('clearance')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-file-alt text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">01</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Barangay Clearance</h3>
                    <p class="text-white/80">General clearance certificate for residents</p>
                </div>

                <!-- Certificate of First Time Job Seeker -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('first_time_job_seeker')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-user-graduate text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">02</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">First Time Job Seeker</h3>
                    <p class="text-white/80">Certificate for first-time job seekers</p>
                </div>

                <!-- Certificate of Residency -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('residency')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-home text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">03</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Certificate of Residency</h3>
                    <p class="text-white/80">Proof of residence in the barangay</p>
                </div>

                <!-- Certificate of Indigency -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('indigency')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-hand-holding-heart text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">04</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Certificate of Indigency</h3>
                    <p class="text-white/80">Financial status certification</p>
                </div>

                <!-- Barangay ID -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('barangay_id')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-id-card text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">05</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Barangay ID</h3>
                    <p class="text-white/80">Official barangay identification card</p>
                </div>
            </div>

            <!-- Recent Certificates -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-history text-blue-600"></i>
                        Recent Certificates
                    </h2>
                    <button class="btn-secondary" onclick="location.href='issued_documents.php'">
                        <i class="fas fa-list mr-2"></i>View All
                    </button>
                </div>
                
                <!-- Recent certificates table placeholder -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Certificate No.</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Resident Name</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Certificate Type</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Date Issued</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Purpose</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recentCertificatesTable">
                            <!-- Dynamic content will be loaded here -->
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                    No recent certificates found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificate Generation Modal -->
    <div id="certificateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Generate Document</h3>
                <button id="closeCertificateModal" class="text-gray-500 hover:text-red-500 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="certificateForm" class="p-6">
                <input type="hidden" id="certificateType" name="certificate_type">
                
                <!-- Resident Selection Options -->
                <div class="mb-6">
                    <div class="flex items-center gap-4 mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="resident_option" value="existing" checked class="form-radio">
                            <span class="text-sm font-medium">Select Existing Resident</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="resident_option" value="new" class="form-radio">
                            <span class="text-sm font-medium">New Resident (Not Registered)</span>
                        </label>
                    </div>
                </div>

                <!-- Existing Resident Selection -->
                <div id="existingResidentSection">
                    <div class="form-group">
                        <label class="form-label" for="residentSelect">
                            <i class="fas fa-user mr-2"></i>Select Resident
                        </label>
                        <select class="form-input form-select" id="residentSelect" name="resident_id">
                            <option value="">Choose a resident...</option>
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                    
                    <div class="text-center my-4">
                        <button type="button" id="registerNewResidentBtn" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <i class="fas fa-plus mr-1"></i>Resident not on the list? Register them now
                        </button>
                    </div>
                </div>

                <!-- New Resident Form -->
                <div id="newResidentSection" style="display: none;">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            Enter the resident information manually. This will generate the certificate without registering them in the system.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label" for="newFirstName">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" class="form-input" id="newFirstName" name="first_name" placeholder="Enter first name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="newMiddleName">
                                Middle Name
                            </label>
                            <input type="text" class="form-input" id="newMiddleName" name="middle_name" placeholder="Enter middle name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="newLastName">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" class="form-input" id="newLastName" name="last_name" placeholder="Enter last name">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="newSuffix">
                                Suffix
                            </label>
                            <select class="form-input form-select" id="newSuffix" name="suffix">
                                <option value="">None</option>
                                <option value="Jr.">Jr.</option>
                                <option value="Sr.">Sr.</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="newBirthdate">
                                Birthdate
                            </label>
                            <input type="date" class="form-input" id="newBirthdate" name="birthdate">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="newGender">
                                Gender
                            </label>
                            <select class="form-input form-select" id="newGender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="newCivilStatus">
                                Civil Status
                            </label>
                            <select class="form-input form-select" id="newCivilStatus" name="civil_status">
                                <option value="">Select Civil Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="newPurok">
                                Purok/Address
                            </label>
                            <input type="text" class="form-input" id="newPurok" name="purok" placeholder="Enter purok or address">
                        </div>
                    </div>
                </div>

                <!-- Purpose -->
                <div class="form-group">
                    <label class="form-label" for="purpose">
                        <i class="fas fa-info-circle mr-2"></i>Purpose
                    </label>
                    <input type="text" class="form-input" id="purpose" name="purpose" placeholder="Enter the purpose of the certificate" required>
                </div>

                <!-- Additional fields will be shown based on certificate type -->
                <div id="additionalFields"></div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4">
                    <button type="button" class="btn-secondary flex-1" onclick="closeCertificateModal()">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn-primary flex-1">
                        <i class="fas fa-file-pdf mr-2"></i>Generate Document
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Global variables
        let preSelectedResidentId = null;

        // Check URL parameters for pre-selected resident
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            preSelectedResidentId = urlParams.get('resident_id');
            
            if (preSelectedResidentId) {
                // If coming from individuals page, show certificate selection
                showCertificateSelectionForResident(preSelectedResidentId);
            }
        });

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
                const dropdown = document.getElementById(dropId);
                if (dropId === id) {
                    dropdown.classList.toggle('dropdown-open');
                    dropdown.classList.toggle('dropdown-closed');
                } else {
                    dropdown.classList.remove('dropdown-open');
                    dropdown.classList.add('dropdown-closed');
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

        // Close user dropdown if clicked outside
        document.addEventListener('click', (e) => {
            if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });

        // Certificate Modal Functions
        const certificateModal = document.getElementById('certificateModal');
        const closeCertificateModalBtn = document.getElementById('closeCertificateModal');

        function showCertificateSelectionForResident(residentId) {
            // Show a certificate type selection modal first
            if (confirm('Select a certificate type for this resident. Click OK to continue to certificate selection.')) {
                // For now, just open the clearance modal with the resident pre-selected
                openCertificateModal('clearance', residentId);
            }
        }

        function openCertificateModal(type, residentId = null) {
            const titles = {
                'clearance': 'Generate Barangay Clearance',
                'first_time_job_seeker': 'Generate Certificate of First Time Job Seeker',
                'residency': 'Generate Certificate of Residency',
                'indigency': 'Generate Certificate of Indigency',
                'barangay_id': 'Generate Barangay ID'
            };
            
            document.getElementById('modalTitle').textContent = titles[type] || 'Generate Document';
            document.getElementById('certificateType').value = type;
            
            // Load additional fields based on certificate type
            loadAdditionalFields(type);
            
            // Load residents
            loadResidents(() => {
                // Auto-select resident if provided
                if (residentId || preSelectedResidentId) {
                    const selectResidentId = residentId || preSelectedResidentId;
                    const residentSelect = document.getElementById('residentSelect');
                    residentSelect.value = selectResidentId;
                    
                    // Clear the URL parameter after using it
                    if (preSelectedResidentId) {
                        const url = new URL(window.location);
                        url.searchParams.delete('resident_id');
                        window.history.replaceState({}, document.title, url.toString());
                        preSelectedResidentId = null;
                    }
                }
            });
            
            certificateModal.classList.remove('hidden');
            certificateModal.style.display = 'flex';
        }

        function closeCertificateModal() {
            certificateModal.classList.add('hidden');
            certificateModal.style.display = 'none';
            document.getElementById('certificateForm').reset();
            
            // Reset to existing resident option
            document.querySelector('input[name="resident_option"][value="existing"]').checked = true;
            toggleResidentSections();
        }

        closeCertificateModalBtn.addEventListener('click', closeCertificateModal);

        // Close modal when clicking outside
        certificateModal.addEventListener('click', (e) => {
            if (e.target === certificateModal) {
                closeCertificateModal();
            }
        });

        // Handle resident option toggle
        document.addEventListener('change', function(e) {
            if (e.target.name === 'resident_option') {
                toggleResidentSections();
            }
        });

        function toggleResidentSections() {
            const existingSection = document.getElementById('existingResidentSection');
            const newSection = document.getElementById('newResidentSection');
            const selectedOption = document.querySelector('input[name="resident_option"]:checked').value;
            
            if (selectedOption === 'existing') {
                existingSection.style.display = 'block';
                newSection.style.display = 'none';
                
                // Make existing resident fields required
                document.getElementById('residentSelect').required = true;
                
                // Remove required from new resident fields
                document.getElementById('newFirstName').required = false;
                document.getElementById('newLastName').required = false;
            } else {
                existingSection.style.display = 'none';
                newSection.style.display = 'block';
                
                // Remove required from existing resident field
                document.getElementById('residentSelect').required = false;
                
                // Make new resident fields required
                document.getElementById('newFirstName').required = true;
                document.getElementById('newLastName').required = true;
            }
        }

        // Register new resident button
        document.addEventListener('click', function(e) {
            if (e.target.id === 'registerNewResidentBtn') {
                e.preventDefault();
                // Switch to new resident option
                document.querySelector('input[name="resident_option"][value="new"]').checked = true;
                toggleResidentSections();
            }
        });

        function loadAdditionalFields(type) {
            const additionalFields = document.getElementById('additionalFields');
            additionalFields.innerHTML = '';

            switch(type) {
                case 'first_time_job_seeker':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="age">
                                <i class="fas fa-calendar mr-2"></i>Age
                            </label>
                            <input type="number" class="form-input" id="age" name="age" placeholder="Enter age" min="15" max="30">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="educationalAttainment">
                                <i class="fas fa-graduation-cap mr-2"></i>Educational Attainment
                            </label>
                            <select class="form-input form-select" id="educationalAttainment" name="educational_attainment">
                                <option value="">Select educational attainment</option>
                                <option value="Elementary Graduate">Elementary Graduate</option>
                                <option value="High School Graduate">High School Graduate</option>
                                <option value="Senior High School Graduate">Senior High School Graduate</option>
                                <option value="College Graduate">College Graduate</option>
                                <option value="Vocational/Technical Graduate">Vocational/Technical Graduate</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                    `;
                    break;
                case 'indigency':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="familyIncome">
                                <i class="fas fa-money-bill-wave mr-2"></i>Monthly Family Income
                            </label>
                            <select class="form-input form-select" id="familyIncome" name="family_income">
                                <option value="">Select income range</option>
                                <option value="Below ₱5,000">Below ₱5,000</option>
                                <option value="₱5,000 - ₱10,000">₱5,000 - ₱10,000</option>
                                <option value="₱10,001 - ₱15,000">₱10,001 - ₱15,000</option>
                                <option value="₱15,001 - ₱20,000">₱15,001 - ₱20,000</option>
                                <option value="Above ₱20,000">Above ₱20,000</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="familyMembers">
                                <i class="fas fa-users mr-2"></i>Number of Family Members
                            </label>
                            <input type="number" class="form-input" id="familyMembers" name="family_members" placeholder="Enter number of family members" min="1">
                        </div>
                    `;
                    break;
                case 'residency':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="yearsOfResidency">
                                <i class="fas fa-clock mr-2"></i>Years of Residency
                            </label>
                            <input type="number" class="form-input" id="yearsOfResidency" name="years_of_residency" placeholder="Enter number of years as resident" min="0">
                        </div>
                    `;
                    break;
                case 'barangay_id':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="emergencyContact">
                                <i class="fas fa-phone mr-2"></i>Emergency Contact Name
                            </label>
                            <input type="text" class="form-input" id="emergencyContact" name="emergency_contact" placeholder="Enter emergency contact name">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emergencyContactNumber">
                                <i class="fas fa-phone-alt mr-2"></i>Emergency Contact Number
                            </label>
                            <input type="tel" class="form-input" id="emergencyContactNumber" name="emergency_contact_number" placeholder="Enter emergency contact number">
                        </div>
                    `;
                    break;
                // For clearance, no additional fields needed
                case 'clearance':
                default:
                    // No additional fields for basic clearance
                    break;
            }
        }

        function loadResidents(callback = null) {
            const residentSelect = document.getElementById('residentSelect');
            residentSelect.innerHTML = '<option value="">Loading residents...</option>';
            
            fetch('fetch_individuals.php')
                .then(response => response.json())
                .then(data => {
                    residentSelect.innerHTML = '<option value="">Choose a resident...</option>';
                    data.forEach(resident => {
                        const option = document.createElement('option');
                        option.value = resident.id;
                        option.textContent = `${resident.first_name} ${resident.middle_name || ''} ${resident.last_name} ${resident.suffix || ''}`.trim();
                        residentSelect.appendChild(option);
                    });
                    
                    if (callback) callback();
                })
                .catch(error => {
                    console.error('Error loading residents:', error);
                    residentSelect.innerHTML = '<option value="">Error loading residents</option>';
                });
        }

        // Form submission
        document.getElementById('certificateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form based on selected option
            const selectedOption = document.querySelector('input[name="resident_option"]:checked').value;
            
            if (selectedOption === 'existing') {
                const residentId = document.getElementById('residentSelect').value;
                if (!residentId) {
                    showNotification('Please select a resident or switch to manual entry.', 'error');
                    return;
                }
            } else {
                const firstName = document.getElementById('newFirstName').value.trim();
                const lastName = document.getElementById('newLastName').value.trim();
                
                if (!firstName || !lastName) {
                    showNotification('Please enter at least the first name and last name.', 'error');
                    return;
                }
            }
            
            // Collect form data
            const formData = new FormData(this);
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
            submitBtn.disabled = true;
            
            // Submit to backend
            fetch('generate_certificate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Handle successful generation
                    showNotification('Certificate generated successfully!', 'success');
                    closeCertificateModal();
                    
                    // Download the certificate
                    if (data.download_url) {
                        window.open(data.download_url, '_blank');
                    }
                } else {
                    showNotification('Error generating certificate: ' + (data.error || data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while generating the certificate.', 'error');
            })
            .finally(() => {
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
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
