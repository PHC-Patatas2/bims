<?php
require_once 'config.php';
require_once 'audit_logger.php';

echo "Creating test audit entry..." . PHP_EOL;
$result = logAuditTrail(1, 'Time Test Action', 'Testing recent activity time display at ' . date('Y-m-d H:i:s'));

if ($result) {
    echo "Test audit entry created successfully!" . PHP_EOL;
    
    // Check recent entries
    echo "Recent audit entries:" . PHP_EOL;
    $logs = getRecentAuditLogs(null, 3);
    foreach ($logs as $log) {
        echo "- " . $log['action'] . " at " . $log['timestamp'] . PHP_EOL;
    }
} else {
    echo "Failed to create test audit entry!" . PHP_EOL;
}
?>
