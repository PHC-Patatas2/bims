<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

echo "Recent Certificate Requests Status:\n";
echo "==================================\n";

$result = $conn->query('SELECT id, certificate_type, status, certificate_number, requested_at FROM certificate_requests ORDER BY requested_at DESC LIMIT 5');

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}, Type: {$row['certificate_type']}, Status: {$row['status']}, Number: {$row['certificate_number']}, Date: {$row['requested_at']}\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
