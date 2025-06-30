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

$page_title = "Add New Resident";
$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    $blood_type = $_POST['blood_type'] ?? '';
    $place_of_birth = trim($_POST['place_of_birth'] ?? '');
    $citizenship = trim($_POST['citizenship'] ?? 'Filipino');
    $religion = trim($_POST['religion'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $purok_street = trim($_POST['purok_street'] ?? '');
    $barangay = trim($_POST['barangay'] ?? ''); // Should ideally be pre-filled from system settings
    $municipality = trim($_POST['municipality'] ?? ''); // Should ideally be pre-filled from system settings
    $province = trim($_POST['province'] ?? ''); // Should ideally be pre-filled from system settings
    $is_voter = isset($_POST['is_voter']) ? 1 : 0;
    $is_4ps_member = isset($_POST['is_4ps_member']) ? 1 : 0;
    $is_pwd = isset($_POST['is_pwd']) ? 1 : 0;
    $is_solo_parent = isset($_POST['is_solo_parent']) ? 1 : 0;
    $is_pregnant = isset($_POST['is_pregnant']) ? 1 : 0;

    // Basic validation
    if (empty($last_name) || empty($first_name) || empty($gender) || empty($birthdate) || empty($purok_street) || empty($barangay) || empty($municipality) || empty($province)) {
        $message = "Please fill in all required fields (Last Name, First Name, Gender, Birthdate, Address).";
        $message_type = 'error';
    } else {
        $sql = "INSERT INTO individuals (last_name, first_name, middle_name, suffix, gender, birthdate, civil_status, blood_type, place_of_birth, citizenship, religion, contact_number, email, purok_street, barangay, municipality, province, is_voter, is_4ps_member, is_pwd, is_solo_parent, is_pregnant, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "ssssssssssssssssiiii",
                $last_name, $first_name, $middle_name, $suffix, $gender, $birthdate, $civil_status, $blood_type,
                $place_of_birth, $citizenship, $religion, $contact_number, $email, $purok_street, $barangay,
                $municipality, $province, $is_voter, $is_4ps_member,
                $is_pwd, $is_solo_parent, $is_pregnant
            );

            if ($stmt->execute()) {
                $message = "New resident added successfully!";
                $message_type = 'success';
                // Clear form fields by redirecting or resetting variables - for now, just a message
            } else {
                $message = "Error adding resident: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Error preparing statement: " . $conn->error;
            $message_type = 'error';
        }
    }
}

// Fetch default barangay, municipality, province from system_settings
$default_barangay = '';
$default_municipality = '';
$default_province = '';

