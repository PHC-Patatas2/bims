<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Get table structure
$result = $conn->query("DESCRIBE individuals");
echo "Individuals table structure:\n";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

$conn->close();
?>
