<?php
// navbar.php
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF']);
}
// Simulate username for demo; replace with session username in production
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Secretary';
?>
<nav class="fixed top-0 left-0 right-0 bg-blue-700 text-white flex items-center justify-between px-6 py-3 shadow z-20">
    <div class="flex items-center gap-4">
        <!-- Hamburger button for sidepanel -->
        <button id="hamburgerBtn" class="mr-4 focus:outline-none">
            <i class="fa-solid fa-bars text-2xl"></i>
        </button>
        <span class="font-bold text-lg">Barangay Information Management System</span>
    </div>
    <div class="relative flex items-center gap-2">
        <span class="font-semibold mr-2"><?php echo htmlspecialchars($username); ?></span>
        <button id="userMenuBtn" class="focus:outline-none">
            <i class="fa-solid fa-chevron-down"></i>
        </button>
        <!-- Dropdown menu -->
        <div id="userDropdown" class="hidden absolute right-0 mt-12 w-48 bg-white text-gray-800 rounded shadow-lg z-50">
            <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100">Manage Profile</a>
            <a href="mailto:support@barangay.com" class="block px-4 py-2 hover:bg-gray-100">Contact Tech Support</a>
            <a href="logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100">Logout</a>
        </div>
    </div>
</nav>
<!-- Sidepanel (hidden by default) -->
<?php include 'sidepanel.php'; ?>
<script>
// Hamburger menu open/close
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidepanel = document.getElementById('sidepanel');
const closeSidepanel = document.getElementById('closeSidepanel');
hamburgerBtn.addEventListener('click', () => {
    sidepanel.classList.remove('-translate-x-full');
});
closeSidepanel.addEventListener('click', () => {
    sidepanel.classList.add('-translate-x-full');
});
// User dropdown
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');
userMenuBtn.addEventListener('click', () => {
    userDropdown.classList.toggle('hidden');
});
document.addEventListener('click', (e) => {
    if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
        userDropdown.classList.add('hidden');
    }
});
</script>
