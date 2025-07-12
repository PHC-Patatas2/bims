<?php
// Test the forgot password system
require_once 'config.php';

echo "Testing Forgot Password System...\n\n";

// Check if password_resets table exists
try {
    $stmt = $pdo->query("DESCRIBE password_resets");
    echo "✓ Password resets table exists\n";
    
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $expected_columns = ['id', 'user_id', 'token', 'email', 'expires_at', 'used', 'created_at'];
    
    foreach ($expected_columns as $col) {
        if (in_array($col, $columns)) {
            echo "✓ Column '$col' exists\n";
        } else {
            echo "✗ Column '$col' missing\n";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Error checking password_resets table: " . $e->getMessage() . "\n";
}

echo "\n";

// Check if required files exist
$required_files = [
    'forgot_password.php' => 'Handles OTP generation and email sending',
    'verify_reset.php' => 'Handles OTP verification and password reset',
    'email_config.php' => 'Email configuration',
    'cleanup_password_resets.php' => 'Cleanup script for old tokens'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "✓ $file exists ($description)\n";
    } else {
        echo "✗ $file missing ($description)\n";
    }
}

echo "\n";

// Check email configuration
echo "Email Configuration:\n";
echo "SMTP Host: " . (defined('SMTP_HOST') ? SMTP_HOST : 'NOT CONFIGURED') . "\n";
echo "SMTP Port: " . (defined('SMTP_PORT') ? SMTP_PORT : 'NOT CONFIGURED') . "\n";
echo "From Email: " . (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NOT CONFIGURED') . "\n";

echo "\nForgot Password System Test Complete!\n";
?>
