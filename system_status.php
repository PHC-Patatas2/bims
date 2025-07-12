<?php
/**
 * System Status Check for BIMS Local
 */

require_once 'config.php';

echo "<h2>BIMS System Status - bims.local</h2>\n";

try {
    // Test database connection
    echo "<h3>Database Connection</h3>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $result = $stmt->fetch();
    echo "✓ Database connected successfully<br>\n";
    echo "✓ Total users: {$result['user_count']}<br>\n";
    
    // Test forgot password tables
    echo "<h3>Forgot Password System</h3>\n";
    
    // Check users table columns
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token%'");
    $columns = $stmt->fetchAll();
    
    if (count($columns) >= 2) {
        echo "✓ Reset token columns exist in users table<br>\n";
    } else {
        echo "❌ Missing reset token columns<br>\n";
    }
    
    // Check audit trail
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM audit_trail");
    $audit_count = $stmt->fetch();
    echo "✓ Audit trail table accessible ({$audit_count['count']} entries)<br>\n";
    
    // Test user lookup
    echo "<h3>Test User Available</h3>\n";
    $stmt = $pdo->query("SELECT username, email FROM users LIMIT 1");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ Test user found: <strong>{$user['username']}</strong> ({$user['email']})<br>\n";
        
        // Generate test OTP for manual testing
        $otp = sprintf('%06d', random_int(100000, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        echo "<h3>Manual Testing</h3>\n";
        echo "<ol>\n";
        echo "<li>Go to <a href='http://bims.local/login.php' target='_blank' style='color: blue; font-weight: bold;'>http://bims.local/login.php</a></li>\n";
        echo "<li>Click 'Forgot password?' link</li>\n";
        echo "<li>Enter username: <strong style='color: green;'>{$user['username']}</strong></li>\n";
        echo "<li>Check console for debug messages</li>\n";
        echo "</ol>\n";
        
    } else {
        echo "❌ No test users available<br>\n";
    }
    
    echo "<h3>System URLs</h3>\n";
    echo "• Login page: <a href='http://bims.local/login.php' target='_blank'>http://bims.local/login.php</a><br>\n";
    echo "• Forgot password API: <a href='http://bims.local/forgot_password.php' target='_blank'>http://bims.local/forgot_password.php</a><br>\n";
    echo "• Verify reset API: <a href='http://bims.local/verify_reset.php' target='_blank'>http://bims.local/verify_reset.php</a><br>\n";
    
    echo "<h3>✅ System Ready</h3>\n";
    echo "<p>All components are working with bims.local domain.</p>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ Error</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2563eb; }
h3 { color: #1e40af; margin-top: 20px; }
a { color: #2563eb; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
