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
$stmt_user = $conn->prepare('SELECT full_name FROM users WHERE id = ?');
$stmt_user->bind_param('i', $user_id);
$stmt_user->execute();
$stmt_user->bind_result($user_full_name);
$stmt_user->fetch();
$stmt_user->close();

$system_title = 'Barangay Information Management System';
$title_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='system_title' LIMIT 1");
if ($title_result && $title_row = $title_result->fetch_assoc()) {
    if (!empty($title_row['setting_value'])) {
        $system_title = $title_row['setting_value'];
    }
}

$page_title = "View Resident Details";
$resident_data = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header('Location: individuals.php');
    exit();
}

$resident_id = $_GET['id'];

$stmt_resident = $conn->prepare("SELECT *, DATE_FORMAT(birthdate, '%m/%d/%Y') AS formatted_birthdate, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age FROM individuals WHERE id = ?");
if ($stmt_resident) {
    $stmt_resident->bind_param('i', $resident_id);
    $stmt_resident->execute();
    $result_resident = $stmt_resident->get_result();
    if ($result_resident->num_rows === 1) {
        $resident_data = $result_resident->fetch_assoc();
    } else {
        // No resident found, redirect or show error
        header('Location: individuals.php?error=notfound');
        exit();
    }
    $stmt_resident->close();
} else {
    // SQL error
    die('Error preparing statement to fetch resident data.');
}

$conn->close();

if (!$resident_data) {
    // Fallback if data couldn't be fetched for some reason after checks
    header('Location: individuals.php?error=fetchfailed');
    exit();
}

// Helper function to display data or a placeholder
function display_data($data, $default = 'N/A') {
    return htmlspecialchars(!empty($data) ? $data : $default);
}

