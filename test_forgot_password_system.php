<?php
require_once 'config.php';

echo "=== Forgot Password System Test ===\n\n";

// 1. Check if password_resets table exists and has correct structure
echo "1. Checking password_resets table...\n";
try {
    $stmt = $pdo->query("DESCRIBE password_resets");
    $columns = [];
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }
    
    $required_columns = ['id', 'user_id', 'token', 'email', 'expires_at', 'used', 'created_at'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (empty($missing_columns)) {
        echo "✓ Password resets table has all required columns\n";
    } else {
        echo "✗ Missing columns: " . implode(', ', $missing_columns) . "\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error checking password_resets table: " . $e->getMessage() . "\n";
}

// 2. Check users table structure
echo "\n2. Checking users table...\n";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $user_columns = [];
    while ($row = $stmt->fetch()) {
        $user_columns[] = $row['Field'];
    }
    
    $required_user_columns = ['id', 'email', 'first_name', 'last_name'];
    $missing_user_columns = array_diff($required_user_columns, $user_columns);
    
    if (empty($missing_user_columns)) {
        echo "✓ Users table has all required columns for password reset\n";
    } else {
        echo "✗ Missing user columns: " . implode(', ', $missing_user_columns) . "\n";
    }
    
    // Check if there are any users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "✓ Found $count users in database\n";
    
} catch (PDOException $e) {
    echo "✗ Error checking users table: " . $e->getMessage() . "\n";
}

// 3. Check required files
echo "\n3. Checking required files...\n";
$required_files = [
    'forgot_password.php' => 'Handles sending OTP emails',
    'verify_reset.php' => 'Handles OTP verification and password reset',
    'email_config.php' => 'Email SMTP configuration',
    'vendor/phpmailer/phpmailer/src/PHPMailer.php' => 'PHPMailer library'
];

foreach ($required_files as $file => $description) {
    if (file_exists($file)) {
        echo "✓ $file exists ($description)\n";
    } else {
        echo "✗ $file missing ($description)\n";
    }
}

// 4. Check email configuration
echo "\n4. Checking email configuration...\n";
if (file_exists('email_config.php')) {
    include_once 'email_config.php';
    
    $config_items = [
        'SMTP_HOST' => 'SMTP server',
        'SMTP_PORT' => 'SMTP port',
        'SMTP_USERNAME' => 'SMTP username',
        'SMTP_PASSWORD' => 'SMTP password',
        'SMTP_FROM_EMAIL' => 'From email address'
    ];
    
    foreach ($config_items as $constant => $description) {
        if (defined($constant) && !empty(constant($constant))) {
            echo "✓ $constant configured ($description)\n";
        } else {
            echo "✗ $constant not configured ($description)\n";
        }
    }
} else {
    echo "✗ email_config.php not found\n";
}

// 5. Test database operations
echo "\n5. Testing database operations...\n";
try {
    // Test insert
    $test_user_id = 1; // Assuming user ID 1 exists
    $test_token = '123456';
    $test_email = 'test@example.com';
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, email, expires_at) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$test_user_id, $test_token, $test_email, $expires_at]);
    
    if ($result) {
        echo "✓ Can insert password reset tokens\n";
        
        // Test select
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
        $stmt->execute([$test_token]);
        $token_data = $stmt->fetch();
        
        if ($token_data) {
            echo "✓ Can retrieve password reset tokens\n";
        } else {
            echo "✗ Cannot retrieve password reset tokens\n";
        }
        
        // Clean up test data
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$test_token]);
        echo "✓ Test data cleaned up\n";
        
    } else {
        echo "✗ Cannot insert password reset tokens\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database operation error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nIf all items show ✓, your forgot password system is ready to use!\n";
echo "If any items show ✗, please fix those issues first.\n";
?>
