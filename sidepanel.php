<div id="sidepanel" class="fixed top-0 left-0 h-screen w-64 bg-gray-100 text-gray-900 shadow-lg z-40 transform -translate-x-full transition-transform duration-200 flex flex-col">
    <button id="closeSidepanel" class="self-end m-4 text-gray-600 hover:text-gray-900 focus:outline-none">
        <i class="fa-solid fa-times text-2xl"></i>
    </button>
    <nav class="flex-1 flex flex-col gap-1 px-4">
        <a href="dashboard.php" class="flex items-center gap-2 py-2 px-3 rounded hover:bg-gray-200 transition text-blue-700 font-semibold">
            <i class="fa-solid fa-gauge"></i> Dashboard
        </a>
        <a href="individuals.php" class="flex items-center gap-2 py-2 px-3 rounded hover:bg-gray-200 transition">
            <i class="fa-solid fa-users"></i> Resident Information
        </a>
        <a href="certificate.php" class="flex items-center gap-2 py-2 px-3 rounded hover:bg-gray-200 transition">
            <i class="fa-solid fa-certificate"></i> Print Certificates
        </a>
        <a href="business_permit.php" class="flex items-center gap-2 py-2 px-3 rounded hover:bg-gray-200 transition">
            <i class="fa-solid fa-briefcase"></i> Business Permit
        </a>
        <a href="blotter_records.php" class="flex items-center gap-2 py-2 px-3 rounded hover:bg-gray-200 transition">
            <i class="fa-solid fa-book"></i> Blotter Records
        </a>
        <a href="system_settings.php" class="flex items-center gap-2 py-2 px-3 rounded hover:bg-gray-200 transition">
            <i class="fa-solid fa-gear"></i> System Settings
        </a>
        <a href="frontend.php" class="flex items-center gap-2 py-2 px-3 rounded hover:bg-gray-200 transition">
            <i class="fa-solid fa-desktop"></i> Frontend
        </a>
    </nav>
</div>
<script>
if (typeof sidepanel === 'undefined') {
    const sidepanel = document.getElementById('sidepanel');
}
if (typeof closeSidepanel === 'undefined') {
    const closeSidepanel = document.getElementById('closeSidepanel');
}
if (closeSidepanel) {
    closeSidepanel.addEventListener('click', () => {
        sidepanel.classList.add('-translate-x-full');
        document.getElementById('mainContent')?.classList.remove('ml-64');
    });
}
</script>
