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
$sql = "SELECT i.*, p.name AS purok_name, i.religion AS religion_name
        FROM individuals i 
        LEFT JOIN purok p ON i.purok_id = p.id 
        WHERE i.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    // Always include the id field in the response
    $row['id'] = $id;
    
    // Format some values for display
    $row['is_voter'] = $row['is_voter'] ? 1 : 0;
    $row['is_pwd'] = $row['is_pwd'] ? 1 : 0;
    $row['is_4ps'] = $row['is_4ps'] ? 1 : 0;
    $row['is_solo_parent'] = $row['is_solo_parent'] ? 1 : 0;
    $row['is_pregnant'] = $row['is_pregnant'] ? 1 : 0;
    $row['is_senior_citizen'] = isset($row['is_senior_citizen']) ? ($row['is_senior_citizen'] ? 1 : 0) : 0;
    
    // Calculate age if birthdate is available
    if (!empty($row['birthdate'])) {
        $birthDate = new DateTime($row['birthdate']);
        $today = new DateTime('today');
        $row['age'] = $birthDate->diff($today)->y;
        $row['date_of_birth'] = $row['birthdate']; // Alias for consistency
    }
    
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Resident not found."]);
}
$stmt->close();
$conn->close();
