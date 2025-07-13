<?php
// Navigation component for BIMS system
// This file contains the sidepanel and top navigation bar
// Include this file in any page that needs navigation

// Ensure we have the required variables
if (!isset($conn)) {
    die('Database connection required');
}
if (!isset($user_full_name)) {
    $user_full_name = 'User';
}
if (!isset($system_title)) {
    $system_title = 'BIMS';
}
?>

<!-- Navigation CSS Styles -->
<style>
    /* Custom thin scrollbar for sidepanel */
    .nav-custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: #2563eb #353535;
        padding-right: 6px; /* Always reserve space for scrollbar */
    }
    .nav-custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .nav-custom-scrollbar::-webkit-scrollbar-thumb {
        background: #2563eb;
        border-radius: 6px;
    }
    .nav-custom-scrollbar::-webkit-scrollbar-track {
        background: #353535;
    }
    /* Always show scrollbar track to prevent layout shift */
    .nav-custom-scrollbar {
        overflow-y: scroll;
    }
    .nav-custom-scrollbar::-webkit-scrollbar {
        background: #353535;
    }
    .nav-dropdown-menu { 
        display: none; 
        position: absolute; 
        right: 0; 
        top: 100%; 
        background: white; 
        min-width: 180px; 
        box-shadow: 0 4px 16px rgba(0,0,0,0.1); 
        border-radius: 0.5rem; 
        z-index: 50; 
    }
    .nav-dropdown-menu.show { 
        display: block; 
    }
    .nav-sidebar-border { 
        border-right: 1px solid #e5e7eb; 
    }
    
    /* Dropdown open/close effect styles */
    .nav-dropdown-open {
        max-height: 500px;
        opacity: 1;
        pointer-events: auto;
        overflow: hidden;
        transition: all 0.3s ease-in-out;
    }
    .nav-dropdown-closed {
        max-height: 0;
        opacity: 0;
        pointer-events: none;
        overflow: hidden;
        transition: all 0.3s ease-in-out;
    }
</style>

<!-- Sidepanel -->
<div id="sidepanel" class="fixed top-0 left-0 h-full w-80 shadow-lg z-40 transform -translate-x-full transition-transform duration-300 ease-in-out nav-sidebar-border overflow-y-auto nav-custom-scrollbar" style="background-color: #454545;">
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
    <nav class="flex flex-col p-4 gap-2 text-white">
        <?php
        // --- Sidepanel Navigation Refactored ---
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
            <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $peopleActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleNavDropdown('<?php echo $peopleId; ?>')">
                <i class="fas fa-users"></i> People Management
                <i class="fas fa-chevron-down ml-auto text-xs"></i>
            </button>
            <div id="<?php echo $peopleId; ?>" class="ml-8 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $peopleActive ? 'nav-dropdown-open' : 'nav-dropdown-closed'; ?>">
                <?php echo navLink('individuals.php', 'fas fa-user', 'Residents', navActive('individuals.php'), 'rounded pl-4'); ?>
            </div>
        </div>

        <?php
        // Barangay Documents
        $docsActive = navActive(['certificate.php', 'reports.php', 'issued_documents.php']);
        $docsId = 'docsSubNav';
        ?>
        <div class="mt-2">
            <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $docsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleNavDropdown('<?php echo $docsId; ?>')">
                <i class="fas fa-file-alt"></i> Barangay Documents
                <i class="fas fa-chevron-down ml-auto text-xs"></i>
            </button>
            <div id="<?php echo $docsId; ?>" class="ml-8 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $docsActive ? 'nav-dropdown-open' : 'nav-dropdown-closed'; ?>">
                <?php echo navLink('certificate.php', 'fas fa-stamp', 'Issue Certificate', navActive('certificate.php'), 'rounded pl-4'); ?>
                <?php echo navLink('reports.php', 'fas fa-chart-bar', 'Generate Reports', navActive('reports.php'), 'rounded pl-4'); ?>
                <?php echo navLink('issued_documents.php', 'fas fa-history', 'Issued Documents Log', navActive('issued_documents.php'), 'rounded pl-4'); ?>
            </div>
        </div>

        <?php
        // System Settings
        $settingsActive = navActive(['officials.php', 'settings.php', 'logs.php']);
        $settingsId = 'settingsSubNav';
        ?>
        <div class="mt-2">
            <button type="button" class="w-full py-2 px-3 rounded-lg flex items-center gap-2 text-left group <?php echo $settingsActive ? 'bg-blue-500 text-white font-bold shadow-md' : 'text-white'; ?> hover:bg-blue-500 hover:text-white focus:outline-none" onclick="toggleNavDropdown('<?php echo $settingsId; ?>')">
                <i class="fas fa-cogs"></i> System Settings
                <i class="fas fa-chevron-down ml-auto text-xs"></i>
            </button>
            <div id="<?php echo $settingsId; ?>" class="ml-8 mt-1 flex flex-col gap-1 transition-all duration-300 ease-in-out <?php echo $settingsActive ? 'nav-dropdown-open' : 'nav-dropdown-closed'; ?>">
                <?php echo navLink('officials.php', 'fas fa-user-tie', 'Officials Management', navActive('officials.php'), 'rounded pl-4'); ?>
                <?php echo navLink('settings.php', 'fas fa-cog', 'General Settings', navActive('settings.php'), 'rounded pl-4'); ?>
                <?php echo navLink('logs.php', 'fas fa-clipboard-list', 'Logs', navActive('logs.php'), 'rounded pl-4'); ?>
            </div>
        </div>
        
    </nav>
</div>

<!-- Overlay -->
<div id="sidepanelOverlay" class="fixed inset-0 bg-black bg-opacity-30 z-30 hidden"></div>

<!-- Top Navbar -->
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
        <div id="userDropdownMenu" class="nav-dropdown-menu mt-2">
            <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100 text-gray-700"><i class="fas fa-user mr-2"></i>Manage Profile</a>
            <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 text-red-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
        </div>
    </div>
</nav>

<!-- Navigation JavaScript -->
<script>
    // Sidepanel toggle functionality
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

    if (menuBtn) menuBtn.addEventListener('click', openSidepanel);
    if (closeSidepanel) closeSidepanel.addEventListener('click', closeSidepanelFn);
    if (sidepanelOverlay) sidepanelOverlay.addEventListener('click', closeSidepanelFn);

    // Dropdown logic for sidepanel (only one open at a time)
    function toggleNavDropdown(id) {
        const dropdowns = ['peopleSubNav', 'docsSubNav', 'settingsSubNav'];
        dropdowns.forEach(function(dropId) {
            const el = document.getElementById(dropId);
            if (el) {
                if (dropId === id) {
                    if (el.classList.contains('nav-dropdown-open')) {
                        el.classList.remove('nav-dropdown-open');
                        el.classList.add('nav-dropdown-closed');
                    } else {
                        el.classList.remove('nav-dropdown-closed');
                        el.classList.add('nav-dropdown-open');
                    }
                } else {
                    el.classList.remove('nav-dropdown-open');
                    el.classList.add('nav-dropdown-closed');
                }
            }
        });
    }

    // User dropdown functionality
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener('click', () => {
            userDropdownMenu.classList.toggle('show');
        });

        // Close user dropdown if clicked outside
        document.addEventListener('click', (e) => {
            if (!userDropdownBtn.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
    }

    // Make toggleNavDropdown globally available
    window.toggleNavDropdown = toggleNavDropdown;
</script>
