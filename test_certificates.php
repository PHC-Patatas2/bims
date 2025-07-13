<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if table exists and has data
$result = $conn->query("SELECT COUNT(*) as count FROM certificate_requests");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Certificate requests count: " . $row['count'] . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Test the exact query used in the AJAX endpoint
$sql = "SELECT 
            cr.id,
            cr.certificate_type,
            cr.purpose,
            cr.requested_at,
            cr.status,
            cr.certificate_number,
            CONCAT(i.first_name, ' ', 
                   COALESCE(i.middle_name, ''), ' ', 
                   i.last_name, ' ', 
                   COALESCE(i.suffix, '')) as resident_name
        FROM certificate_requests cr 
        LEFT JOIN individuals i ON cr.individual_id = i.id 
        ORDER BY cr.requested_at DESC 
        LIMIT 5";

$result = $conn->query($sql);
if ($result) {
    echo "Query executed successfully. Rows returned: " . $result->num_rows . "\n";
    if ($result->num_rows > 0) {
        echo "Sample data:\n";
        while ($row = $result->fetch_assoc()) {
            echo "ID: " . $row['id'] . ", Type: " . $row['certificate_type'] . ", Resident: " . $row['resident_name'] . "\n";
        }
    }
} else {
    echo "Query error: " . $conn->error . "\n";
}
?>
