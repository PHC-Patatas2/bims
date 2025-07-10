<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Individuals with empty or NULL gender:\n";
$result = $conn->query("SELECT id, first_name, last_name, gender FROM individuals WHERE gender IS NULL OR gender = '' ORDER BY id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $gender_display = $row['gender'] === null ? 'NULL' : ($row['gender'] === '' ? 'EMPTY STRING' : "'{$row['gender']}'");
        echo "ID: {$row['id']}, Name: {$row['first_name']} {$row['last_name']}, Gender: {$gender_display}\n";
    }
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