$settings_keys = ['barangay_name', 'municipality_name', 'province_name'];
$settings_placeholders = implode(',', array_fill(0, count($settings_keys), '?'));
$stmt_settings = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ($settings_placeholders)");
if ($stmt_settings) {
    $stmt_settings->bind_param(str_repeat('s', count($settings_keys)), ...$settings_keys);
    $stmt_settings->execute();
    $result_settings = $stmt_settings->get_result();
    while ($row = $result_settings->fetch_assoc()) {
        if ($row['setting_key'] === 'barangay_name') $default_barangay = $row['setting_value'];
        if ($row['setting_key'] === 'municipality_name') $default_municipality = $row['setting_value'];
        if ($row['setting_key'] === 'province_name') $default_province = $row['setting_value'];
    }
    $stmt_settings->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" integrity="sha512-u3fPA7V/q_dR0APDDUuOzvKFBBHlAwKRj5lHZRt1gs3osuTRswblYIWkxVAqkSgM3/CaHXMwEcOuc_2Nqbuhmw==" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <style>
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        .form-input {
            border-radius: 0.375rem;
            border: 2px solid #111827; /* gray-900, thicker and blacker */
            padding: 0.5rem 0.75rem;
            width: 100%;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-input:focus {
            outline: none;
            border-color: #1e293b; /* even darker on focus */
            box-shadow: 0 0 0 0.2rem rgba(30, 41, 59, 0.15);
        }
        .form-select {
            border-radius: 0.375rem;
            border: 2px solid #111827; /* match form-input: gray-900, thicker and blacker */
            padding: 0.5rem 0.75rem;
            width: 100%;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.5em 1.5em;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-select:focus {
            outline: none;
            border-color: #1e293b; /* even darker on focus, match form-input */
            box-shadow: 0 0 0 0.2rem rgba(30, 41, 59, 0.15);
        }
        .form-checkbox {
            border-radius: 0.25rem;
            border: 1px solid #D1D5DB; /* gray-300 */
            color: #3B82F6; /* blue-500 */
        }
        .form-checkbox:focus {
            outline: none;
            ring: 2px;
            ring-offset: 2px;
            ring-color: #3B82F6; /* blue-500 */
        }
        .required-label::after {
            content: ' *';
            color: red;
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
            $barangay_logo_path = 'img/logo.png'; // Default logo
            // Re-establish connection for this small query as $conn was closed.
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
                ['reports.php', 'fas fa-chart-bar', 'Reports'],
                ['certificate.php', 'fas fa-file-alt', 'Certificates'],
            ];
            $current = basename($_SERVER['PHP_SELF']);
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
            <a href="individuals.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg inline-flex items-center shadow hover:shadow-md transition-all duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i> Back to Residents List
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-4 p-4 rounded-md <?php echo $message_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="add_individual.php" method="POST" class="bg-white shadow-xl rounded-lg p-6 md:p-8 space-y-6">
            
            <!-- Personal Information Section -->
            <div class="mb-6">
                <h3 class="text-2xl font-extrabold text-gray-800 mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 required-label">Last Name</label>
                        <input type="text" name="last_name" id="last_name" class="form-input mt-1" required>
                    </div>
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 required-label">First Name</label>
                        <input type="text" name="first_name" id="first_name" class="form-input mt-1" required>
                    </div>
                    <div>
                        <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" class="form-input mt-1" placeholder="(leave blank if none)">
                    </div>
                    <div>
                        <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix (e.g., Jr., Sr., III)</label>
                        <input type="text" name="suffix" id="suffix" class="form-input mt-1" placeholder="(leave blank if none)">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 required-label">Sex / Gender</label>
                        <select name="gender" id="gender" class="form-select mt-1" required>
                            <option value="" disabled selected>Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label for="birthdate" class="block text-sm font-medium text-gray-700 required-label">Birthdate</label>
                        <input type="date" name="birthdate" id="birthdate" class="form-input mt-1" required>
                    </div>
                    <div>
                        <label for="civil_status" class="block text-sm font-medium text-gray-700">Civil Status</label>
                        <select name="civil_status" id="civil_status" class="form-select mt-1">
                            <option value="" disabled selected>Select Civil Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Separated">Separated</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Annulled">Annulled</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                    <div>
                        <label for="blood_type" class="block text-sm font-medium text-gray-700">Blood Type</label>
                        <select name="blood_type" id="blood_type" class="form-select mt-1">
                            <option value="" disabled selected>Select Blood Type</option>
                            <option value="A+">A+</option> <option value="A-">A-</option>
                            <option value="B+">B+</option> <option value="B-">B-</option>
                            <option value="AB+">AB+</option> <option value="AB-">AB-</option>
                            <option value="O+">O+</option> <option value="O-">O-</option>
                            <option value="Unknown">Unknown</option>
                        </select>
                    </div>
                    <div>
                        <label for="citizenship" class="block text-sm font-medium text-gray-700">Citizenship</label>
                        <input type="text" name="citizenship" id="citizenship" class="form-input mt-1" value="Filipino">
                    </div>
                    <div>
                        <label for="religion" class="block text-sm font-medium text-gray-700">Religion</label>
                        <select name="religion" id="religion" class="form-select mt-1">
                            <option value="" disabled selected>Select Religion</option>
                            <option value="Roman Catholic">Roman Catholic</option>
                            <option value="Iglesia ni Cristo">Iglesia ni Cristo</option>
                            <option value="Evangelical (Born Again)">Evangelical (Born Again)</option>
                            <option value="Seventh-day Adventist">Seventh-day Adventist</option>
                            <option value="Jehovah’s Witnesses">Jehovah’s Witnesses</option>
                            <option value="Baptist">Baptist</option>
                            <option value="United Church of Christ in the Philippines (UCCP)">United Church of Christ in the Philippines (UCCP)</option>
                            <option value="Islam (Muslim)">Islam (Muslim)</option>
                            <option value="Aglipayan / Philippine Independent Church">Aglipayan / Philippine Independent Church</option>
                            <option value="Pentecostal">Pentecostal</option>
                            <option value="Methodist">Methodist</option>
                            <option value="Lutheran">Lutheran</option>
                            <option value="Orthodox Christian">Orthodox Christian</option>
                            <option value="Church of Jesus Christ of Latter-day Saints (Mormon)">Church of Jesus Christ of Latter-day Saints (Mormon)</option>
                            <option value="Buddhist">Buddhist</option>
                            <option value="Hindu">Hindu</option>
                            <option value="Judaism (Jewish)">Judaism (Jewish)</option>
                            <option value="No Religion / Atheist / Agnostic">No Religion / Atheist / Agnostic</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr class="my-6 border-gray-500 border-2">
            <!-- Place of Birth Section -->
            <div class="mb-6">
                <h3 class="text-2xl font-extrabold text-gray-800 mb-4">Place of Birth</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="birthplace_province" class="block text-sm font-medium text-gray-700 required-label">Province</label>
                        <select name="birthplace_province" id="birthplace_province" class="form-select mt-1" required>
                            <option value="" disabled selected>Select Province</option>
                        </select>
                    </div>
                    <div>
                        <label for="birthplace_municipality" class="block text-sm font-medium text-gray-700 required-label">Municipality / City</label>
                        <select name="birthplace_municipality" id="birthplace_municipality" class="form-select mt-1" required>
                            <option value="" disabled selected>Select Municipality / City</option>
                        </select>
                    </div>
                    <div>
                        <label for="birthplace_barangay" class="block text-sm font-medium text-gray-700 required-label">Barangay</label>
                        <select name="birthplace_barangay" id="birthplace_barangay" class="form-select mt-1" required>
                            <option value="" disabled selected>Select Barangay</option>
                        </select>
                    </div>
                    <div>
                        <label for="birthplace_purok" class="block text-sm font-medium text-gray-700 required-label">Purok</label>
                        <select name="birthplace_purok" id="birthplace_purok" class="form-select mt-1" required>
                            <option value="" disabled selected>Select Purok</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr class="my-6 border-gray-500 border-2">
            <!-- Current Address Section -->
            <div class="mb-6">
                <h3 class="text-2xl font-extrabold text-gray-800 mb-4">Current Address</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label for="current_province" class="block text-sm font-medium text-gray-700 required-label">Province</label>
                        <select name="current_province" id="current_province" class="form-select mt-1" required>
                            <option value="Bulacan" selected>Bulacan</option>
                        </select>
                    </div>
                    <div>
                        <label for="current_municipality" class="block text-sm font-medium text-gray-700 required-label">Municipality / City</label>
                        <select name="current_municipality" id="current_municipality" class="form-select mt-1" required>
                            <option value="Calumpit" selected>Calumpit</option>
                        </select>
                    </div>
                    <div>
                        <label for="current_barangay" class="block text-sm font-medium text-gray-700 required-label">Barangay</label>
                        <select name="current_barangay" id="current_barangay" class="form-select mt-1" required>
                            <option value="Sucol" selected>Sucol</option>
                        </select>
                    </div>
                    <div>
                        <label for="current_purok" class="block text-sm font-medium text-gray-700 required-label">Purok</label>
                        <select name="current_purok" id="current_purok" class="form-select mt-1" required>
                            <option value="" disabled selected>Select Purok</option>
                            <option value="Purok 1">Purok 1</option>
                            <option value="Purok 2">Purok 2</option>
                            <option value="Purok 3">Purok 3</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr class="my-6 border-gray-500 border-2">
            <!-- Contact Information Section -->
            <div class="mb-6">
                <h3 class="text-2xl font-extrabold text-gray-800 mb-4">Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="tel" name="contact_number" id="contact_number" class="form-input mt-1" placeholder="e.g., 09123456789">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" id="email" class="form-input mt-1" placeholder="e.g., juan.delacruz@example.com">
                    </div>
                </div>
            </div>
            <hr class="my-6 border-gray-500 border-2">
            <!-- Other Information Section -->
            <div class="mb-6">
                <h3 class="text-2xl font-extrabold text-gray-800 mb-4">Other Information</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-6 gap-y-4 mt-2">
                    <div class="flex items-center">
                        <input id="is_voter" name="is_voter" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                        <label for="is_voter" class="ml-2 block text-sm text-gray-900">Registered Voter?</label>
                    </div>
                    <div class="flex items-center">
                        <input id="is_4ps_member" name="is_4ps_member" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                        <label for="is_4ps_member" class="ml-2 block text-sm text-gray-900">4Ps Member?</label>
                    </div>
                    <div class="flex items-center">
                        <input id="is_pwd" name="is_pwd" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                        <label for="is_pwd" class="ml-2 block text-sm text-gray-900">PWD?</label>
                    </div>
                    <div class="flex items-center">
                        <input id="is_solo_parent" name="is_solo_parent" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                        <label for="is_solo_parent" class="ml-2 block text-sm text-gray-900">Solo Parent?</label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="individuals.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out">
                    <i class="fas fa-save mr-2"></i> Save Resident
                </button>
            </div>
        </form>
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
        
        // Close dropdowns if clicked outside
        document.addEventListener('click', function(event) {
            if (userDropdownMenu && !userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });

        // Clear message after a few seconds if it's a success message
        <?php if ($message_type === 'success' && !empty($message)): ?>
        setTimeout(() => {
            const successMessageDiv = document.querySelector('.bg-green-100');
            if (successMessageDiv) {
                successMessageDiv.style.transition = 'opacity 0.5s ease';
                successMessageDiv.style.opacity = '0';
                setTimeout(() => successMessageDiv.remove(), 500);
            }
        }, 5000); // Hide after 5 seconds
        <?php endif; ?>

        // Load PH locations and populate dropdowns
        let phLocations = null;
        fetch('lib/assets/ph_locations.json')
            .then(res => res.json())
            .then(data => {
                phLocations = data;
                const provinceSelect = document.getElementById('birthplace_province');
                data.provinces.forEach(prov => {
                    const opt = document.createElement('option');
                    opt.value = prov.name;
                    opt.textContent = prov.name;
                    provinceSelect.appendChild(opt);
                });
            });

        document.getElementById('birthplace_province').addEventListener('change', function() {
            const provName = this.value;
            const munSelect = document.getElementById('birthplace_municipality');
            const brgySelect = document.getElementById('birthplace_barangay');
            const purokSelect = document.getElementById('birthplace_purok');
            munSelect.innerHTML = '<option value="" disabled selected>Select Municipality / City</option>';
            brgySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            purokSelect.innerHTML = '<option value="" disabled selected>Select Purok</option>';
            if (!phLocations) return;
            const prov = phLocations.provinces.find(p => p.name === provName);
            if (prov) {
                prov.municipalities.forEach(mun => {
                    const opt = document.createElement('option');
                    opt.value = mun.name;
                    opt.textContent = mun.name;
                    munSelect.appendChild(opt);
                });
            }
        });

        document.getElementById('birthplace_municipality').addEventListener('change', function() {
            const provName = document.getElementById('birthplace_province').value;
            const munName = this.value;
            const brgySelect = document.getElementById('birthplace_barangay');
            const purokSelect = document.getElementById('birthplace_purok');
            brgySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            purokSelect.innerHTML = '<option value="" disabled selected>Select Purok</option>';
            if (!phLocations) return;
            const prov = phLocations.provinces.find(p => p.name === provName);
            if (prov) {
                const mun = prov.municipalities.find(m => m.name === munName);
                if (mun) {
                    mun.barangays.forEach(brgy => {
                        const opt = document.createElement('option');
                        opt.value = brgy.name;
                        opt.textContent = brgy.name;
                        brgySelect.appendChild(opt);
                    });
                }
            }
        });

        document.getElementById('birthplace_barangay').addEventListener('change', function() {
            const provName = document.getElementById('birthplace_province').value;
            const munName = document.getElementById('birthplace_municipality').value;
            const brgyName = this.value;
            const purokSelect = document.getElementById('birthplace_purok');
            purokSelect.innerHTML = '<option value="" disabled selected>Select Purok</option>';
            if (!phLocations) return;
            const prov = phLocations.provinces.find(p => p.name === provName);
            if (prov) {
                const mun = prov.municipalities.find(m => m.name === munName);
                if (mun) {
                    const brgy = mun.barangays.find(b => b.name === brgyName);
                    if (brgy && brgy.puroks) {
                        brgy.puroks.forEach(purok => {
                            const opt = document.createElement('option');
                            opt.value = purok;
                            opt.textContent = purok;
                            purokSelect.appendChild(opt);
                        });
                    }
                }
            }
        });

        // Current Address cascading dropdowns (similar to Place of Birth)
        let currentAddressInitialized = false;
        document.getElementById('current_province').addEventListener('change', function() {
            const provName = this.value;
            const munSelect = document.getElementById('current_municipality');
            const brgySelect = document.getElementById('current_barangay');
            const purokSelect = document.getElementById('current_purok');
            munSelect.innerHTML = '<option value="" disabled selected>Select Municipality / City</option>';
            brgySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            purokSelect.innerHTML = '<option value="" disabled selected>Select Purok</option>';
            if (!phLocations) return;
            const prov = phLocations.provinces.find(p => p.name === provName);
            if (prov) {
                prov.municipalities.forEach(mun => {
                    const opt = document.createElement('option');
                    opt.value = mun.name;
                    opt.textContent = mun.name;
                    munSelect.appendChild(opt);
                });
            }
            currentAddressInitialized = true;
        });

        document.getElementById('current_municipality').addEventListener('change', function() {
            const provName = document.getElementById('current_province').value;
            const munName = this.value;
            const brgySelect = document.getElementById('current_barangay');
            const purokSelect = document.getElementById('current_purok');
            brgySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            purokSelect.innerHTML = '<option value="" disabled selected>Select Purok</option>';
            if (!phLocations) return;
            const prov = phLocations.provinces.find(p => p.name === provName);
            if (prov) {
                const mun = prov.municipalities.find(m => m.name === munName);
                if (mun) {
                    mun.barangays.forEach(brgy => {
                        const opt = document.createElement('option');
                        opt.value = brgy.name;
                        opt.textContent = brgy.name;
                        brgySelect.appendChild(opt);
                    });
                }
            }
        });

        document.getElementById('current_barangay').addEventListener('change', function() {
            const provName = document.getElementById('current_province').value;
            const munName = document.getElementById('current_municipality').value;
            const brgyName = this.value;
            const purokSelect = document.getElementById('current_purok');
            purokSelect.innerHTML = '<option value="" disabled selected>Select Purok</option>';
            if (!phLocations) return;
            const prov = phLocations.provinces.find(p => p.name === provName);
            if (prov) {
                const mun = prov.municipalities.find(m => m.name === munName);
                if (mun) {
                    const brgy = mun.barangays.find(b => b.name === brgyName);
                    if (brgy && brgy.puroks) {
                        brgy.puroks.forEach(purok => {
                            const opt = document.createElement('option');
                            opt.value = purok;
                            opt.textContent = purok;
                            purokSelect.appendChild(opt);
                        });
                    }
                }
            }
        });
    </script>
</body>
</html>
