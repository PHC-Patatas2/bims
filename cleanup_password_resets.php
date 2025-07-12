<?php
// cleanup_password_resets.php
// This script should be run periodically (e.g., via cron job) to clean up old password reset tokens

require_once 'config.php';

try {
    // Clean up expired or old tokens
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $deleted = $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "Cleanup completed. Removed $count expired password reset tokens.\n";
    
} catch (PDOException $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
}
?>
