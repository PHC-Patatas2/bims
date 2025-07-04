<?php
// fetch_individual_detail.php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing resident ID."]);
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}

$id = intval($_GET['id']);
$sql = "SELECT first_name, middle_name, last_name, suffix, gender, birthdate, civil_status, blood_type, religion, is_pwd, is_voter, is_4ps, is_pregnant, created_at, is_solo_parent, purok_id FROM individuals WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Resident not found."]);
}
$stmt->close();
$conn->close();
