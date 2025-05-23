<?php
// navbar.php
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF']);
}
?>
<nav class="fixed top-0 left-0 right-0 bg-blue-700 text-white flex items-center justify-between px-6 py-3 shadow z-20">
    <div class="flex items-center gap-4">
        <span class="font-bold text-lg">Barangay Information Management System</span>
    </div>
    <div class="flex items-center gap-4">
        <a href="dashboard.php" class="hover:underline <?= $currentPage === 'dashboard.php' ? 'font-bold underline' : '' ?>">Dashboard</a>
        <a href="individuals.php" class="hover:underline <?= $currentPage === 'individuals.php' ? 'font-bold underline' : '' ?>">Individuals</a>
        <a href="families.php" class="hover:underline <?= $currentPage === 'families.php' ? 'font-bold underline' : '' ?>">Families</a>
        <a href="reports.php" class="hover:underline <?= $currentPage === 'reports.php' ? 'font-bold underline' : '' ?>">Reports</a>
        <a href="profile.php" class="hover:underline <?= $currentPage === 'profile.php' ? 'font-bold underline' : '' ?>">Manage Profile</a>
        <a href="logout.php" class="bg-red-500 px-3 py-1 rounded hover:bg-red-600 transition">Logout</a>
    </div>
</nav>
