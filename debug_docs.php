<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

echo "Sample of issued documents data:\n";
echo "================================\n";

$sql = "SELECT 
            cr.id,
            cr.certificate_type,
            cr.purpose,
            cr.requested_at as issued_date,
            cr.status,
            cr.certificate_number,
            CONCAT(i.first_name, ' ', 
                   COALESCE(i.middle_name, ''), ' ', 
                   i.last_name, ' ', 
                   COALESCE(i.suffix, '')) as resident_name
        FROM certificate_requests cr 
        LEFT JOIN individuals i ON cr.individual_id = i.id 
        WHERE cr.status = 'Issued'
        ORDER BY cr.requested_at DESC
        LIMIT 5";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $certificate_type_formatted = ucwords(str_replace('_', ' ', $row['certificate_type']));
        echo "ID: {$row['id']}\n";
        echo "Type: {$row['certificate_type']} -> {$certificate_type_formatted}\n";
        echo "Date: {$row['issued_date']}\n";
        echo "Today: " . date('Y-m-d') . "\n";
        echo "Match: " . (date('Y-m-d', strtotime($row['issued_date'])) === date('Y-m-d') ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
