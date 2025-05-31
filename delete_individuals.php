<?php
session_start();
require_once 'config.php';

// Check if user is logged in (basic security)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['ids']) || !is_array($input['ids'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input. Array of IDs is required.']);
    exit();
}

$idsToDelete = $input['ids'];

if (empty($idsToDelete)) {
    echo json_encode(['success' => false, 'message' => 'No IDs provided for deletion.']);
    exit();
}

// Sanitize IDs to ensure they are integers
$sanitizedIds = array_map('intval', $idsToDelete);
$sanitizedIds = array_filter($sanitizedIds, function($id) {
    return $id > 0; // Ensure IDs are positive integers
});

if (empty($sanitizedIds)) {
    echo json_encode(['success' => false, 'message' => 'No valid IDs provided for deletion.']);
    exit();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$conn->begin_transaction();

try {
    $placeholders = implode(',', array_fill(0, count($sanitizedIds), '?'));
    $types = str_repeat('i', count($sanitizedIds));
    
    $stmt = $conn->prepare("DELETE FROM individuals WHERE id IN ($placeholders)");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$sanitizedIds);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete records: " . $stmt->error);
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    $conn->commit();
    
    if ($affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => "Successfully deleted {$affected_rows} resident(s)."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No residents found with the provided IDs or no changes made.']);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Deletion Error: " . $e->getMessage()); // Log the detailed error
    echo json_encode(['success' => false, 'message' => 'An error occurred during deletion. ' . $e->getMessage()]);
}

$conn->close();
?>
