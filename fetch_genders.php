<?php
header('Content-Type: application/json');
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}
$result = $conn->query("SHOW COLUMNS FROM individuals LIKE 'gender'");
if ($result && $row = $result->fetch_assoc()) {
    $type = $row['Type']; // e.g. enum('Male','Female')
    if (preg_match("/enum\((.*)\)/", $type, $matches)) {
        $enums = explode(",", $matches[1]);
        $genders = array_map(function($v) {
            return trim($v, "'\"");
        }, $enums);
        echo json_encode($genders);
        exit();
    }
}
echo json_encode([]);
