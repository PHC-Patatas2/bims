<?php
// delete_official.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'config.php';
require_once 'audit_logger.php'; // Include audit logging functions

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Official ID is required']);
    exit();
}

$id = (int)$input['id'];

try {
    // Check if official exists
    $check_stmt = $conn->prepare("SELECT id, first_name, last_name, position FROM barangay_officials WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Official not found']);
        exit();
    }
    
    $official = $result->fetch_assoc();
    $check_stmt->close();
    
    // Delete the official
    $delete_stmt = $conn->prepare("DELETE FROM barangay_officials WHERE id = ?");
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        // Log the official deletion action
        $official_data = [
            'official_id' => $id,
            'name' => $official['first_name'] . ' ' . $official['last_name'],
            'position' => $official['position']
        ];
        logAuditTrail($_SESSION['user_id'], 'Official Deleted', $official_data);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Official deleted successfully',
            'deleted_official' => [
                'id' => $id,
                'name' => $official['first_name'] . ' ' . $official['last_name'],
                'position' => $official['position']
            ]
        ]);
    } else {
        throw new Exception('Failed to delete official');
    }
    
    $delete_stmt->close();
} catch (Exception $e) {
    error_log('Delete official error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete official']);
}

$conn->close();
?>
