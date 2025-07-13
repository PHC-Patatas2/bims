<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Simulate the AJAX endpoint
header('Content-Type: application/json');

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
$certificates = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format certificate type for display
        $certificate_type_formatted = ucwords(str_replace('_', ' ', $row['certificate_type']));
        
        // Generate certificate number if not present
        $cert_number = $row['certificate_number'] ?: 'CERT-' . strtoupper($row['certificate_type']) . '-' . date('Y') . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
        
        $certificates[] = [
            'id' => $row['id'],
            'certificate_number' => $cert_number,
            'resident_name' => trim($row['resident_name']),
            'certificate_type' => $certificate_type_formatted,
            'purpose' => $row['purpose'] ?: 'N/A',
            'requested_at' => $row['requested_at'],
            'status' => $row['status']
        ];
    }
}

echo json_encode($certificates);
?>
