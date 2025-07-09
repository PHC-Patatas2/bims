<?php
// settings.php
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
    <title>General Settings - <?php echo htmlspecialchars($system_title); ?></title>
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
        
        /* Enhanced Settings Styles */
        .settings-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .settings-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .tab-button {
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .tab-button::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .tab-button.active::before {
            transform: scaleX(1);
        }
        
        .tab-button:hover {
            background: rgba(59, 130, 246, 0.05);
        }
        
        .input-field {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .input-field:focus {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.25);
        }
        
        .action-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-gradient, linear-gradient(90deg, #3b82f6, #1d4ed8));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .action-card:hover::before {
            transform: scaleX(1);
        }
        
        .action-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px -12px rgba(59, 130, 246, 0.25);
            border-color: #3b82f6;
        }
        
        .action-icon {
            background: linear-gradient(135deg, var(--icon-color-1), var(--icon-color-2));
            transition: all 0.3s ease;
        }
        
        .action-card:hover .action-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .save-button {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .save-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .save-button:hover::before {
            left: 100%;
        }
        
        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.4);
        }
        
        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .notification-slide {
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col settings-container">
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
            <!-- Settings Tabs -->
            <div class="settings-card rounded-xl shadow mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex">
                        <button onclick="switchTab('general')" id="generalTab" class="tab-button active px-6 py-4 font-medium text-sm border-b-2 border-blue-500 text-blue-600">
                            <i class="fas fa-cog mr-2"></i>General Settings
                        </button>
                        <button onclick="switchTab('system')" id="systemTab" class="tab-button px-6 py-4 font-medium text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            <i class="fas fa-server mr-2"></i>System Configuration
                        </button>
                        <button onclick="switchTab('appearance')" id="appearanceTab" class="tab-button px-6 py-4 font-medium text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            <i class="fas fa-palette mr-2"></i>Appearance
                        </button>
                    </nav>
                </div>

                <!-- General Settings Tab -->
                <div id="generalContent" class="tab-content p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">General Settings</h3>
                    <form id="generalSettingsForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">System Title</label>
                                <input type="text" id="systemTitle" value="" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter system title">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Barangay Name</label>
                                <input type="text" id="barangayName" value="" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter barangay name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Municipality/City</label>
                                <input type="text" id="municipality" value="" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter municipality or city">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                                <input type="text" id="province" value="" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter province">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Complete Address</label>
                            <textarea id="address" rows="3" 
                                      class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Enter complete barangay address"></textarea>
                        </div>
                    </form>
                </div>

                <!-- System Configuration Tab -->
                <div id="systemContent" class="tab-content p-6 hidden">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">System Configuration</h3>
                    <form id="systemSettingsForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Records Per Page</label>
                                <select id="recordsPerPage" class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Session Timeout (minutes)</label>
                                <input type="number" id="sessionTimeout" value="" min="5" max="480" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter session timeout">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Appearance Tab -->
                <div id="appearanceContent" class="tab-content p-6 hidden">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Appearance Settings</h3>
                    <form id="appearanceSettingsForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Primary Color</label>
                                <div class="flex space-x-2">
                                    <input type="color" id="primaryColor" value="#2563eb" class="w-12 h-12 border border-gray-300 rounded cursor-pointer">
                                    <input type="text" id="primaryColorHex" value="#2563eb" 
                                           class="input-field flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Save Button -->
                <div class="p-6 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <button onclick="resetSettings()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                            <i class="fas fa-undo mr-2"></i>Reset to Defaults
                        </button>
                        <div class="space-x-3">
                            <button onclick="previewSettings()" class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white px-6 py-3 rounded-lg hover:from-yellow-600 hover:to-yellow-700 transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-eye mr-2"></i>Preview
                            </button>
                            <button onclick="saveSettings()" id="saveButton" class="save-button text-white px-8 py-3 rounded-lg transition-all duration-300 transform hover:scale-105">
                                <span id="saveButtonText">
                                    <i class="fas fa-save mr-2"></i>Save Settings
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="action-card rounded-xl shadow-lg p-6 text-center" style="--card-gradient: linear-gradient(90deg, #3b82f6, #1d4ed8); --icon-color-1: #3b82f6; --icon-color-2: #1d4ed8;">
                    <div class="action-icon w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-download text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Backup Data</h3>
                    <p class="text-sm text-gray-600 mb-6">Create a backup of system data</p>
                    <button onclick="backupData()" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-blue-700 w-full transition-all duration-300 transform hover:scale-105">
                        Backup Now
                    </button>
                </div>
                <div class="action-card rounded-xl shadow-lg p-6 text-center" style="--card-gradient: linear-gradient(90deg, #10b981, #059669); --icon-color-1: #10b981; --icon-color-2: #059669;">
                    <div class="action-icon w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-upload text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Restore Data</h3>
                    <p class="text-sm text-gray-600 mb-6">Restore from backup file</p>
                    <button onclick="restoreData()" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg hover:from-green-600 hover:to-green-700 w-full transition-all duration-300 transform hover:scale-105">
                        Restore
                    </button>
                </div>
                <div class="action-card rounded-xl shadow-lg p-6 text-center" style="--card-gradient: linear-gradient(90deg, #f59e0b, #d97706); --icon-color-1: #f59e0b; --icon-color-2: #d97706;">
                    <div class="action-icon w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-sync text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Clear Cache</h3>
                    <p class="text-sm text-gray-600 mb-6">Clear system cache files</p>
                    <button onclick="clearCache()" class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white px-6 py-3 rounded-lg hover:from-yellow-600 hover:to-yellow-700 w-full transition-all duration-300 transform hover:scale-105">
                        Clear Cache
                    </button>
                </div>
                <div class="action-card rounded-xl shadow-lg p-6 text-center" style="--card-gradient: linear-gradient(90deg, #ef4444, #dc2626); --icon-color-1: #ef4444; --icon-color-2: #dc2626;">
                    <div class="action-icon w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-tools text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Maintenance</h3>
                    <p class="text-sm text-gray-600 mb-6">System maintenance mode</p>
                    <button onclick="toggleMaintenance()" class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-3 rounded-lg hover:from-red-600 hover:to-red-700 w-full transition-all duration-300 transform hover:scale-105">
                        Toggle Mode
                    </button>
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

        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });

            // Show selected tab content
            document.getElementById(tabName + 'Content').classList.remove('hidden');

            // Add active class to selected tab button
            const activeTab = document.getElementById(tabName + 'Tab');
            activeTab.classList.add('active', 'border-blue-500', 'text-blue-600');
            activeTab.classList.remove('border-transparent', 'text-gray-500');
        }

        // Settings functions
        let isLoading = false;

        function loadSettings() {
            if (isLoading) return;
            isLoading = true;
            
            fetch('load_settings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate general settings
                        document.getElementById('systemTitle').value = data.settings.system_title || '';
                        document.getElementById('barangayName').value = data.settings.barangay_name || '';
                        document.getElementById('municipality').value = data.settings.municipality || '';
                        document.getElementById('province').value = data.settings.province || '';
                        document.getElementById('address').value = data.settings.barangay_address || '';
                        
                        // Populate system settings
                        document.getElementById('recordsPerPage').value = data.settings.records_per_page || '25';
                        document.getElementById('sessionTimeout').value = data.settings.session_timeout || '30';
                        
                        // Populate appearance settings
                        document.getElementById('primaryColor').value = data.settings.primary_color || '#2563eb';
                        document.getElementById('primaryColorHex').value = data.settings.primary_color || '#2563eb';
                        
                        showNotification('Settings loaded successfully', 'success');
                    } else {
                        showNotification('Failed to load settings: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading settings:', error);
                    showNotification('Error loading settings', 'error');
                })
                .finally(() => {
                    isLoading = false;
                });
        }

        function saveSettings() {
            if (isLoading) return;
            
            const saveButton = document.getElementById('saveButton');
            const saveButtonText = document.getElementById('saveButtonText');
            const originalText = saveButtonText.innerHTML;
            
            // Show loading state
            saveButtonText.innerHTML = '<div class="loading-spinner inline-block mr-2"></div>Saving...';
            saveButton.disabled = true;
            isLoading = true;
            
            // Collect all form data
            const settingsData = {
                // General Settings
                system_title: document.getElementById('systemTitle').value,
                barangay_name: document.getElementById('barangayName').value,
                municipality: document.getElementById('municipality').value,
                province: document.getElementById('province').value,
                address: document.getElementById('address').value,
                
                // System Settings
                records_per_page: document.getElementById('recordsPerPage').value,
                session_timeout: document.getElementById('sessionTimeout').value,
                
                // Appearance Settings
                primary_color: document.getElementById('primaryColor').value
            };

            fetch('save_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(settingsData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Refresh the page title if system title changed
                    if (settingsData.system_title) {
                        document.title = 'General Settings - ' + settingsData.system_title;
                    }
                } else {
                    showNotification('Failed to save settings: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error saving settings:', error);
                showNotification('Error saving settings', 'error');
            })
            .finally(() => {
                // Restore button state
                saveButtonText.innerHTML = originalText;
                saveButton.disabled = false;
                isLoading = false;
            });
        }

        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
                // Reset to default values
                document.getElementById('systemTitle').value = 'Resident Information and Certification Management System';
                document.getElementById('barangayName').value = 'Barangay Sample';
                document.getElementById('municipality').value = 'Sample City';
                document.getElementById('province').value = 'Sample Province';
                document.getElementById('address').value = 'Sample Street, Sample City, Sample Province';
                
                // Reset system settings
                document.getElementById('recordsPerPage').value = '25';
                document.getElementById('sessionTimeout').value = '30';
                
                // Reset appearance settings
                document.getElementById('primaryColor').value = '#2563eb';
                document.getElementById('primaryColorHex').value = '#2563eb';

                showNotification('Settings reset to defaults', 'info');
            }
        }

        function previewSettings() {
            showNotification('Preview mode activated. Changes are temporary.', 'info');
            // Apply settings temporarily for preview
            applyAppearanceSettings();
        }

        function applyAppearanceSettings() {
            const theme = document.getElementById('theme').value;
            const primaryColor = document.getElementById('primaryColor').value;
            const fontSize = document.getElementById('fontSize').value;

            // Apply theme
            if (theme === 'dark') {
                document.body.classList.add('dark-theme');
            } else {
                document.body.classList.remove('dark-theme');
            }

            // Apply primary color
            document.documentElement.style.setProperty('--primary-color', primaryColor);

            // Apply font size
            const fontSizeMap = {
                'small': '14px',
                'medium': '16px',
                'large': '18px'
            };
            document.documentElement.style.setProperty('--base-font-size', fontSizeMap[fontSize]);
        }

        // Quick action functions
        function backupData() {
            if (confirm('Do you want to create a backup of all system data?')) {
                showNotification('Creating backup...', 'info');
                // Simulate backup process
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = '#'; // Replace with actual backup endpoint
                    link.download = `bims_backup_${new Date().toISOString().split('T')[0]}.sql`;
                    showNotification('Backup created successfully!', 'success');
                }, 2000);
            }
        }

        function restoreData() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.sql,.zip';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (confirm(`Are you sure you want to restore from "${file.name}"? This will overwrite all current data.`)) {
                        showNotification('Restoring data...', 'info');
                        // Simulate restore process
                        setTimeout(() => {
                            showNotification('Data restored successfully!', 'success');
                        }, 3000);
                    }
                }
            };
            input.click();
        }

        function clearCache() {
            if (confirm('Do you want to clear all system cache files?')) {
                showNotification('Clearing cache...', 'info');
                // Simulate cache clearing
                setTimeout(() => {
                    showNotification('Cache cleared successfully!', 'success');
                }, 1000);
            }
        }

        function toggleMaintenance() {
            const isMaintenanceMode = document.body.classList.contains('maintenance-mode');
            if (isMaintenanceMode) {
                if (confirm('Do you want to disable maintenance mode? The system will be available to users.')) {
                    document.body.classList.remove('maintenance-mode');
                    showNotification('Maintenance mode disabled', 'success');
                }
            } else {
                if (confirm('Do you want to enable maintenance mode? This will temporarily disable the system for users.')) {
                    document.body.classList.add('maintenance-mode');
                    showNotification('Maintenance mode enabled', 'warning');
                }
            }
        }

        // Color picker sync
        document.getElementById('primaryColor').addEventListener('change', function() {
            document.getElementById('primaryColorHex').value = this.value;
        });

        document.getElementById('primaryColorHex').addEventListener('change', function() {
            document.getElementById('primaryColor').value = this.value;
        });

        // Logo file preview
        document.getElementById('logoFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('logoPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification-slide fixed top-20 right-4 p-4 rounded-xl shadow-xl z-50 transform transition-all duration-300 ${
                type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' : 
                type === 'error' ? 'bg-gradient-to-r from-red-500 to-red-600 text-white' : 
                type === 'warning' ? 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white' :
                'bg-gradient-to-r from-blue-500 to-blue-600 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-3">
                        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
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

        // Initialize settings on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
        });
    </script>
</body>
</html>
