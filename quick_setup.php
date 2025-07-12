<?php
/**
 * Quick Database Setup for Forgot Password System
 * This will create the missing password_resets table and verify everything works
 */

require_once 'config.php';

echo "<h2>Setting up Database for Forgot Password</h2>\n";

try {
    // 1. Create password_resets table if it doesn't exist
    echo "<h3>1. Creating password_resets table...</h3>\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(6) NOT NULL,
        email VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_user_id (user_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "✓ password_resets table created/verified<br>\n";
    
    // 2. Check if audit_trail table exists, if not create it
    echo "<h3>2. Checking audit_trail table...</h3>\n";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'audit_trail'")->fetchAll();
    if (empty($tables)) {
        echo "Creating audit_trail table...<br>\n";
        
        $sql = "CREATE TABLE audit_trail (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $pdo->exec($sql);
        echo "✓ audit_trail table created<br>\n";
    } else {
        echo "✓ audit_trail table already exists<br>\n";
    }
    
    // 3. Verify users table structure
    echo "<h3>3. Checking users table...</h3>\n";
    
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    echo "Users table columns: " . implode(', ', $columns) . "<br>\n";
    
    // Check for required columns
    $required_columns = ['id', 'username', 'email', 'password'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        echo "❌ Missing required columns: " . implode(', ', $missing_columns) . "<br>\n";
        
        // Add missing columns
        foreach ($missing_columns as $column) {
            switch ($column) {
                case 'email':
                    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE AFTER username");
                    echo "✓ Added email column<br>\n";
                    break;
            }
        }
    } else {
        echo "✓ All required columns exist<br>\n";
    }
    
    // 4. Test a sample user (optional)
    echo "<h3>4. Checking for test users...</h3>\n";
    
    $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total users in system: $user_count<br>\n";
    
    if ($user_count == 0) {
        echo "No users found. Would you like to create a test user?<br>\n";
        echo "<a href='#' onclick=\"createTestUser()\">Create Test User</a><br>\n";
    }
    
    // 5. Show recent activity
    echo "<h3>5. System Status</h3>\n";
    
    // Show recent password resets
    $reset_count = $pdo->query("SELECT COUNT(*) FROM password_resets")->fetchColumn();
    echo "Password reset attempts: $reset_count<br>\n";
    
    // Show recent audit entries
    if (in_array('audit_trail', $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
        $audit_count = $pdo->query("SELECT COUNT(*) FROM audit_trail")->fetchColumn();
        echo "Audit trail entries: $audit_count<br>\n";
    }
    
    echo "<h3>✅ Setup Complete!</h3>\n";
    echo "<p>The forgot password system is now ready to use.</p>\n";
    
    echo "<h4>Testing the System:</h4>\n";
    echo "<ol>\n";
    echo "<li>Go to <a href='login.php'>login.php</a></li>\n";
    echo "<li>Click 'Forgot password?'</li>\n";
    echo "<li>Enter a valid username or email</li>\n";
    echo "<li>Check the password_resets table for the OTP</li>\n";
    echo "</ol>\n";
    
    echo "<h4>Email Configuration:</h4>\n";
    echo "<p>Make sure to update <code>email_config.php</code> with your SMTP settings before testing email functionality.</p>\n";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>

<script>
function createTestUser() {
    if (confirm('Create a test user (username: test, email: test@example.com, password: test123)?')) {
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=create_test_user'
        })
        .then(response => response.text())
        .then(data => {
            alert('Test user created!');
            location.reload();
        })
        .catch(error => {
            alert('Error creating test user: ' + error);
        });
    }
}
</script>

<?php
// Handle test user creation
if ($_POST['action'] ?? '' === 'create_test_user') {
    try {
        $test_password = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['test', 'test@example.com', $test_password, 'Test', 'User']);
        echo "Test user created successfully!";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    exit;
}
?>
