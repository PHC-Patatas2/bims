<?php
// Test script to verify export column configuration
session_start();
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Check the individuals table structure
echo "<h2>Individuals Table Structure:</h2>";
$result = $conn->query("SHOW COLUMNS FROM individuals");
while ($row = $result->fetch_assoc()) {
    echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "<br>";
}

echo "<hr>";

// Test the export query
echo "<h2>Export Query Result (first 3 records):</h2>";
$sql = "SELECT 
    i.first_name,
    i.middle_name,
    i.last_name,
    i.suffix,
    i.gender,
    i.birthdate,
    i.civil_status,
    p.name as purok
FROM individuals i 
LEFT JOIN purok p ON i.purok_id = p.id
ORDER BY i.last_name, i.first_name 
LIMIT 3";

$result = $conn->query($sql);
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>First Name</th><th>Middle Name</th><th>Last Name</th><th>Suffix</th><th>Gender</th><th>Birthdate</th><th>Civil Status</th><th>Purok</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['first_name'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['middle_name'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['last_name'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['suffix'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['gender'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['birthdate'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['civil_status'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['purok'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Query failed: " . $conn->error;
}

$conn->close();
?>
