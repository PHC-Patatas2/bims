<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Update all existing records with "Pending" status to "Issued" 
// since if they exist in the system, they were successfully generated
$update_query = "UPDATE certificate_requests SET status = 'Issued', processed_at = NOW() WHERE status = 'Pending' OR status IS NULL";
$result = $conn->query($update_query);

if ($result) {
    $affected_rows = $conn->affected_rows;
    echo "Successfully updated $affected_rows certificate records from 'Pending' to 'Issued'.\n";
} else {
    echo "Error updating records: " . $conn->error . "\n";
}

// Also add certificate numbers for records that don't have them
$number_query = "UPDATE certificate_requests SET certificate_number = CONCAT('CERT-', UPPER(certificate_type), '-', YEAR(requested_at), '-', LPAD(id, 4, '0')) WHERE certificate_number IS NULL OR certificate_number = ''";
$number_result = $conn->query($number_query);

if ($number_result) {
    $affected_rows = $conn->affected_rows;
    echo "Successfully added certificate numbers to $affected_rows records.\n";
} else {
    echo "Error adding certificate numbers: " . $conn->error . "\n";
}

$conn->close();
echo "Database update completed!\n";
?>
