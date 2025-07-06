<?php
require_once 'config.php';
header('Content-Type: application/json');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed.']);
    exit();
}
// Validate required fields
$required = ['first_name', 'last_name', 'gender', 'purok_id'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => 'Missing required field: ' . $field]);
        exit();
    }
}
// Prepare data
$first_name = trim($_POST['first_name']);
$middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : null;
$last_name = trim($_POST['last_name']);
$suffix = isset($_POST['suffix']) ? trim($_POST['suffix']) : null;
$gender = $_POST['gender'];
$birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
$civil_status = !empty($_POST['civil_status']) ? $_POST['civil_status'] : null;
$blood_type = !empty($_POST['blood_type']) ? $_POST['blood_type'] : null;
$religion = !empty($_POST['religion']) ? $_POST['religion'] : null;
$is_pwd = isset($_POST['is_pwd']) ? 1 : 0;
$is_voter = isset($_POST['is_voter']) ? 1 : 0;
$is_4ps = isset($_POST['is_4ps']) ? 1 : 0;
$is_pregnant = isset($_POST['is_pregnant']) ? 1 : 0;
$is_solo_parent = isset($_POST['is_solo_parent']) ? 1 : 0;
$purok_id = intval($_POST['purok_id']);
// Check if purok_id exists
$purok_check = $conn->prepare('SELECT id FROM purok WHERE id = ? LIMIT 1');
$purok_check->bind_param('i', $purok_id);
$purok_check->execute();
$purok_check->store_result();
if ($purok_check->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Selected Purok does not exist. Please select a valid Purok.']);
    $purok_check->close();
    $conn->close();
    exit();
}
$purok_check->close();
// Insert
$stmt = $conn->prepare("INSERT INTO individuals (first_name, middle_name, last_name, suffix, gender, birthdate, civil_status, blood_type, religion, is_pwd, is_voter, is_4ps, is_pregnant, is_solo_parent, purok_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement: ' . $conn->error]);
    $conn->close();
    exit();
}
// Corrected: 9 strings (first_name, middle_name, last_name, suffix, gender, birthdate, civil_status, blood_type, religion), 6 ints (is_pwd, is_voter, is_4ps, is_pregnant, is_solo_parent, purok_id)
$stmt->bind_param(
    'sssssssssiiiiii',
    $first_name,
    $middle_name,
    $last_name,
    $suffix,
    $gender,
    $birthdate,
    $civil_status,
    $blood_type,
    $religion,
    $is_pwd,
    $is_voter,
    $is_4ps,
    $is_pregnant,
    $is_solo_parent,
    $purok_id
);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    $errorMsg = 'Failed to add resident.';
    $errorMsg .= ' SQL Error: ' . $stmt->error . ' | Religion: ' . $religion;
    if ($stmt->errno === 1452) { // Foreign key constraint
        $errorMsg = 'Invalid Purok or related data. Please check your input.';
    } elseif ($stmt->errno === 1062) { // Duplicate entry
        $errorMsg = 'A resident with similar details already exists.';
    } else if (!empty($stmt->error)) {
        $errorMsg = $stmt->error;
    }
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}
$stmt->close();
$conn->close();
