<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Retrieve and sanitize form data
$fields = [
    'last_name', 'first_name', 'middle_name', 'suffix', 'gender', 'birthdate', 'civil_status', 'blood_type',
    'citizenship', 'religion', 'contact_number', 'email',
    'birthplace_barangay', 'birthplace_municipality', 'birthplace_province',
    'current_purok', 'current_barangay', 'current_municipality', 'current_province'
];
$data = [];
foreach ($fields as $f) {
    $data[$f] = trim($_POST[$f] ?? '');
}
$is_voter = isset($_POST['is_voter']) ? 1 : 0;
$is_4ps_member = isset($_POST['is_4ps_member']) ? 1 : 0;
$is_pwd = isset($_POST['is_pwd']) ? 1 : 0;
$is_solo_parent = isset($_POST['is_solo_parent']) ? 1 : 0;
$is_pregnant = isset($_POST['is_pregnant']) ? 1 : 0;

// Basic validation
if (empty($data['last_name']) || empty($data['first_name']) || empty($data['gender']) || empty($data['birthdate']) || empty($data['current_purok']) || empty($data['current_barangay']) || empty($data['current_municipality']) || empty($data['current_province']) || empty($data['birthplace_barangay']) || empty($data['birthplace_municipality']) || empty($data['birthplace_province'])) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

$sql = "INSERT INTO individuals (
    last_name, first_name, middle_name, suffix, gender, birthdate, civil_status, blood_type, citizenship, religion, contact_number, email,
    birthplace_barangay, birthplace_municipality, birthplace_province,
    current_purok, current_barangay, current_municipality, current_province,
    is_voter, is_4ps_member, is_pwd, is_solo_parent, is_pregnant, created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param(
        "ssssssssssssssssssiiiii",
        $data['last_name'], $data['first_name'], $data['middle_name'], $data['suffix'], $data['gender'], $data['birthdate'], $data['civil_status'], $data['blood_type'],
        $data['citizenship'], $data['religion'], $data['contact_number'], $data['email'],
        $data['birthplace_barangay'], $data['birthplace_municipality'], $data['birthplace_province'],
        $data['current_purok'], $data['current_barangay'], $data['current_municipality'], $data['current_province'],
        $is_voter, $is_4ps_member, $is_pwd, $is_solo_parent, $is_pregnant
    );
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'New resident added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding resident: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
}
$conn->close();