function display_boolean($value, $yes = 'Yes', $no = 'No') {
    return $value ? $yes : $no;
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
    <style>
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        .detail-label { font-weight: 600; color: #4A5568; /* gray-700 */ }
        .detail-value { color: #2D3748; /* gray-800 */ }
        .detail-section-title { font-size: 1.25rem; font-weight: 700; color: #1A202C; /* gray-900 */ margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E2E8F0; /* gray-300 */}
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
            $barangay_logo_path = 'img/logo.png'; // Default logo
            $conn_temp = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (!$conn_temp->connect_error) {
                $logo_result = $conn_temp->query("SELECT setting_value FROM system_settings WHERE setting_key='barangay_logo_path' LIMIT 1");
                if ($logo_result && $logo_row = $logo_result->fetch_assoc()) {
                    if (!empty($logo_row['setting_value'])) {
                        $barangay_logo_path = $logo_row['setting_value'];
                    }
                }
                $conn_temp->close();
            }
            ?>
            <img src="<?php echo htmlspecialchars($barangay_logo_path); ?>" alt="Barangay Logo" class="w-28 h-28 object-cover rounded-full mb-1 border-2 border-white bg-white p-1" style="aspect-ratio:1/1;" onerror="this.onerror=null;this.src='img/logo.png';">
        </div>
        <nav class="flex flex-col p-4 gap-2">
            <?php
            $pages = [
                ['dashboard.php', 'fas fa-tachometer-alt', 'Dashboard'],
                ['individuals.php', 'fas fa-users', 'Residents'],
                ['families.php', 'fas fa-house-user', 'Families'],
                ['reports.php', 'fas fa-chart-bar', 'Reports'],
                ['certificate.php', 'fas fa-file-alt', 'Certificates'],
                ['announcement.php', 'fas fa-bullhorn', 'Announcement'],
                ['system_settings.php', 'fas fa-cogs', 'System Settings'],
            ];
            $current = 'individuals.php'; // Highlight Residents as active
            foreach ($pages as $page) {
                $isActive = $current === $page[0];
                $activeClass = $isActive ? 'bg-blue-600 text-white font-bold shadow-md' : 'text-white';
                $hoverClass = 'hover:bg-blue-500 hover:text-white';
                echo '<a href="' . $page[0] . '" class="py-2 px-3 rounded-lg flex items-center gap-2 ' . $activeClass . ' ' . $hoverClass . '">';
                echo '<i class="' . $page[1] . '"></i> ' . $page[2] . '</a>';
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
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($page_title); ?>: <?php echo display_data($resident_data['first_name'] ?? null) . ' ' . display_data($resident_data['last_name'] ?? null); ?></h1>
            <div class="flex gap-2">
                <a href="individuals.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow hover:shadow-md transition-all duration-150 ease-in-out">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
                <a href="edit_individual.php?id=<?php echo $resident_id; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow hover:shadow-md transition-all duration-150 ease-in-out">
                    <i class="fas fa-edit mr-2"></i> Edit Resident
                </a>
            </div>
        </div>

        <div class="bg-white shadow-xl rounded-lg p-6 md:p-8 space-y-6">
            <!-- Personal Information Section -->
            <div>
                <h2 class="detail-section-title">Personal Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    <div><span class="detail-label">Full Name:</span> <span class="detail-value"><?php echo display_data($resident_data['first_name'] ?? null) . ' ' . display_data($resident_data['middle_name'] ?? null, '') . ' ' . display_data($resident_data['last_name'] ?? null) . ' ' . display_data($resident_data['suffix'] ?? null, ''); ?></span></div>
                    <div><span class="detail-label">Sex/Gender:</span> <span class="detail-value"><?php echo ucfirst(display_data($resident_data['gender'] ?? null)); ?></span></div>
                    <div><span class="detail-label">Birthdate:</span> <span class="detail-value"><?php echo display_data($resident_data['formatted_birthdate'] ?? null); ?></span></div>
                    <div><span class="detail-label">Age:</span> <span class="detail-value"><?php echo display_data($resident_data['age'] ?? null); ?> years old</span></div>
                    <div><span class="detail-label">Civil Status:</span> <span class="detail-value"><?php echo display_data($resident_data['civil_status'] ?? null); ?></span></div>
                    <div><span class="detail-label">Blood Type:</span> <span class="detail-value"><?php echo display_data($resident_data['blood_type'] ?? null); ?></span></div>
                    <div><span class="detail-label">Place of Birth:</span> <span class="detail-value"><?php echo display_data($resident_data['place_of_birth'] ?? null); ?></span></div>
                    <div><span class="detail-label">Citizenship:</span> <span class="detail-value"><?php echo display_data($resident_data['citizenship'] ?? null); ?></span></div>
                    <div><span class="detail-label">Religion:</span> <span class="detail-value"><?php echo display_data($resident_data['religion'] ?? null); ?></span></div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div>
                <h2 class="detail-section-title">Contact Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    <div><span class="detail-label">Contact Number:</span> <span class="detail-value"><?php echo display_data($resident_data['contact_number'] ?? null); ?></span></div>
                    <div><span class="detail-label">Email:</span> <span class="detail-value"><?php echo display_data($resident_data['email'] ?? null); ?></span></div>
                </div>
            </div>

            <!-- Address Section -->
            <div>
                <h2 class="detail-section-title">Address</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    <div><span class="detail-label">Purok:</span> <span class="detail-value"><?php echo display_data($resident_data['current_purok'] ?? null); ?></span></div>
                    <div><span class="detail-label">Barangay:</span> <span class="detail-value"><?php echo display_data($resident_data['current_barangay'] ?? null); ?></span></div>
                    <div><span class="detail-label">Municipality/City:</span> <span class="detail-value"><?php echo display_data($resident_data['current_municipality'] ?? null); ?></span></div>
                    <div><span class="detail-label">Province:</span> <span class="detail-value"><?php echo display_data($resident_data['current_province'] ?? null); ?></span></div>
                </div>
            </div>
            <!-- Place of Birth Section -->
            <div>
                <h2 class="detail-section-title">Place of Birth</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    <div><span class="detail-label">Barangay:</span> <span class="detail-value"><?php echo display_data($resident_data['birthplace_barangay'] ?? null); ?></span></div>
                    <div><span class="detail-label">Municipality/City:</span> <span class="detail-value"><?php echo display_data($resident_data['birthplace_municipality'] ?? null); ?></span></div>
                    <div><span class="detail-label">Province:</span> <span class="detail-value"><?php echo display_data($resident_data['birthplace_province'] ?? null); ?></span></div>
                </div>
            </div>
            
            <!-- Other Information Section -->
            <div>
                <h2 class="detail-section-title">Other Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4">
                    <div><span class="detail-label">Occupation:</span> <span class="detail-value"><?php echo display_data($resident_data['occupation'] ?? null); ?></span></div>
                    <div><span class="detail-label">Educational Attainment:</span> <span class="detail-value"><?php echo display_data($resident_data['educational_attainment'] ?? null); ?></span></div>
                    <div><span class="detail-label">Registered Voter:</span> <span class="detail-value"><?php echo display_boolean($resident_data['is_voter'] ?? null); ?></span></div>
                    <div><span class="detail-label">4Ps Member:</span> <span class="detail-value"><?php echo display_boolean($resident_data['is_4ps_member'] ?? null); ?></span></div>
                    <div><span class="detail-label">PWD:</span> <span class="detail-value"><?php echo display_boolean($resident_data['is_pwd'] ?? null); ?></span></div>
                    <div><span class="detail-label">Solo Parent:</span> <span class="detail-value"><?php echo display_boolean($resident_data['is_solo_parent'] ?? null); ?></span></div>
                    <div><span class="detail-label">Pregnant:</span> <span class="detail-value"><?php echo display_boolean($resident_data['is_pregnant'] ?? null); ?></span></div>
                </div>
            </div>

            <!-- Timestamps -->
            <div>
                <h2 class="detail-section-title">Record Timestamps</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                     <div><span class="detail-label">Date Created:</span> <span class="detail-value"><?php echo display_data(isset($resident_data['created_at']) ? date('m/d/Y h:i A', strtotime($resident_data['created_at'])) : null); ?></span></div>
                     <div><span class="detail-label">Last Updated:</span> <span class="detail-value"><?php echo display_data(isset($resident_data['updated_at']) ? date('m/d/Y h:i A', strtotime($resident_data['updated_at'])) : null); ?></span></div>
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

        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', () => {
                userDropdownMenu.classList.toggle('show');
            });
        }
        
        document.addEventListener('click', function(event) {
            if (userDropdownMenu && !userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });
    </script>
    <script src="lib/assets/all.min.js" defer></script>
</body>
</html>
