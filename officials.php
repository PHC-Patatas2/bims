<?php
// officials.php
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

// Fetch officials from barangay_officials table early to use in header
$officials = [];
$officials_result = $conn->query("SELECT id, first_name, middle_initial, last_name, suffix, position FROM barangay_officials ORDER BY FIELD(position, 'Punong Barangay', 'Barangay Secretary', 'Barangay Treasurer', 'Sangguniang Barangay Member'), id ASC");
if ($officials_result) {
    while ($row = $officials_result->fetch_assoc()) {
        $officials[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officials Management - <?php echo htmlspecialchars($system_title); ?></title>
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
        
        /* Enhanced Official Card Styles */
        .official-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .official-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .official-card:hover::before {
            transform: scaleX(1);
        }
        
        .official-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px -12px rgba(59, 130, 246, 0.25), 0 8px 16px -4px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }
        
        .official-avatar {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .official-avatar::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.2), transparent);
            transform: rotate(45deg) translateX(-100%);
            transition: transform 0.6s ease;
        }
        
        .official-card:hover .official-avatar::after {
            transform: rotate(45deg) translateX(100%);
        }
        
        .position-badge {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            border: 1px solid #cbd5e1;
            color: #475569;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
            margin-top: 0.25rem;
        }
        
        .action-btn {
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            padding: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .btn-edit:hover {
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-delete:hover {
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }
        
        /* Enhanced Add Button */
        .btn-add-official {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-add-official::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-add-official:hover::before {
            left: 100%;
        }
        
        .btn-add-official:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        /* Page Header Enhancement */
        .page-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8, #3b82f6);
        }
        
        /* Modal Enhancements */
        .modal-enhanced {
            backdrop-filter: blur(8px);
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 1.5rem;
            border-bottom: 1px solid #1d4ed8;
        }
        
        .form-input {
            transition: all 0.2s ease;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
        }
        
        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Empty State Enhancement */
        .empty-state {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 3rem;
            text-align: center;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #94a3b8;
            margin-bottom: 1rem;
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
            <!-- Header with Add Button -->
            <div class="page-header fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Officials Management</h1>
                        <p class="text-gray-600 text-lg">Manage barangay officials and their positions</p>
                        <div class="mt-4 flex items-center gap-4">
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <i class="fas fa-users"></i>
                                <span><?php echo count($officials); ?> Officials</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-500">
                                <i class="fas fa-shield-alt"></i>
                                <span>Active Administration</span>
                            </div>
                        </div>
                    </div>
                    <button onclick="openOfficialModal()" class="btn-add-official text-white px-6 py-3 rounded-lg font-medium shadow-lg relative overflow-hidden">
                        <i class="fas fa-plus mr-2"></i>Add Official
                    </button>
                </div>
            </div>

            <!-- Officials Grid (Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8 mb-6" id="officialsGrid">
                <?php
                // Officials are already fetched above, just loop through them
                foreach ($officials as $official) {
                    // Build full name
                    $full_name = htmlspecialchars($official['first_name']);
                    if (!empty($official['middle_initial'])) $full_name .= ' ' . htmlspecialchars($official['middle_initial']) . '.';
                    $full_name .= ' ' . htmlspecialchars($official['last_name']);
                    if (!empty($official['suffix'])) $full_name .= ', ' . htmlspecialchars($official['suffix']);
                    // Icon by position
                    $icon = 'fa-user';
                    if (stripos($official['position'], 'Punong Barangay') !== false) $icon = 'fa-user-tie';
                    elseif (stripos($official['position'], 'Secretary') !== false) $icon = 'fa-user-pen';
                    elseif (stripos($official['position'], 'Treasurer') !== false) $icon = 'fa-coins';
                    elseif (stripos($official['position'], 'Kagawad') !== false || stripos($official['position'], 'Sangguniang Barangay Member') !== false) $icon = 'fa-user-friends';
                    // Card
                ?>
                <div class="official-card rounded-xl shadow-lg p-6 flex flex-col justify-between slide-up">
                    <div class="flex items-start mb-6">
                        <div class="official-avatar w-16 h-16 rounded-full flex items-center justify-center mr-4 shadow-lg">
                            <i class="fas <?php echo $icon; ?> text-white text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo $full_name; ?></h3>
                            <span class="position-badge"><?php echo htmlspecialchars($official['position']); ?></span>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
                        <button onclick="editOfficial(<?php echo (int)$official['id']; ?>)" class="action-btn btn-edit" title="Edit Official">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteOfficial(<?php echo (int)$official['id']; ?>)" class="action-btn btn-delete" title="Delete Official">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php } ?>
                <?php if (empty($officials)) { ?>
                    <div class="col-span-full empty-state">
                        <i class="fas fa-user-friends"></i>
                        <h3 class="text-xl font-semibold mb-2">No Officials Found</h3>
                        <p class="text-gray-500 mb-4">Start by adding your first barangay official</p>
                        <button onclick="openOfficialModal()" class="btn-add-official text-white px-6 py-2 rounded-lg font-medium relative overflow-hidden">
                            <i class="fas fa-plus mr-2"></i>Add First Official
                        </button>
                    </div>
                <?php } ?>
            </div>

            <!-- Officials Table removed as requested -->

            <!-- Add/Edit Official Modal -->
            <div id="officialModal" class="modal-enhanced fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="modal-content max-w-md w-full">
                        <div class="modal-header">
                            <h3 class="text-xl font-bold" id="modalTitle">Add Official</h3>
                            <button onclick="closeOfficialModal()" class="text-white hover:text-gray-200 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <form id="officialForm" class="p-6 space-y-6">
                            <input type="hidden" id="officialId">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">First Name *</label>
                                    <input type="text" id="firstName" required 
                                           class="form-input w-full" placeholder="Enter first name">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Middle Initial</label>
                                    <input type="text" id="middleInitial" maxlength="5"
                                           class="form-input w-full" placeholder="M.I.">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Last Name *</label>
                                    <input type="text" id="lastName" required 
                                           class="form-input w-full" placeholder="Enter last name">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Suffix</label>
                                    <input type="text" id="suffix" maxlength="20"
                                           class="form-input w-full" placeholder="Jr., Sr., III">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Position *</label>
                                <select id="officialPosition" required 
                                        class="form-input w-full">
                                    <option value="">Select Position</option>
                                    <option value="Punong Barangay">Punong Barangay</option>
                                    <option value="Barangay Secretary">Barangay Secretary</option>
                                    <option value="Barangay Treasurer">Barangay Treasurer</option>
                                    <option value="Sangguniang Barangay Member">Sangguniang Barangay Member</option>
                                    <option value="SK Chairman">SK Chairman</option>
                                    <option value="SK Kagawad">SK Kagawad</option>
                                </select>
                            </div>
                        </form>
                        <div class="flex justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50">
                            <button onclick="closeOfficialModal()" type="button" class="px-6 py-2 text-gray-600 border-2 border-gray-300 rounded-lg hover:bg-gray-100 transition-colors font-medium">
                                Cancel
                            </button>
                            <button onclick="saveOfficial()" type="button" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg font-medium">
                                Save Official
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="modal-enhanced fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="modal-content max-w-md w-full">
                        <div class="p-6">
                            <div class="flex items-center mb-6">
                                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900">Confirm Delete</h3>
                                    <p class="text-gray-600 mt-1">This action cannot be undone.</p>
                                </div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                                <p class="text-gray-700 font-medium" id="deleteOfficialName"></p>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 p-6 border-t border-gray-100 bg-gray-50">
                            <button onclick="closeDeleteModal()" class="px-6 py-2 text-gray-600 border-2 border-gray-300 rounded-lg hover:bg-gray-100 transition-colors font-medium">
                                Cancel
                            </button>
                            <button onclick="confirmDelete()" class="px-6 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all shadow-lg font-medium">
                                Delete Official
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error/Success Modal -->
            <div id="messageModal" class="modal-enhanced fixed inset-0 bg-black bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="modal-content max-w-md w-full">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div id="messageIcon" class="w-16 h-16 rounded-full flex items-center justify-center mr-4">
                                    <i id="messageIconClass" class="text-2xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 id="messageTitle" class="text-xl font-bold text-gray-900"></h3>
                                    <p id="messageText" class="text-gray-600 mt-1"></p>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end p-6 border-t border-gray-100 bg-gray-50">
                            <button onclick="closeMessageModal()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                OK
                            </button>
                        </div>
                    </div>
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

        let officials = [
            <?php
            $officials_result = $conn->query("SELECT id, first_name, middle_initial, last_name, suffix, position FROM barangay_officials ORDER BY FIELD(position, 'Punong Barangay', 'Barangay Secretary', 'Barangay Treasurer', 'Sangguniang Barangay Member'), id ASC");
            if ($officials_result) {
                $first = true;
                while ($row = $officials_result->fetch_assoc()) {
                    if (!$first) echo ",\n"; else $first = false;
                    $full_name = addslashes($row['first_name']);
                    if (!empty($row['middle_initial'])) $full_name .= ' ' . addslashes($row['middle_initial']) . '.';
                    $full_name .= ' ' . addslashes($row['last_name']);
                    if (!empty($row['suffix'])) $full_name .= ', ' . addslashes($row['suffix']);
                    echo json_encode([
                        'id' => (int)$row['id'],
                        'name' => $full_name,
                        'position' => $row['position'],
                        // No term_start, term_end, contact_number, status in schema
                    ]);
                }
            }
            ?>
        ];

        let editingOfficialId = null;
        let deletingOfficialId = null;

        // Remove the Tabulator table initialization since we're only using the grid view

        // Render officials grid
        function renderOfficialsGrid() {
            const grid = document.getElementById('officialsGrid');
            grid.innerHTML = '';

            officials.forEach((official, index) => {
                const card = document.createElement('div');
                card.className = 'official-card rounded-xl shadow-lg p-6 slide-up';
                card.style.animationDelay = `${index * 0.1}s`;
                
                // Determine icon based on position
                let icon = 'fa-user';
                if (official.position.toLowerCase().includes('captain') || official.position.toLowerCase().includes('punong')) {
                    icon = 'fa-user-tie';
                } else if (official.position.toLowerCase().includes('secretary')) {
                    icon = 'fa-user-pen';
                } else if (official.position.toLowerCase().includes('treasurer')) {
                    icon = 'fa-coins';
                } else if (official.position.toLowerCase().includes('kagawad')) {
                    icon = 'fa-user-friends';
                }
                
                card.innerHTML = `
                    <div class="flex items-start mb-6">
                        <div class="official-avatar w-16 h-16 rounded-full flex items-center justify-center mr-4 shadow-lg">
                            <i class="fas ${icon} text-white text-2xl"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">${official.name}</h3>
                            <span class="position-badge">${official.position}</span>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-100">
                        <button onclick="editOfficial(${official.id})" class="action-btn btn-edit" title="Edit Official">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteOfficial(${official.id})" class="action-btn btn-delete" title="Delete Official">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });

            // Add empty state if no officials
            if (officials.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'col-span-full empty-state';
                emptyState.innerHTML = `
                    <i class="fas fa-user-friends"></i>
                    <h3 class="text-xl font-semibold mb-2">No Officials Found</h3>
                    <p class="text-gray-500 mb-4">Start by adding your first barangay official</p>
                    <button onclick="openOfficialModal()" class="btn-add-official text-white px-6 py-2 rounded-lg font-medium relative overflow-hidden">
                        <i class="fas fa-plus mr-2"></i>Add First Official
                    </button>
                `;
                grid.appendChild(emptyState);
            }
        }

        // Modal functions
        function openOfficialModal(id = null) {
            editingOfficialId = id;
            const modal = document.getElementById('officialModal');
            const title = document.getElementById('modalTitle');
            const form = document.getElementById('officialForm');

            if (id) {
                // Edit mode
                title.textContent = 'Edit Official';
                const official = officials.find(o => o.id === id);
                if (official) {
                    document.getElementById('officialId').value = official.id;
                    // Parse the full name back to separate fields
                    const nameParts = official.name.split(' ');
                    let firstName = '';
                    let middleInitial = '';
                    let lastName = '';
                    let suffix = '';
                    
                    if (nameParts.length >= 2) {
                        firstName = nameParts[0];
                        // Check if last part contains comma (suffix)
                        const lastPart = nameParts[nameParts.length - 1];
                        if (lastPart.includes(',')) {
                            const parts = lastPart.split(',');
                            lastName = nameParts.slice(1, -1).join(' ') + ' ' + parts[0];
                            suffix = parts[1].trim();
                        } else {
                            // Check for middle initial (ends with .)
                            if (nameParts.length === 3 && nameParts[1].endsWith('.')) {
                                middleInitial = nameParts[1];
                                lastName = nameParts[2];
                            } else {
                                lastName = nameParts.slice(1).join(' ');
                            }
                        }
                    }
                    
                    document.getElementById('firstName').value = firstName;
                    document.getElementById('middleInitial').value = middleInitial;
                    document.getElementById('lastName').value = lastName;
                    document.getElementById('suffix').value = suffix;
                    document.getElementById('officialPosition').value = official.position;
                }
            } else {
                // Add mode
                title.textContent = 'Add Official';
                form.reset();
            }

            modal.classList.remove('hidden');
        }

        function closeOfficialModal() {
            document.getElementById('officialModal').classList.add('hidden');
            document.getElementById('officialForm').reset();
            editingOfficialId = null;
        }

        function saveOfficial() {
            const firstName = document.getElementById('firstName').value.trim();
            const middleInitial = document.getElementById('middleInitial').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const suffix = document.getElementById('suffix').value.trim();
            const position = document.getElementById('officialPosition').value;

            if (!firstName || !lastName || !position) {
                showNotification('Please fill in all required fields (First Name, Last Name, and Position)', 'error');
                return;
            }

            // Build full name for display
            let fullName = firstName;
            if (middleInitial) {
                fullName += ' ' + middleInitial;
                if (!middleInitial.endsWith('.')) {
                    fullName += '.';
                }
            }
            fullName += ' ' + lastName;
            if (suffix) {
                fullName += ', ' + suffix;
            }

            // Prepare data for server
            const officialData = {
                first_name: firstName,
                middle_initial: middleInitial || null,
                last_name: lastName,
                suffix: suffix || null,
                position: position
            };

            if (editingOfficialId) {
                // Update existing official
                officialData.id = editingOfficialId;
                
                fetch('update_official.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(officialData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update local data
                        const index = officials.findIndex(o => o.id === editingOfficialId);
                        if (index !== -1) {
                            officials[index] = {
                                ...officials[index],
                                name: data.official.name,
                                position: data.official.position
                            };
                        }
                        
                        renderOfficialsGrid();
                        closeOfficialModal();
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message || 'Failed to update official', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
                
            } else {
                // Add new official
                fetch('add_official.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(officialData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Add to local data
                        officials.push({
                            id: data.official.id,
                            name: data.official.name,
                            position: data.official.position
                        });
                        
                        renderOfficialsGrid();
                        closeOfficialModal();
                        showNotification(data.message, 'success');
                        
                        // Update header count
                        const countElement = document.querySelector('.page-header .text-sm span');
                        if (countElement) {
                            countElement.textContent = `${officials.length} Officials`;
                        }
                    } else {
                        showNotification(data.message || 'Failed to add official', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
            }
        }

        function editOfficial(id) {
            openOfficialModal(id);
        }

        function deleteOfficial(id) {
            deletingOfficialId = id;
            const official = officials.find(o => o.id === id);
            if (official) {
                document.getElementById('deleteOfficialName').textContent = `${official.name} (${official.position})`;
                document.getElementById('deleteModal').classList.remove('hidden');
            }
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            deletingOfficialId = null;
        }

        function confirmDelete() {
            if (deletingOfficialId) {
                fetch('delete_official.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: deletingOfficialId })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Remove from local data
                        officials = officials.filter(o => o.id !== deletingOfficialId);
                        
                        renderOfficialsGrid();
                        closeDeleteModal();
                        showNotification(data.message, 'success');
                        
                        // Update header count
                        const countElement = document.querySelector('.page-header .text-sm span');
                        if (countElement) {
                            countElement.textContent = `${officials.length} Officials`;
                        }
                    } else {
                        showNotification(data.message || 'Failed to delete official', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Network error occurred', 'error');
                });
            }
        }

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
                    <span class="font-medium">${message}</span>
                </div>
            `;
            document.body.appendChild(notification);

            // Slide in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Slide out and remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Initialize everything when page loads
        document.addEventListener('DOMContentLoaded', function() {
            renderOfficialsGrid();
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
