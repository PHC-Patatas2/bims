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

$page_title = "Edit Resident Information";
$message = '';
$message_type = ''; // 'success' or 'error'
$resident_data = null;

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header('Location: individuals.php?error=invalid_id');
    exit();
}
$resident_id = $_GET['id'];

// Fetch existing resident data
$stmt_fetch = $conn->prepare("SELECT * FROM individuals WHERE id = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param('i', $resident_id);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    if ($result_fetch->num_rows === 1) {
        $resident_data = $result_fetch->fetch_assoc();
    } else {
        $message = "Resident not found.";
        $message_type = 'error';
        // Optionally redirect if resident not found after form post attempt or direct access
    }
    $stmt_fetch->close();
} else {
    die('Error preparing statement to fetch resident data.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$resident_data && $message_type !== 'error') { // Ensure resident_data was loaded unless already in error state
        $message = "Cannot update. Resident data not loaded correctly.";
        $message_type = 'error';
    } else if ($message_type !== 'error') { // Proceed only if no prior errors
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
        $barangay = trim($_POST['barangay'] ?? '');
        $municipality = trim($_POST['municipality'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $educational_attainment = $_POST['educational_attainment'] ?? '';
        $is_voter = isset($_POST['is_voter']) ? 1 : 0;
        $is_4ps_member = isset($_POST['is_4ps_member']) ? 1 : 0;
        $is_pwd = isset($_POST['is_pwd']) ? 1 : 0;
        $is_solo_parent = isset($_POST['is_solo_parent']) ? 1 : 0;
        $is_pregnant = isset($_POST['is_pregnant']) ? 1 : 0;

        if (empty($last_name) || empty($first_name) || empty($gender) || empty($birthdate) || empty($purok_street) || empty($barangay) || empty($municipality) || empty($province)) {
            $message = "Please fill in all required fields (Last Name, First Name, Gender, Birthdate, Address).";
            $message_type = 'error';
        } else {
            $sql = "UPDATE individuals SET last_name=?, first_name=?, middle_name=?, suffix=?, gender=?, birthdate=?, civil_status=?, blood_type=?, place_of_birth=?, citizenship=?, religion=?, contact_number=?, email=?, purok_street=?, barangay=?, municipality=?, province=?, occupation=?, educational_attainment=?, is_voter=?, is_4ps_member=?, is_pwd=?, is_solo_parent=?, is_pregnant=?, updated_at=NOW() WHERE id=?";
            $stmt_update = $conn->prepare($sql);
            if ($stmt_update) {
                $stmt_update->bind_param(
                    "ssssssssssssssssssssiiiis",
                    $last_name, $first_name, $middle_name, $suffix, $gender, $birthdate, $civil_status, $blood_type,
                    $place_of_birth, $citizenship, $religion, $contact_number, $email, $purok_street, $barangay,
                    $municipality, $province, $occupation, $educational_attainment, $is_voter, $is_4ps_member,
                    $is_pwd, $is_solo_parent, $is_pregnant, $resident_id
                );

                if ($stmt_update->execute()) {
                    $message = "Resident information updated successfully!";
                    $message_type = 'success';
                    // Re-fetch data to show updated values in the form
                    $stmt_refetch = $conn->prepare("SELECT * FROM individuals WHERE id = ?");
                    $stmt_refetch->bind_param('i', $resident_id);
                    $stmt_refetch->execute();
                    $result_refetch = $stmt_refetch->get_result();
                    $resident_data = $result_refetch->fetch_assoc();
                    $stmt_refetch->close();
                } else {
                    $message = "Error updating resident: " . $stmt_update->error;
                    $message_type = 'error';
                }
                $stmt_update->close();
            } else {
                $message = "Error preparing update statement: " . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

if (!$resident_data && $message_type !== 'error') { // If still no resident data and no specific error message set
    // This can happen if GET ID is valid but record doesn't exist.
    // The initial fetch attempt above would set $message and $message_type if resident not found.
    // If $message is still empty, it implies an issue not caught by initial fetch or direct invalid access.
    header('Location: individuals.php?error=notfound_critical');
    exit();
}

$conn->close();

// Helper to pre-fill form values
function old_value($field_name, $data_array, $default = '') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return htmlspecialchars($_POST[$field_name] ?? $default);
    }
    return htmlspecialchars($data_array[$field_name] ?? $default);
}

function old_checked($field_name, $data_array) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST[$field_name]) ? 'checked' : '';
    }
    return !empty($data_array[$field_name]) && $data_array[$field_name] ? 'checked' : '';
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
        .form-input { border-radius: 0.375rem; border: 1px solid #D1D5DB; padding: 0.5rem 0.75rem; width: 100%; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
        .form-input:focus { outline: none; border-color: #3B82F6; box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25); }
        .form-select { border-radius: 0.375rem; border: 1px solid #D1D5DB; padding: 0.5rem 0.75rem; width: 100%; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 0.5rem center; background-size: 1.5em 1.5em; -webkit-appearance: none; -moz-appearance: none; appearance: none; }
        .form-checkbox { border-radius: 0.25rem; border: 1px solid #D1D5DB; color: #3B82F6; }
        .form-checkbox:focus { outline: none; ring: 2px; ring-offset: 2px; ring-color: #3B82F6; }
        .required-label::after { content: ' *'; color: red; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Sidepanel -->
    <div id="sidepanel" class="fixed top-0 left-0 h-full w-64 shadow-lg z-40 transform -translate-x-full transition-transform duration-300 ease-in-out sidebar-border" style="background-color: #454545;">
        <div class="flex flex-col items-center justify-center min-h-[90px] px-4 pt-3 pb-3 relative" style="border-bottom: 4px solid #FFD700;">
            <button id="closeSidepanel" class="absolute right-2 top-2 text-white hover:text-blue-400 focus:outline-none text-2xl md:hidden" aria-label="Close menu"><i class="fas fa-times"></i></button>
            <?php
            $barangay_logo_path = 'img/logo.png';
            $conn_temp = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (!$conn_temp->connect_error) {
                $logo_result = $conn_temp->query("SELECT setting_value FROM system_settings WHERE setting_key='barangay_logo_path' LIMIT 1");
                if ($logo_result && $logo_row = $logo_result->fetch_assoc()) {
                    if (!empty($logo_row['setting_value'])) $barangay_logo_path = $logo_row['setting_value'];
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
                ['business_permit.php', 'fas fa-briefcase', 'Business Permits'],
                ['blotter_records.php', 'fas fa-book', 'Blotter'],
                ['system_settings.php', 'fas fa-cogs', 'System Settings'],
            ];
            $current = 'individuals.php';
            foreach ($pages as $page) {
                $isActive = $current === $page[0];
                $activeClass = $isActive ? 'bg-blue-600 text-white font-bold shadow-md' : 'text-white';
                $hoverClass = 'hover:bg-blue-500 hover:text-white';
                echo '<a href="' . $page[0] . '" class="py-2 px-3 rounded-lg flex items-center gap-2 ' . $activeClass . ' ' . $hoverClass . '"><i class="' . $page[1] . '"></i> ' . $page[2] . '</a>';
            }
            ?>
        </nav>
    </div>
    <div id="sidepanelOverlay" class="fixed inset-0 bg-black bg-opacity-30 z-30 hidden"></div>
    <nav class="fixed top-0 left-0 right-0 z-30 bg-white shadow flex items-center justify-between h-16 px-4 md:px-8">
        <div class="flex items-center gap-2">
            <button id="menuBtn" class="h-8 w-8 mr-2 flex items-center justify-center text-blue-700 focus:outline-none"><i class="fas fa-bars text-2xl"></i></button>
            <span class="font-bold text-lg text-blue-700"><?php echo htmlspecialchars($system_title); ?></span>
        </div>
        <div class="relative flex items-center gap-2">
            <span class="hidden sm:inline text-gray-700 font-medium">Welcome, <?php echo htmlspecialchars($user_full_name); ?></span>
            <button id="userDropdownBtn" class="focus:outline-none flex items-center gap-1 px-2 py-1 rounded hover:bg-gray-100"><i class="fas fa-chevron-down"></i></button>
            <div id="userDropdownMenu" class="dropdown-menu mt-2">
                <a href="profile.php" class="block px-4 py-2 hover:bg-gray-100 text-gray-700"><i class="fas fa-user mr-2"></i>Manage Profile</a>
                <a href="logout.php" class="block px-4 py-2 hover:bg-gray-100 text-red-600"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>

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

        <?php if ($resident_data): // Only show form if resident data is loaded ?>
        <form action="edit_individual.php?id=<?php echo $resident_id; ?>" method="POST" class="bg-white shadow-xl rounded-lg p-6 md:p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 required-label">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-input mt-1" value="<?php echo old_value('last_name', $resident_data); ?>" required>
                </div>
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 required-label">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="form-input mt-1" value="<?php echo old_value('first_name', $resident_data); ?>" required>
                </div>
                <div>
                    <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" class="form-input mt-1" value="<?php echo old_value('middle_name', $resident_data); ?>">
                </div>
                <div>
                    <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                    <input type="text" name="suffix" id="suffix" class="form-input mt-1" value="<?php echo old_value('suffix', $resident_data); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700 required-label">Sex / Gender</label>
                    <select name="gender" id="gender" class="form-select mt-1" required>
                        <option value="male" <?php echo (old_value('gender', $resident_data) == 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (old_value('gender', $resident_data) == 'female') ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label for="birthdate" class="block text-sm font-medium text-gray-700 required-label">Birthdate</label>
                    <input type="date" name="birthdate" id="birthdate" class="form-input mt-1" value="<?php echo old_value('birthdate', $resident_data); ?>" required>
                </div>
                <div>
                    <label for="civil_status" class="block text-sm font-medium text-gray-700">Civil Status</label>
                    <select name="civil_status" id="civil_status" class="form-select mt-1">
                        <option value="" <?php echo (old_value('civil_status', $resident_data) == '') ? 'selected' : ''; ?>>Select Civil Status</option>
                        <option value="Single" <?php echo (old_value('civil_status', $resident_data) == 'Single') ? 'selected' : ''; ?>>Single</option>
                        <option value="Married" <?php echo (old_value('civil_status', $resident_data) == 'Married') ? 'selected' : ''; ?>>Married</option>
                        <option value="Widowed" <?php echo (old_value('civil_status', $resident_data) == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                        <option value="Separated" <?php echo (old_value('civil_status', $resident_data) == 'Separated') ? 'selected' : ''; ?>>Separated</option>
                        <option value="Divorced" <?php echo (old_value('civil_status', $resident_data) == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                        <option value="Annulled" <?php echo (old_value('civil_status', $resident_data) == 'Annulled') ? 'selected' : ''; ?>>Annulled</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                 <div>
                    <label for="blood_type" class="block text-sm font-medium text-gray-700">Blood Type</label>
                    <select name="blood_type" id="blood_type" class="form-select mt-1">
                        <option value="" <?php echo (old_value('blood_type', $resident_data) == '') ? 'selected' : ''; ?>>Select Blood Type</option>
                        <option value="A+" <?php echo (old_value('blood_type', $resident_data) == 'A+') ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo (old_value('blood_type', $resident_data) == 'A-') ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo (old_value('blood_type', $resident_data) == 'B+') ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo (old_value('blood_type', $resident_data) == 'B-') ? 'selected' : ''; ?>>B-</option>
                        <option value="AB+" <?php echo (old_value('blood_type', $resident_data) == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo (old_value('blood_type', $resident_data) == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                        <option value="O+" <?php echo (old_value('blood_type', $resident_data) == 'O+') ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo (old_value('blood_type', $resident_data) == 'O-') ? 'selected' : ''; ?>>O-</option>
                        <option value="Unknown" <?php echo (old_value('blood_type', $resident_data) == 'Unknown') ? 'selected' : ''; ?>>Unknown</option>
                    </select>
                </div>
                <div>
                    <label for="place_of_birth" class="block text-sm font-medium text-gray-700">Place of Birth</label>
                    <input type="text" name="place_of_birth" id="place_of_birth" class="form-input mt-1" value="<?php echo old_value('place_of_birth', $resident_data); ?>">
                </div>
                <div>
                    <label for="citizenship" class="block text-sm font-medium text-gray-700">Citizenship</label>
                    <input type="text" name="citizenship" id="citizenship" class="form-input mt-1" value="<?php echo old_value('citizenship', $resident_data, 'Filipino'); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label for="religion" class="block text-sm font-medium text-gray-700">Religion</label>
                    <input type="text" name="religion" id="religion" class="form-input mt-1" value="<?php echo old_value('religion', $resident_data); ?>">
                </div>
                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                    <input type="tel" name="contact_number" id="contact_number" class="form-input mt-1" value="<?php echo old_value('contact_number', $resident_data); ?>">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" id="email" class="form-input mt-1" value="<?php echo old_value('email', $resident_data); ?>">
                </div>
            </div>

            <fieldset class="border p-4 rounded-md">
                <legend class="text-lg font-semibold text-gray-700 px-2">Address</legend>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mt-2">
                    <div>
                        <label for="purok_street" class="block text-sm font-medium text-gray-700 required-label">Purok / Street / Subd.</label>
                        <input type="text" name="purok_street" id="purok_street" class="form-input mt-1" value="<?php echo old_value('purok_street', $resident_data); ?>" required>
                    </div>
                    <div>
                        <label for="barangay" class="block text-sm font-medium text-gray-700 required-label">Barangay</label>
                        <input type="text" name="barangay" id="barangay" class="form-input mt-1" value="<?php echo old_value('barangay', $resident_data); ?>" required>
                    </div>
                    <div>
                        <label for="municipality" class="block text-sm font-medium text-gray-700 required-label">Municipality / City</label>
                        <input type="text" name="municipality" id="municipality" class="form-input mt-1" value="<?php echo old_value('municipality', $resident_data); ?>" required>
                    </div>
                    <div>
                        <label for="province" class="block text-sm font-medium text-gray-700 required-label">Province</label>
                        <input type="text" name="province" id="province" class="form-input mt-1" value="<?php echo old_value('province', $resident_data); ?>" required>
                    </div>
                </div>
            </fieldset>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="occupation" class="block text-sm font-medium text-gray-700">Occupation</label>
                    <input type="text" name="occupation" id="occupation" class="form-input mt-1" value="<?php echo old_value('occupation', $resident_data); ?>">
                </div>
                <div>
                    <label for="educational_attainment" class="block text-sm font-medium text-gray-700">Educational Attainment</label>
                    <select name="educational_attainment" id="educational_attainment" class="form-select mt-1">
                        <option value="" <?php echo (old_value('educational_attainment', $resident_data) == '') ? 'selected' : ''; ?>>Select Attainment</option>
                        <option value="No Formal Education" <?php echo (old_value('educational_attainment', $resident_data) == 'No Formal Education') ? 'selected' : ''; ?>>No Formal Education</option>
                        <option value="Elementary Level" <?php echo (old_value('educational_attainment', $resident_data) == 'Elementary Level') ? 'selected' : ''; ?>>Elementary Level</option>
                        <option value="Elementary Graduate" <?php echo (old_value('educational_attainment', $resident_data) == 'Elementary Graduate') ? 'selected' : ''; ?>>Elementary Graduate</option>
                        <option value="High School Level" <?php echo (old_value('educational_attainment', $resident_data) == 'High School Level') ? 'selected' : ''; ?>>High School Level</option>
                        <option value="High School Graduate" <?php echo (old_value('educational_attainment', $resident_data) == 'High School Graduate') ? 'selected' : ''; ?>>High School Graduate</option>
                        <option value="Vocational/Trade Course" <?php echo (old_value('educational_attainment', $resident_data) == 'Vocational/Trade Course') ? 'selected' : ''; ?>>Vocational/Trade Course</option>
                        <option value="College Level" <?php echo (old_value('educational_attainment', $resident_data) == 'College Level') ? 'selected' : ''; ?>>College Level</option>
                        <option value="College Graduate" <?php echo (old_value('educational_attainment', $resident_data) == 'College Graduate') ? 'selected' : ''; ?>>College Graduate</option>
                        <option value="Post Graduate" <?php echo (old_value('educational_attainment', $resident_data) == 'Post Graduate') ? 'selected' : ''; ?>>Post Graduate</option>
                        <option value="Other" <?php echo (old_value('educational_attainment', $resident_data) == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>

            <fieldset class="border p-4 rounded-md">
                <legend class="text-lg font-semibold text-gray-700 px-2">Other Information</legend>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-6 gap-y-4 mt-2">
                    <div class="flex items-center">
                        <input id="is_voter" name="is_voter" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600" <?php echo old_checked('is_voter', $resident_data); ?>>
                        <label for="is_voter" class="ml-2 block text-sm text-gray-900">Registered Voter?</label>
                    </div>
                    <div class="flex items-center">
                        <input id="is_4ps_member" name="is_4ps_member" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600" <?php echo old_checked('is_4ps_member', $resident_data); ?>>
                        <label for="is_4ps_member" class="ml-2 block text-sm text-gray-900">4Ps Member?</label>
                    </div>
                    <div class="flex items-center">
                        <input id="is_pwd" name="is_pwd" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600" <?php echo old_checked('is_pwd', $resident_data); ?>>
                        <label for="is_pwd" class="ml-2 block text-sm text-gray-900">PWD?</label>
                    </div>
                    <div class="flex items-center">
                        <input id="is_solo_parent" name="is_solo_parent" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600" <?php echo old_checked('is_solo_parent', $resident_data); ?>>
                        <label for="is_solo_parent" class="ml-2 block text-sm text-gray-900">Solo Parent?</label>
                    </div>
                    <div class="flex items-center">
                        <input id="is_pregnant" name="is_pregnant" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600" <?php echo old_checked('is_pregnant', $resident_data); ?>>
                        <label for="is_pregnant" class="ml-2 block text-sm text-gray-900">Pregnant?</label>
                    </div>
                </div>
            </fieldset>

            <div class="flex justify-end space-x-3 pt-4">
                <a href="view_individual.php?id=<?php echo $resident_id; ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow hover:shadow-md transition-all duration-150 ease-in-out">
                    <i class="fas fa-save mr-2"></i> Save Changes
                </button>
            </div>
        </form>
        <?php else: ?>
            <?php if (empty($message)): // Show a generic message if no specific error was set and data is null ?>
            <div class="mb-4 p-4 rounded-md bg-yellow-100 text-yellow-700" role="alert">
                Could not load resident data. The resident may have been deleted or the ID is incorrect.
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
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

        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', () => userDropdownMenu.classList.toggle('show'));
        }
        document.addEventListener('click', function(event) {
            if (userDropdownMenu && !userDropdownBtn.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.remove('show');
            }
        });

        <?php if ($message_type === 'success' && !empty($message)): ?>
        setTimeout(() => {
            const successMessageDiv = document.querySelector('.bg-green-100');
            if (successMessageDiv) {
                successMessageDiv.style.transition = 'opacity 0.5s ease';
                successMessageDiv.style.opacity = '0';
                setTimeout(() => successMessageDiv.remove(), 500);
            }
        }, 3000); // Hide after 3 seconds
        <?php endif; ?>
    </script>
    <script src="lib/assets/all.min.js" defer></script>
</body>
</html>
