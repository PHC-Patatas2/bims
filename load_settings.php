<?php
// load_settings.php
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

try {
    // Fetch all settings from database
    $result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    
    $settings = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Default values if settings don't exist
    $defaults = [
        'system_title' => 'Resident Information and Certification Management System',
        'barangay_name' => 'Barangay Sample',
        'municipality' => 'Sample City',
        'province' => 'Sample Province',
        'barangay_address' => 'Sample Street, Sample City, Sample Province',
        'records_per_page' => '25',
        'session_timeout' => '30',
        'primary_color' => '#2563eb'
    ];
    
    // Merge with defaults
    $finalSettings = array_merge($defaults, $settings);
    
    echo json_encode([
        'success' => true,
        'settings' => $finalSettings
    ]);
    
} catch (Exception $e) {
    error_log('Load settings error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load settings']);
}

$conn->close();
?>
