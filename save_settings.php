<?php
// save_settings.php
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

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Define settings mapping - only general settings
    $settingsMap = [
        'system_title' => ['key' => 'system_title', 'description' => 'System title displayed in header'],
        'barangay_name' => ['key' => 'barangay_name', 'description' => 'Name of the barangay'],
        'municipality' => ['key' => 'municipality', 'description' => 'Municipality or city name'],
        'province' => ['key' => 'province', 'description' => 'Province name'],
        'address' => ['key' => 'barangay_address', 'description' => 'Complete barangay address'],
        'barangay_logo_path' => ['key' => 'barangay_logo_path', 'description' => 'Path to barangay logo']
    ];
    
    // Prepare statement for inserting/updating settings
    $stmt = $conn->prepare("
        INSERT INTO system_settings (setting_key, setting_value, description) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value), 
        description = VALUES(description),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $updatedCount = 0;
    
    foreach ($input as $fieldName => $value) {
        if (isset($settingsMap[$fieldName])) {
            $settingKey = $settingsMap[$fieldName]['key'];
            $description = $settingsMap[$fieldName]['description'];
            
            // Convert boolean values to string
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            
            $stmt->bind_param("sss", $settingKey, $value, $description);
            
            if ($stmt->execute()) {
                $updatedCount++;
            } else {
                throw new Exception("Failed to update setting: " . $settingKey);
            }
        }
    }
    
    $stmt->close();
    $conn->commit();
    
    // Log the settings changes
    foreach ($input as $fieldName => $value) {
        if (isset($settingsMap[$fieldName])) {
            $settingKey = $settingsMap[$fieldName]['key'];
            // Note: We don't have the old value easily accessible here, 
            // but we log that the setting was changed
            logSystemChange($_SESSION['user_id'], $settingKey, 'Previous Value', $value);
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Settings saved successfully ($updatedCount settings updated)",
        'updated_count' => $updatedCount
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log('Save settings error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save settings: ' . $e->getMessage()]);
}

$conn->close();
?>
