<?php
include 'config.php';
if (!isset($conn) || !$conn) {
    // Fallback: create a new mysqli connection if $conn is not set
    $conn = new mysqli('localhost', 'root', '', 'bims_db');
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blotter Records | Barangay Information Management System</title>
    <link rel="stylesheet" href="lib/assets/tailwind.min.css">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Sidepanel -->
    <div id="sidepanel" class="fixed top-0 left-0 h-full w-64 shadow-lg z-40 transform -translate-x-full transition-transform duration-300 ease-in-out sidebar-border" style="background-color: #454545;">
        <div class="flex flex-col items-center justify-center min-h-[90px] px-4 pt-3 pb-3 relative" style="border-bottom: 4px solid #FFD700;">
            <button id="closeSidepanel" class="absolute right-2 top-2 text-white hover:text-blue-400 focus:outline-none text-2xl md:hidden" aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
            <?php
            $barangay_logo = 'img/logo.png';
            if (isset($conn)) {
                $logo_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='barangay_logo_path' LIMIT 1");
                if ($logo_result && $logo_row = $logo_result->fetch_assoc()) {
                    if (!empty($logo_row['setting_value'])) {
                        $barangay_logo = $logo_row['setting_value'];
                    }
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
        <div class="flex items-center gap-2">
            <button id="menuBtn" class="h-8 w-8 mr-2 flex items-center justify-center text-blue-700 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <span class="font-bold text-lg text-blue-700">Barangay Information Management System</span>
        </div>
        <div class="relative flex items-center gap-2">
            <span class="hidden sm:inline text-gray-700 font-medium">Welcome, <?php echo htmlspecialchars($user_full_name ?? ''); ?></span>
            <button id="userDropdownBtn" class="focus:outline-none flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-100">
                <i class="fas fa-chevron-down"></i>
            </button>
            <div id="userDropdownMenu" class="dropdown-menu mt-2 hidden">
                <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100 text-gray-700"><i class="fas fa-user mr-2"></i>Manage Profile</a>
                <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 text-red-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>
    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out p-4 md:px-8 md:pt-8 mt-16 flex flex-col">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Blotter Records</h1>
            <button id="addBlotterBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow hover:shadow-md transition-colors duration-150 ease-in-out">
                <i class="fas fa-plus mr-2"></i> Add Blotter Record
            </button>
        </div>
        <div class="bg-white shadow-xl rounded-lg overflow-hidden w-full table-container mb-6">
            <div class="p-4 text-gray-600 text-center">
                <!-- Placeholder for blotter records table -->
                <span class="text-lg">No blotter records to display yet.</span>
            </div>
        </div>
        <!-- Modal for Add Blotter Record (future implementation) -->
        <div id="addBlotterModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
            <div class="bg-white shadow-2xl border border-gray-200 w-full max-w-lg p-4 relative mx-2 my-8" style="max-height:90vh; overflow-y:auto; font-size:0.95rem;">
                <button id="closeAddBlotterModal" class="absolute top-2 right-2 text-gray-500 hover:text-red-600 text-xl focus:outline-none" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                <h2 class="text-lg font-bold mb-2 text-gray-800 text-center">Add New Blotter Record</h2>
                <form id="addBlotterForm" method="POST" class="space-y-4">
                    <div>
                        <label for="complainant" class="block text-xs font-medium text-gray-700 required-label">Complainant</label>
                        <input type="text" name="complainant" id="complainant" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                    </div>
                    <div>
                        <label for="respondent" class="block text-xs font-medium text-gray-700 required-label">Respondent</label>
                        <input type="text" name="respondent" id="respondent" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                    </div>
                    <div>
                        <label for="incident_date" class="block text-xs font-medium text-gray-700 required-label">Date of Incident</label>
                        <input type="date" name="incident_date" id="incident_date" class="form-input mt-0.5 py-1 px-2 text-xs" required>
                    </div>
                    <div>
                        <label for="incident_details" class="block text-xs font-medium text-gray-700 required-label">Incident Details</label>
                        <textarea name="incident_details" id="incident_details" class="form-input mt-0.5 py-1 px-2 text-xs" rows="3" required></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-1 gap-2">
                        <button type="button" id="cancelAddBlotterBtn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-1 px-3 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out text-xs">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out text-xs">
                            <i class="fas fa-save mr-1"></i> Save Record
                        </button>
                    </div>
                    <div id="addBlotterMessage" class="mt-2 text-xs"></div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Sidepanel and Navbar toggle logic
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const menuBtn = document.getElementById('menuBtn');
        const sidepanel = document.getElementById('sidepanel');
        const sidepanelOverlay = document.getElementById('sidepanelOverlay');
        const closeSidepanel = document.getElementById('closeSidepanel');
        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                userDropdownMenu.classList.toggle('show');
            });
        }
        document.addEventListener('click', function(event) {
            if (userDropdownMenu && userDropdownBtn && !userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
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
        // Modal logic for Add Blotter Record
        const addBlotterModal = document.getElementById('addBlotterModal');
        const addBlotterBtn = document.getElementById('addBlotterBtn');
        const closeAddBlotterModal = document.getElementById('closeAddBlotterModal');
        const cancelAddBlotterBtn = document.getElementById('cancelAddBlotterBtn');
        function openAddBlotterModal() {
            addBlotterModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
        function closeAddBlotter() {
            addBlotterModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            document.getElementById('addBlotterForm').reset();
            document.getElementById('addBlotterMessage').innerHTML = '';
        }
        if (addBlotterBtn) addBlotterBtn.addEventListener('click', openAddBlotterModal);
        if (closeAddBlotterModal) closeAddBlotterModal.addEventListener('click', closeAddBlotter);
        if (cancelAddBlotterBtn) cancelAddBlotterBtn.addEventListener('click', closeAddBlotter);
        // AJAX form submission (future implementation)
        // const addBlotterForm = document.getElementById('addBlotterForm');
        // addBlotterForm.addEventListener('submit', function(e) {
        //     e.preventDefault();
        //     // AJAX logic here
        // });
    </script>
    <?php if(isset($conn)) $conn->close(); ?>
</body>
</html>
