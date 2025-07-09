<?php
// reports.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
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
    <title>Generate Reports - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .custom-scrollbar { scrollbar-width: thin; scrollbar-color: #2563eb #353535; padding-right: 6px; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #2563eb; border-radius: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #353535; }
        .custom-scrollbar { overflow-y: scroll; }
        .custom-scrollbar::-webkit-scrollbar { background: #353535; }
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
    </style>
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
    <div class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <div class="flex items-center mb-4">
                <h1 class="text-2xl font-bold">Generate Reports</h1>
            </div>
            <!-- Reports Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Resident Demographics Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Demographics Report</h3>
                            <p class="text-sm text-gray-600">Population statistics by age, gender, and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('demographics')" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Voter Statistics Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-vote-yea text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Voter Statistics</h3>
                            <p class="text-sm text-gray-600">Registered voters and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('voters')" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- PWD Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-wheelchair text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">PWD Report</h3>
                            <p class="text-sm text-gray-600">Persons with Disabilities and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('pwd')" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Senior Citizens Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-user-clock text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Senior Citizens</h3>
                            <p class="text-sm text-gray-600">Residents 60 years old and above, and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('seniors')" class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- 4Ps Recipients Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-red-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-hand-holding-heart text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">4Ps Recipients</h3>
                            <p class="text-sm text-gray-600">Pantawid Pamilyang Pilipino beneficiaries and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('4ps')" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Solo Parents Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-pink-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-user-friends text-pink-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Solo Parents</h3>
                            <p class="text-sm text-gray-600">Single parent households and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('solo_parents')" class="w-full bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Certificate Statistics -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-teal-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-certificate text-teal-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Certificate Statistics</h3>
                            <p class="text-sm text-gray-600">Issued certificates and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('certificates')" class="w-full bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>



                <!-- Purok Summary -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-yellow-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-map-marked-alt text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Purok Summary</h3>
                            <p class="text-sm text-gray-600">Population by purok/sitio</p>
                        </div>
                    </div>
                    <button onclick="generateReport('purok')" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
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

        // Report functions
        function generateReport(type) {
            // Show loading indicator
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
            button.disabled = true;

            // Simulate report generation (replace with actual implementation)
            setTimeout(() => {
                // Create a link to download the report
                const link = document.createElement('a');
                link.href = `generate_report.php?type=${type}&format=pdf`;
                link.download = `${type}_report_${new Date().toISOString().split('T')[0]}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Reset button
                button.innerHTML = originalText;
                button.disabled = false;

                // Show success message
                showNotification('Report generated successfully!', 'success');
            }, 2000);
        }

        function openCustomReportModal() {
            document.getElementById('customReportModal').classList.remove('hidden');
        }

        function closeCustomReportModal() {
            document.getElementById('customReportModal').classList.add('hidden');
            // Reset form
            document.getElementById('customReportType').value = 'all';
            document.getElementById('customReportFormat').value = 'pdf';
            document.getElementById('ageRangeFields').classList.add('hidden');
            document.getElementById('minAge').value = '';
            document.getElementById('maxAge').value = '';
        }

        function generateCustomReport() {
            const type = document.getElementById('customReportType').value;
            const format = document.getElementById('customReportFormat').value;
            const minAge = document.getElementById('minAge').value;
            const maxAge = document.getElementById('maxAge').value;

            let params = `type=${type}&format=${format}`;
            if (type === 'age_range' && minAge && maxAge) {
                params += `&min_age=${minAge}&max_age=${maxAge}`;
            }

            // Create download link
            const link = document.createElement('a');
            link.href = `generate_report.php?${params}`;
            link.download = `custom_report_${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            closeCustomReportModal();
            showNotification('Custom report generated successfully!', 'success');
        }

        function showNotification(message, type = 'info') {
            // Simple notification system
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

        // Show/hide age range fields based on report type
        document.getElementById('customReportType').addEventListener('change', function() {
            const ageRangeFields = document.getElementById('ageRangeFields');
            if (this.value === 'age_range') {
                ageRangeFields.classList.remove('hidden');
            } else {
                ageRangeFields.classList.add('hidden');
            }
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
