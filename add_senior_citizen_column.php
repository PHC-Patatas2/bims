<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Adding is_senior_citizen column to individuals table...\n";

$sql = "ALTER TABLE individuals ADD COLUMN is_senior_citizen TINYINT(1) DEFAULT 0 AFTER is_solo_parent";
if ($conn->query($sql) === TRUE) {
    echo "Column 'is_senior_citizen' added successfully!\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$conn->close();
?>
