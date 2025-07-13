<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== CERTIFICATE_REQUESTS TABLE STRUCTURE ===\n";
$result = $conn->query("DESCRIBE certificate_requests");
if ($result) {
    printf("%-20s %-20s %-10s %-10s %-20s %-10s\n", 
        'Field', 'Type', 'Null', 'Key', 'Default', 'Extra');
    echo str_repeat('-', 100) . "\n";
    while ($row = $result->fetch_assoc()) {
        printf("%-20s %-20s %-10s %-10s %-20s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL', 
            $row['Extra']
        );
    }
} else {
    echo "Error describing table: " . $conn->error . "\n";
}

echo "\n=== SAMPLE DATA (LAST 5 RECORDS) ===\n";
$result = $conn->query("SELECT * FROM certificate_requests ORDER BY requested_at DESC LIMIT 5");
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            print_r($row);
            echo "---\n";
        }
    } else {
        echo "No records found in certificate_requests table.\n";
    }
} else {
    echo "Error selecting data: " . $conn->error . "\n";
}

echo "\n=== INDIVIDUALS TABLE STRUCTURE (for JOIN) ===\n";
$result = $conn->query("DESCRIBE individuals");
if ($result) {
    printf("%-20s %-20s %-10s\n", 'Field', 'Type', 'Null');
    echo str_repeat('-', 60) . "\n";
    while ($row = $result->fetch_assoc()) {
        printf("%-20s %-20s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null']
        );
    }
}

$conn->close();
?>
