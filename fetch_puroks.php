<?php
require_once 'config.php';
header('Content-Type: application/json');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
$sql = "SELECT id, name FROM purok ORDER BY name ASC";
$result = $conn->query($sql);
$puroks = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $puroks[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
}
echo json_encode($puroks);
$conn->close();
