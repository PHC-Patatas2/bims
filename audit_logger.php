<?php
// audit_logger.php - Centralized audit logging utility

require_once 'config.php';

/**
 * Log user actions to the audit trail
 * 
 * @param int|null $user_id User ID (null for system actions)
 * @param string $action Action description
 * @param string|array $details Additional details (can be string or array that will be JSON encoded)
 * @param string|null $ip_address IP address (auto-detected if not provided)
 * @param string|null $user_agent User agent (auto-detected if not provided)
 * @return bool Success status
 */
function logAuditTrail($user_id, $action, $details = '', $ip_address = null, $user_agent = null) {
    global $pdo;
    
    try {
        // Auto-detect IP address if not provided
        if ($ip_address === null) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            // Handle comma-separated IPs (in case of multiple proxies)
            if (strpos($ip_address, ',') !== false) {
                $ip_address = trim(explode(',', $ip_address)[0]);
            }
        }
        
        // Auto-detect user agent if not provided
        if ($user_agent === null) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        }
        
        // Convert details to JSON if it's an array
        if (is_array($details)) {
            $details = json_encode($details, JSON_UNESCAPED_UNICODE);
        }
        
        // Prepare and execute insert
        $stmt = $pdo->prepare("
            INSERT INTO audit_trail (user_id, action, details, ip_address, user_agent, timestamp, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        return $stmt->execute([$user_id, $action, $details, $ip_address, $user_agent]);
        
    } catch (Exception $e) {
        // Log the error but don't throw it to avoid breaking the main functionality
        error_log("Audit logging error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user login
 */
function logLogin($user_id, $username, $success = true, $additional_info = []) {
    $action = $success ? 'User Login' : 'Failed Login Attempt';
    
    $details = array_merge([
        'username' => $username,
        'success' => $success,
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => session_id()
    ], $additional_info);
    
    return logAuditTrail($user_id, $action, $details);
}

/**
 * Log user logout
 */
function logLogout($user_id, $username) {
    $details = [
        'username' => $username,
        'timestamp' => date('Y-m-d H:i:s'),
        'session_id' => session_id()
    ];
    
    return logAuditTrail($user_id, 'User Logout', $details);
}

/**
 * Log CRUD operations on residents/individuals
 */
function logResidentAction($user_id, $action, $resident_id, $resident_name, $changes = []) {
    $details = [
        'resident_id' => $resident_id,
        'resident_name' => $resident_name,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (!empty($changes)) {
        $details['changes'] = $changes;
    }
    
    return logAuditTrail($user_id, $action, $details);
}

/**
 * Log certificate generation
 */
function logCertificateGeneration($user_id, $cert_type, $resident_id, $resident_name, $additional_info = []) {
    $details = array_merge([
        'certificate_type' => $cert_type,
        'resident_id' => $resident_id,
        'resident_name' => $resident_name,
        'timestamp' => date('Y-m-d H:i:s')
    ], $additional_info);
    
    return logAuditTrail($user_id, 'Certificate Generated', $details);
}

/**
 * Log system configuration changes
 */
function logSystemChange($user_id, $setting_name, $old_value, $new_value) {
    $details = [
        'setting' => $setting_name,
        'old_value' => $old_value,
        'new_value' => $new_value,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    return logAuditTrail($user_id, 'System Setting Changed', $details);
}

/**
 * Log data export/report generation
 */
function logDataExport($user_id, $export_type, $criteria = [], $record_count = null) {
    $details = [
        'export_type' => $export_type,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (!empty($criteria)) {
        $details['criteria'] = $criteria;
    }
    
    if ($record_count !== null) {
        $details['record_count'] = $record_count;
    }
    
    return logAuditTrail($user_id, 'Data Export', $details);
}

/**
 * Log file uploads
 */
function logFileUpload($user_id, $filename, $file_type, $file_size, $purpose = '') {
    $details = [
        'filename' => $filename,
        'file_type' => $file_type,
        'file_size' => $file_size,
        'purpose' => $purpose,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    return logAuditTrail($user_id, 'File Upload', $details);
}

/**
 * Log security events
 */
function logSecurityEvent($user_id, $event_type, $details_array = []) {
    $details = array_merge([
        'event_type' => $event_type,
        'timestamp' => date('Y-m-d H:i:s')
    ], $details_array);
    
    return logAuditTrail($user_id, 'Security Event', $details);
}

/**
 * Get recent audit logs for a user
 */
function getRecentAuditLogs($user_id = null, $limit = 10) {
    global $pdo;
    
    try {
        if ($user_id !== null) {
            $stmt = $pdo->prepare("
                SELECT a.*, u.first_name, u.last_name, u.username 
                FROM audit_trail a 
                LEFT JOIN users u ON a.user_id = u.id 
                WHERE a.user_id = ?
                ORDER BY a.timestamp DESC 
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
        } else {
            $stmt = $pdo->prepare("
                SELECT a.*, u.first_name, u.last_name, u.username 
                FROM audit_trail a 
                LEFT JOIN users u ON a.user_id = u.id 
                ORDER BY a.timestamp DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
        }
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error fetching audit logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Clean up old audit logs (older than specified days)
 */
function cleanupOldAuditLogs($days = 365) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM audit_trail WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("Error cleaning up audit logs: " . $e->getMessage());
        return false;
    }
}
?>
