<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
    exit();
}
require_once 'config.php';
require_once 'audit_logger.php'; // Include audit logging functions
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}

// Validate required fields
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$gender = trim($_POST['gender'] ?? '');

// Only require essential fields, others can be optional
if (!$id || !$first_name || !$last_name || !$gender) {
    // Debug: Show which fields are missing or empty
    $debug = [
        'id' => $id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'gender' => $gender
    ];
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ID, First Name, Last Name, and Gender are required.',
        'debug' => $debug
    ]);
    exit();
}

// Get optional fields
$birthdate = trim($_POST['birthdate'] ?? '');
$civil_status = trim($_POST['civil_status'] ?? '');
$religion = trim($_POST['religion'] ?? '');
$purok_id = !empty($_POST['purok_id']) ? intval($_POST['purok_id']) : null;

$middle_name = trim($_POST['middle_name'] ?? '');
$suffix = trim($_POST['suffix'] ?? '');
$blood_type = trim($_POST['blood_type'] ?? '');
$is_pwd = isset($_POST['is_pwd']) ? 1 : 0;
$is_voter = isset($_POST['is_voter']) ? 1 : 0;
$is_4ps = isset($_POST['is_4ps']) ? 1 : 0;
$is_pregnant = isset($_POST['is_pregnant']) ? 1 : 0;
$is_solo_parent = isset($_POST['is_solo_parent']) ? 1 : 0;

// Automatically determine senior citizen status based on age (60+ years)
$is_senior_citizen = 0;
if (!empty($birthdate)) {
    $birth_date = new DateTime($birthdate);
    $today = new DateTime();
    $age = $today->diff($birth_date)->y;
    $is_senior_citizen = ($age >= 60) ? 1 : 0;
}

$email = isset($_POST['email']) && !empty(trim($_POST['email'])) ? trim($_POST['email']) : null;

$stmt = $conn->prepare("UPDATE individuals SET first_name=?, middle_name=?, last_name=?, suffix=?, gender=?, birthdate=?, civil_status=?, blood_type=?, religion=?, purok_id=?, is_pwd=?, is_voter=?, is_4ps=?, is_pregnant=?, is_solo_parent=?, is_senior_citizen=?, email=? WHERE id=?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
    exit();
}

// Convert empty strings to NULL for optional fields
$birthdate = !empty($birthdate) ? $birthdate : null;
$civil_status = !empty($civil_status) ? $civil_status : null;
$blood_type = !empty($blood_type) ? $blood_type : null;
$religion = !empty($religion) ? $religion : null;

$stmt->bind_param(
    'sssssssssiiiiiiisi', // 9 strings, 1 int (purok_id), 6 integers (status fields), 1 string (email), 1 integer (id)
    $first_name,
    $middle_name,
    $last_name,
    $suffix,
    $gender,
    $birthdate,
    $civil_status,
    $blood_type,
    $religion,
    $purok_id,
    $is_pwd,
    $is_voter,
    $is_4ps,
    $is_pregnant,
    $is_solo_parent,
    $is_senior_citizen,
    $email,
    $id
);
$success = $stmt->execute();
if ($success) {
    // Log the resident update action
    $resident_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name . ' ' . $suffix);
    $changes = [
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'last_name' => $last_name,
        'suffix' => $suffix,
        'gender' => $gender,
        'birthdate' => $birthdate,
        'civil_status' => $civil_status,
        'blood_type' => $blood_type,
        'religion' => $religion,
        'purok_id' => $purok_id,
        'is_pwd' => $is_pwd,
        'is_voter' => $is_voter,
        'is_4ps' => $is_4ps,
        'is_pregnant' => $is_pregnant,
        'is_solo_parent' => $is_solo_parent,
        'is_senior_citizen' => $is_senior_citizen,
        'email' => $email
    ];
    
    logResidentAction($_SESSION['user_id'], 'Resident Updated', $id, $resident_name, $changes);
    
    echo json_encode(['success' => true, 'message' => 'Resident updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update resident.']);
}
$stmt->close();
$conn->close();
