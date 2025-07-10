<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Checking current gender values in database:\n";
$result = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name, gender FROM individuals ORDER BY id LIMIT 15");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $gender_display = $row['gender'] === null ? 'NULL' : "'{$row['gender']}'";
        echo "ID: {$row['id']}, Name: {$row['name']}, Gender: {$gender_display}\n";
    }
} else {
    echo "Error: " . $conn->error;
}

echo "\nChecking unique gender values:\n";
$result = $conn->query("SELECT DISTINCT gender, COUNT(*) as count FROM individuals GROUP BY gender");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $gender_display = $row['gender'] === null ? 'NULL' : "'{$row['gender']}'";
        echo "Gender: {$gender_display}, Count: {$row['count']}\n";
    }
}

$conn->close();
?>
