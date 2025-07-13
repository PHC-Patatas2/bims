<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

try {
    // Get total residents
    $total_residents_query = "SELECT COUNT(*) as count FROM individuals";
    $total_residents_result = $conn->query($total_residents_query);
    $total_residents = $total_residents_result ? $total_residents_result->fetch_assoc()['count'] : 0;

    // Get total registered voters
    $total_voters_query = "SELECT COUNT(*) as count FROM individuals WHERE is_voter = 1";
    $total_voters_result = $conn->query($total_voters_query);
    $total_voters = $total_voters_result ? $total_voters_result->fetch_assoc()['count'] : 0;

    // Get total senior citizens (60+ years old)
    $total_seniors_query = "SELECT COUNT(*) as count FROM individuals WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60";
    $total_seniors_result = $conn->query($total_seniors_query);
    $total_seniors = $total_seniors_result ? $total_seniors_result->fetch_assoc()['count'] : 0;

    // Get total certificates issued
    $total_certificates_query = "SELECT COUNT(*) as count FROM certificate_requests WHERE status = 'Issued'";
    $total_certificates_result = $conn->query($total_certificates_query);
    $total_certificates = $total_certificates_result ? $total_certificates_result->fetch_assoc()['count'] : 0;

    // Return the stats as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'total_residents' => (int)$total_residents,
        'total_voters' => (int)$total_voters,
        'total_seniors' => (int)$total_seniors,
        'total_certificates' => (int)$total_certificates
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch statistics']);
}

$conn->close();
?>
