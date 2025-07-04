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
$sql = "SELECT first_name, middle_name, last_name, suffix, gender, birthdate FROM individuals";
$result = $conn->query($sql);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "first_name" => $row["first_name"],
            "middle_name" => $row["middle_name"],
            "last_name" => $row["last_name"],
            "suffix" => $row["suffix"],
            "gender" => $row["gender"],
            "birthdate" => $row["birthdate"],
        ];
    }
}
echo json_encode($data);
$conn->close();
