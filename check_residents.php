<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$result = $conn->query('SELECT id, first_name, last_name FROM individuals LIMIT 5');
echo "Available residents:\n";
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " - " . $row['first_name'] . " " . $row['last_name'] . "\n";
}
?>
