<?php
// fetch_individuals.php
require_once 'config.php';
header('Content-Type: application/json');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
$sql = "SELECT i.id, i.first_name, i.middle_name, i.last_name, i.suffix, i.gender, i.birthdate, i.civil_status, i.blood_type, i.religion, i.is_pwd, i.is_voter, i.is_4ps, i.is_pregnant, i.is_solo_parent, p.name AS purok FROM individuals i LEFT JOIN purok p ON i.purok_id = p.id";
$result = $conn->query($sql);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "id" => $row["id"],
            "first_name" => $row["first_name"],
            "middle_name" => $row["middle_name"],
            "last_name" => $row["last_name"],
            "suffix" => $row["suffix"],
            "gender" => $row["gender"],
            "birthdate" => $row["birthdate"],
            "civil_status" => $row["civil_status"],
            "blood_type" => $row["blood_type"],
            "religion" => $row["religion"],
            "is_pwd" => $row["is_pwd"],
            "is_voter" => $row["is_voter"],
            "is_4ps" => $row["is_4ps"],
            "is_pregnant" => $row["is_pregnant"],
            "is_solo_parent" => $row["is_solo_parent"],
            "purok" => $row["purok"]
        ];
    }
}
echo json_encode($data);
$conn->close();
