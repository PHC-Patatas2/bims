<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$user_id = $_SESSION['user_id'];
$user_full_name = '';
$stmt = $conn->prepare('SELECT full_name FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($user_full_name);
$stmt->fetch();
$stmt->close();

// Fetch system title from system_settings table
$system_title = 'Barangay Information Management System'; // default fallback
$title_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='system_title' LIMIT 1");
if ($title_result && $title_row = $title_result->fetch_assoc()) {
    if (!empty($title_row['setting_value'])) {
        $system_title = $title_row['setting_value'];
    }
}

$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all_residents'; // Default to all_residents

// Map filter_type to a more readable title
$page_title = "Residents List"; // Default
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
];
if (array_key_exists($filter_type, $filter_type_map)) {
    $page_title = $filter_type_map[$filter_type];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars($system_title); ?></title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <link rel="stylesheet" href="lib/assets/tabulator.min.css">
    <script src="lib/assets/all.min.js" defer></script>
    <script src="lib/assets/tabulator.min.js"></script>
    <!-- For Export Functionalities: Please download these files and place them in lib/assets/ -->
    <!-- Make sure to download xlsx.full.min.js and place it in lib/assets/ -->
    <script src="lib/assets/xlsx.full.min.js"></script> 
    <!-- Make sure to download jspdf.umd.min.js and place it in lib/assets/ -->
    <script src="lib/assets/jspdf.umd.min.js"></script> 
    <!-- Make sure to download jspdf.plugin.autotable.min.js and place it in lib/assets/ -->
    <script src="lib/assets/jspdf.plugin.autotable.min.js"></script>
    <style>
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }

        /* Tabulator Styling - Inspired by the provided image */
        #data-table .tabulator {
            border: 1px solid #dee2e6; /* Light border around the whole table */
            border-radius: 0.25rem; /* Optional: slightly rounded corners for the table */
            background-color: #fff;
        }

        #data-table .tabulator-header {
            background-color: #f8f9fa; /* Light grey header background */
            color: #212529; /* Dark header text */
            border-bottom: 2px solid #dee2e6; /* Heavier border below header */
            font-weight: 600; /* Slightly bolder header font */
            text-transform: none; /* Keep default text casing */
        }

        #data-table .tabulator-header .tabulator-col {
            border-right: 1px solid #dee2e6; /* Light vertical border for header cells */
        }
        #data-table .tabulator-header .tabulator-col:last-child {
            border-right: none;
        }
        
        #data-table .tabulator-header .tabulator-col .tabulator-col-content {
            padding: 0.75rem 1rem; /* Adjust padding for header cells */
        }

        #data-table .tabulator-row {
            background-color: #fff; /* White row background */
            border-bottom: 1px solid #e9ecef; /* Light horizontal border for rows */
        }

        #data-table .tabulator-row:nth-child(even) {
            background-color: #fff; /* Remove zebra striping, or use a very subtle one like #f9f9f9 if desired */
        }
        
        #data-table .tabulator-row:hover {
            background-color: #f1f3f5; /* Optional: subtle hover effect */
        }

        #data-table .tabulator-row .tabulator-cell {
            border-right: 1px solid #e9ecef; /* Light vertical borders for data cells, can be removed if not desired */
            padding: 0.75rem 1rem; /* Adjust padding for data cells */
            vertical-align: middle;
        }
        #data-table .tabulator-row .tabulator-cell:last-child {
            border-right: none;
        }

        /* Style for header filters to make them less obtrusive if kept */
        #data-table .tabulator-header .tabulator-col .tabulator-header-filter input,
        #data-table .tabulator-header .tabulator-col .tabulator-header-filter select {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
            border: 1px solid #ced4da;
            margin-top: 4px; /* Add some space above the filter */
        }
        
        /* Pagination styling (example, can be further customized) */
        #data-table .tabulator-footer {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-top: 1px solid #dee2e6;
        }
        #data-table .tabulator-paginator button {
            color: #007bff;
            border: 1px solid #007bff;
            background-color: white;
            margin: 0 2px;
            padding: 5px 10px;
            border-radius: 3px;
        }
        #data-table .tabulator-paginator button:hover {
            background-color: #007bff;
            color: white;
        }
        #data-table .tabulator-paginator button:disabled {
            color: #6c757d;
            border-color: #6c757d;
            background-color: #e9ecef;
        }
        #data-table .tabulator-page-size {
            margin-left: 10px;
            padding: 5px;
            border-radius: 3px;
            border: 1px solid #ced4da;
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
                $activeClass = $isActive ? 'bg-blue-600 text-white font-bold shadow-md' : 'text-white';
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
    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out p-4 md:px-8 md:pt-8 mt-16 flex flex-col">
        <div class="flex flex-wrap justify-between items-center mb-4 gap-4">
            <h1 class="text-3xl font-bold text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($page_title); ?></h1>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 ml-auto">
                <button id="addNewResidentBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow hover:shadow-md transition-all duration-150 ease-in-out">
                    <i class="fas fa-plus mr-2"></i> Add Resident
                </button>
                <div class="relative inline-block text-left">
                    <button id="exportDataBtnDropdown" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow hover:shadow-md transition-all duration-150 ease-in-out">
                        <i class="fas fa-file-export mr-2"></i> Export <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div id="exportDropdownMenu" class="dropdown-menu origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none" style="display:none; z-index:60;">
                        <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="exportDataBtnDropdown">
                            <a href="#" id="exportCsvBtn" class="text-gray-700 hover:bg-gray-100 hover:text-gray-900 block px-4 py-2 text-sm" role="menuitem"><i class="fas fa-file-csv mr-2"></i>Export as CSV</a>
                            <a href="#" id="exportXlsxBtn" class="text-gray-700 hover:bg-gray-100 hover:text-gray-900 block px-4 py-2 text-sm" role="menuitem"><i class="fas fa-file-excel mr-2"></i>Export as XLSX</a>
                            <a href="#" id="exportPdfBtn" class="text-gray-700 hover:bg-gray-100 hover:text-gray-900 block px-4 py-2 text-sm" role="menuitem"><i class="fas fa-file-pdf mr-2"></i>Export as PDF</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Bar for Selected Rows -->
        <div id="selectedActionsBar" class="mb-4 p-3 bg-gray-200 shadow rounded-lg flex flex-wrap items-center justify-start gap-3" style="display: none;">
            <span id="selectedRowCount" class="font-semibold text-gray-700"></span>
            <button id="printSelectedBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-3 rounded-lg inline-flex items-center shadow hover:shadow-md transition-colors duration-150 ease-in-out text-sm">
                <i class="fas fa-print mr-2"></i> Print Selected
            </button>
            <button id="deleteSelectedBtn" class="bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-3 rounded-lg inline-flex items-center shadow hover:shadow-md transition-colors duration-150 ease-in-out text-sm">
                <i class="fas fa-trash-alt mr-2"></i> Delete Selected
            </button>
            <!-- Add more batch actions here if needed -->
        </div>
        
        <div class="bg-white shadow-xl rounded-lg overflow-hidden">
            <div id="data-table" class="p-1"></div> <!-- Tabulator will be initialized here -->
        </div>
    </div>

    <script>
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

        // User dropdown toggle
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const exportDataBtnDropdown = document.getElementById('exportDataBtnDropdown');
        const exportDropdownMenu = document.getElementById('exportDropdownMenu');

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', () => {
                userDropdownMenu.classList.toggle('show');
                if (exportDropdownMenu.classList.contains('show')) {
                    exportDropdownMenu.classList.remove('show');
                }
            });
        }
        if (exportDataBtnDropdown && exportDropdownMenu) {
            exportDataBtnDropdown.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent click from bubbling to document
                exportDropdownMenu.classList.toggle('show');
                 if (userDropdownMenu && userDropdownMenu.classList.contains('show')) {
                    userDropdownMenu.classList.remove('show');
                }
            });
        }
        
        // Close dropdowns if clicked outside
        document.addEventListener('click', function(event) {
            if (userDropdownMenu && !userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.remove('show');
            }
            if (exportDropdownMenu && !exportDataBtnDropdown.contains(event.target) && !exportDropdownMenu.contains(event.target)) {
                exportDropdownMenu.classList.remove('show');
            }
        });


        // Tabulator setup
        const filterType = "<?php echo htmlspecialchars($filter_type, ENT_QUOTES, 'UTF-8'); ?>";
        
        const tableColumns = [
            {
                formatter: "rowSelection",
                titleFormatter: "rowSelection",
                hozAlign: "center",
                headerSort: false,
                width: 40,
                cellClick: function(e, cell){
                    cell.getRow().toggleSelect();
                }
            },
            { title: "ID", field: "id", width: 70, hozAlign: "center", headerFilter: "input", headerFilterPlaceholder: "Filter ID", visible: false },
            { title: "Last Name", field: "last_name", headerFilter: "input", minWidth: 100, headerFilterPlaceholder: "Filter Last Name" },
            { title: "First Name", field: "first_name", headerFilter: "input", minWidth: 100, headerFilterPlaceholder: "Filter First Name" },
            { title: "Middle Name", field: "middle_name", headerFilter: "input", minWidth: 100, headerFilterPlaceholder: "Filter Middle Name" },
            { title: "Suffix", field: "suffix", width: 100, headerFilter: "input", headerFilterPlaceholder: "Filter Suffix" },
            { 
                title: "Sex", 
                field: "gender", 
                width: 100, 
                headerFilter: "select", 
                headerFilterParams: { values: {"": "Any", "male": "Male", "female": "Female"} },
                formatter: function(cell, formatterParams, onRendered){
                    const value = cell.getValue();
                    if (value === 'male') return 'Male';
                    if (value === 'female') return 'Female';
                    return '';
                }
            },
            {
                title: "Birthdate", 
                field: "birthdate", 
                width: 120, 
                hozAlign: "center", 
                sorter: "date", 
                headerFilter: "input", 
                headerFilterPlaceholder: "Filter Birthdate",
            },
            { 
                title: "Age", 
                field: "age", 
                width: 120,
                hozAlign: "center", 
                headerFilter: "input", 
                headerFilterPlaceholder: "Filter Age",
                formatter: function(cell, formatterParams, onRendered){
                    const value = cell.getValue();
                    if (value !== null && value !== undefined && value !== '') {
                        return value + " years old";
                    }
                    return ""; 
                }
            },
            {
                title: "Actions",
                field: "actions",
                hozAlign: "center",
                headerSort: false,
                width: 100,
                formatter: function(cell, formatterParams, onRendered) {
                    const id = cell.getRow().getData().id;
                    return `
                        <button onclick="viewResident(${id})" class="text-blue-600 hover:text-blue-800 p-1" title="View Details"><i class="fas fa-eye"></i></button>
                        <button onclick="editResident(${id})" class="text-green-600 hover:text-green-800 p-1" title="Edit Resident"><i class="fas fa-edit"></i></button>
                    `;
                }
            }
        ];

        const table = new Tabulator("#data-table", {
            height: "calc(100vh - 350px)", // Adjusted height to accommodate new bars
            layout: "fitColumns", 
            selectable: "highlight", // Enable row selection with highlight
            resizableColumns: false, // Global setting for column resizing
            columnDefaults:{
                resizable: false // Default for all columns, to prevent resizing
            },
            placeholder: "No Data Available",
            ajaxURL: "dashboard_data.php",
            ajaxParams: { type: filterType }, 
            ajaxConfig: { 
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest" 
                }
            }, 
            ajaxResponse: function(url, params, response){
                if(response && Array.isArray(response.data) && typeof response.last_page === 'number'){
                    return response; 
                } else {
                    console.error("Data Loading Error: Invalid data structure from server. Expected {last_page: number, data: array[]}, Received:", response);
                    if(Array.isArray(response)) { // Fallback for non-paginated array
                        console.warn("Received an array directly, but expected paginated structure. Wrapping it for display.");
                        return { last_page: 1, data: response }; 
                    }
                    return { last_page: 0, data: [] }; // Default empty structure on error
                }
            },
            pagination: true,
            paginationMode: "remote", 
            paginationSize: 20,
            paginationSizeSelector: [10, 20, 50, 100, 500, 1000],
            filterMode: "remote", 
            sortMode: "remote", 
            columns: tableColumns,
            initialSort: [
                { column: "id", dir: "asc" } 
            ]
        });

        // Action Button Handlers & UI updates
        const addNewResidentBtn = document.getElementById('addNewResidentBtn');
        const exportCsvBtn = document.getElementById('exportCsvBtn');
        const exportXlsxBtn = document.getElementById('exportXlsxBtn');
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        
        const selectedActionsBar = document.getElementById('selectedActionsBar');
        const selectedRowCount = document.getElementById('selectedRowCount');
        const printSelectedBtn = document.getElementById('printSelectedBtn');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

        if(addNewResidentBtn) {
            addNewResidentBtn.addEventListener('click', () => {
                // Replace with your actual navigation or modal logic
                // alert('Navigating to Add New Resident page/modal...');
                window.location.href = 'add_individual.php'; 
            });
        }

        // Export Handlers
        if(exportCsvBtn) {
            exportCsvBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (table) {
                    table.download("csv", "<?php echo str_replace(' ', '_', $page_title); ?>_export.csv");
                }
                exportDropdownMenu.classList.remove('show');
            });
        }
        if(exportXlsxBtn) {
            exportXlsxBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (table) {
                    table.download("xlsx", "<?php echo str_replace(' ', '_', $page_title); ?>_export.xlsx", {sheetName:"Residents"});
                }
                exportDropdownMenu.classList.remove('show');
            });
        }
        if(exportPdfBtn) {
            exportPdfBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (table) {
                    const allData = table.getData("active"); // Get filtered/sorted data
                    if (allData.length === 0) {
                        alert("No data to export.");
                        exportDropdownMenu.classList.remove('show');
                        return;
                    }
                    // Define columns for PDF (ensure field names match your table data)
                    const head = [tableColumns.filter(col => col.visible !== false && col.field && col.field !== 'actions' && col.formatter !== 'rowSelection').map(col => col.title)];
                    const body = allData.map(row => {
                        return tableColumns.filter(col => col.field && col.visible !== false && col.field !== 'actions' && col.formatter !== 'rowSelection').map(col => {
                            let val = row[col.field];
                            if (col.field === 'age') return val + " years old";
                            if (col.field === 'gender') return (val === 'male' ? 'Male' : (val === 'female' ? 'Female' : ''));
                            return val;
                        });
                    });
                    
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF({ orientation: 'landscape' });
                    doc.autoTable({
                        head: head,
                        body: body,
                        startY: 15,
                        headStyles: { fillColor: [22, 160, 133] }, // Example header style
                        didDrawPage: function (data) {
                            // Header
                            doc.setFontSize(20);
                            doc.setTextColor(40);
                            doc.text("<?php echo htmlspecialchars($page_title); ?>", data.settings.margin.left, 10);
                        }
                    });
                    doc.save("<?php echo str_replace(' ', '_', $page_title); ?>_export.pdf");
                }
                exportDropdownMenu.classList.remove('show');
            });
        }


        // Update selected actions bar based on row selection
        if (table) {
            table.on("rowSelectionChanged", function(data, rows){
                const numSelected = rows.length;
                if (numSelected > 0) {
                    selectedRowCount.textContent = `${numSelected} row(s) selected`;
                    selectedActionsBar.style.display = 'flex';
                    if(printSelectedBtn) printSelectedBtn.disabled = false;
                    if(deleteSelectedBtn) deleteSelectedBtn.disabled = false;
                } else {
                    selectedActionsBar.style.display = 'none';
                    if(printSelectedBtn) printSelectedBtn.disabled = true;
                    if(deleteSelectedBtn) deleteSelectedBtn.disabled = true;
                }
            });
        }

        if(printSelectedBtn){
            printSelectedBtn.addEventListener('click', () => {
                const selectedData = table.getSelectedData();
                if(selectedData.length > 0){
                    const idsToPrint = selectedData.map(item => item.id);
                    if (idsToPrint.length > 0) {
                        const url = `print_individuals.php?ids=${idsToPrint.join(',')}`;
                        window.open(url, '_blank'); // Open in a new tab
                    } else {
                        alert("No valid IDs found for selected residents.");
                    }
                } else {
                    alert("No rows selected to print.");
                }
            });
        }

        if(deleteSelectedBtn){
            deleteSelectedBtn.addEventListener('click', () => {
                const selectedRows = table.getSelectedRows();
                const selectedData = table.getSelectedData(); 

                if(selectedRows.length > 0){
                    if(confirm(`Are you sure you want to delete ${selectedRows.length} selected resident(s)? This action cannot be undone.`)){
                        const idsToDelete = selectedData.map(item => item.id);
                        
                        fetch('delete_individuals.php', { 
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ ids: idsToDelete }) 
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success){
                                // selectedRows.forEach(row => row.delete()); // This can cause issues if table is reloaded
                                table.setData(); // Refresh table data from server to reflect deletions
                                table.clearSelection(); // Clear selection after deletion
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

        // Functions for row actions (View, Edit)
        function viewResident(id) {
            window.location.href = `view_individual.php?id=${id}`;
        }

        function editResident(id) {
            window.location.href = `edit_individual.php?id=${id}`;
        }

    </script>
    <?php $conn->close(); ?>
</body>
</html>
