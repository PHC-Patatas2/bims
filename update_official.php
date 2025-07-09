<?php
// update_official.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
$required_fields = ['id', 'first_name', 'last_name', 'position'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit();
    }
}

// Sanitize inputs
$id = (int)$input['id'];
$first_name = trim($input['first_name']);
$middle_initial = isset($input['middle_initial']) ? trim($input['middle_initial']) : null;
$last_name = trim($input['last_name']);
$suffix = isset($input['suffix']) ? trim($input['suffix']) : null;
$position = trim($input['position']);

// Validate field lengths
if (strlen($first_name) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'First name too long (max 50 characters)']);
    exit();
}

if ($middle_initial && strlen($middle_initial) > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Middle initial too long (max 5 characters)']);
    exit();
}

if (strlen($last_name) > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Last name too long (max 50 characters)']);
    exit();
}

if ($suffix && strlen($suffix) > 20) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Suffix too long (max 20 characters)']);
    exit();
}

if (strlen($position) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Position too long (max 100 characters)']);
    exit();
}

try {
    // Check if official exists
    $check_stmt = $conn->prepare("SELECT id FROM barangay_officials WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Official not found']);
        exit();
    }
    $check_stmt->close();
    
    // Prepare and execute update statement
    $stmt = $conn->prepare("UPDATE barangay_officials SET first_name = ?, middle_initial = ?, last_name = ?, suffix = ?, position = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $first_name, $middle_initial, $last_name, $suffix, $position, $id);
    
    if ($stmt->execute()) {
        // Build full name for response
        $full_name = $first_name;
        if ($middle_initial) {
            $full_name .= ' ' . $middle_initial;
            if (substr($middle_initial, -1) !== '.') {
                $full_name .= '.';
            }
        }
        $full_name .= ' ' . $last_name;
        if ($suffix) {
            $full_name .= ', ' . $suffix;
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Official updated successfully',
            'official' => [
                'id' => $id,
                'name' => $full_name,
                'position' => $position
            ]
        ]);
    } else {
        throw new Exception('Failed to update official');
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log('Update official error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update official']);
}

$conn->close();
?>
