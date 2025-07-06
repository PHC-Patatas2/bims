<?php
// fetch_religions.php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}
$sql = "SHOW COLUMNS FROM individuals LIKE 'religion'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $type = $row['Type'];
    if (preg_match("/enum\((.*)\)/", $type, $matches)) {
        $enum = $matches[1];
        $values = array();
        foreach (explode(",", $enum) as $value) {
            $v = trim($value, "'\"");
            $values[] = $v;
        }
        header('Content-Type: application/json');
        echo json_encode($values);
        exit();
    }
}
http_response_code(500);
echo json_encode(['error' => 'Could not fetch religion options']);
