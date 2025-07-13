<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT i.id, i.first_name, i.middle_name, i.last_name, i.suffix, i.gender, i.birthdate, p.name AS purok_name FROM individuals i LEFT JOIN purok p ON i.purok_id = p.id LIMIT 3";
$result = $conn->query($sql);

echo "Testing purok data:\n";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Name: " . $row['first_name'] . " " . $row['last_name'] . ", Purok: " . ($row['purok_name'] ?: 'NULL') . "\n";
}

$conn->close();
?>
