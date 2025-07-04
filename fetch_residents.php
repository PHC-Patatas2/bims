<?php
require_once 'config.php';
header('Content-Type: application/json');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}
$sql = "SELECT id, first_name, middle_name, last_name, suffix, gender, birthdate, civil_status, blood_type, citizenship, religion, is_pwd, is_voter, is_4ps, is_pregnant, is_newborn, created_at, is_solo_parent, current_purok, current_barangay, current_municipality, current_province, purok_id FROM individuals";
$result = $conn->query($sql);
$residents = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Cast boolean-like fields to int
        foreach (["is_pwd","is_voter","is_4ps","is_pregnant","is_newborn","is_solo_parent"] as $boolField) {
            $row[$boolField] = (int)$row[$boolField];
        }
        $residents[] = $row;
    }
}
$conn->close();
echo json_encode($residents);
