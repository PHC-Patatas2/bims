<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized.']);
    exit();
}
require_once 'config.php';
require_once 'audit_logger.php'; // Include audit logging functions
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
    exit();
}
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid resident ID.']);
    exit();
}
$id = intval($_POST['id']);
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}
// Get resident information before deleting for audit logging
$resident_stmt = $conn->prepare('SELECT first_name, middle_name, last_name, suffix FROM individuals WHERE id = ?');
if (!$resident_stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query preparation error.']);
    exit();
}
$resident_stmt->bind_param('i', $id);
$resident_stmt->execute();
$resident_result = $resident_stmt->get_result();
$resident = $resident_result->fetch_assoc();
$resident_stmt->close();

if (!$resident) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Resident not found.']);
    exit();
}

$resident_name = trim($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name'] . ' ' . $resident['suffix']);

// You may want to add more related deletions here (e.g. certificates, logs, etc.)
$stmt = $conn->prepare('DELETE FROM individuals WHERE id = ?');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query error.']);
    exit();
}
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Log the resident deletion action
        logResidentAction($_SESSION['user_id'], 'Resident Deleted', $id, $resident_name);
        
        echo json_encode(['success' => true, 'message' => 'Resident deleted successfully.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Resident not found.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete resident.']);
}
$stmt->close();
$conn->close();
