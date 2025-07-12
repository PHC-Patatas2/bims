<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$result = $conn->query('DESCRIBE individuals');
echo "Individuals table structure:\n";
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . "\n";
}
?>
