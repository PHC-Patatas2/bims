<?php
require_once 'config.php';
header('Content-Type: application/json');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}

// Use the certificate_requests table to show certificate history
$sql = "SELECT 
    cr.id,
    cr.individual_id,
    cr.certificate_type,
    cr.requested_at,
    CONCAT('CERT-', UPPER(cr.certificate_type), '-', YEAR(cr.requested_at), '-', LPAD(cr.id, 4, '0')) as certificate_id,
    CONCAT(i.first_name, ' ', COALESCE(i.middle_name, ''), ' ', i.last_name, ' ', COALESCE(i.suffix, '')) as resident_name,
    i.first_name,
    i.last_name,
    TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) as age,
    i.gender,
    i.civil_status,
    p.name as purok_name
FROM certificate_requests cr
LEFT JOIN individuals i ON cr.individual_id = i.id
LEFT JOIN purok p ON i.purok_id = p.id
ORDER BY cr.requested_at DESC";

$result = $conn->query($sql);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format certificate type for display
        $certificate_type_display = str_replace('_', ' ', $row['certificate_type']);
        $certificate_type_display = ucwords($certificate_type_display);
        
        $data[] = [
            "id" => $row["id"],
            "certificate_id" => $row["certificate_id"],
            "certificate_number" => $row["certificate_id"], // alias for compatibility
            "document_type" => $certificate_type_display,
            "certificate_type" => $row["certificate_type"],
            "resident_name" => trim($row["resident_name"]),
            "individual_id" => $row["individual_id"],
            "issued_by" => "System", // You can modify this if you track who processed the request
            "issued_date" => $row["requested_at"],
            "purpose" => "Certificate Request",
            "status" => "Issued"
        ];
    }
}

echo json_encode($data);
$conn->close();
?>
