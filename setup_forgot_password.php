<?php
/**
 * Setup script for forgot password functionality
 * This script will create the necessary database tables and verify the setup
 */

require_once 'config.php';

echo "<h2>Setting up Forgot Password System for BIMS</h2>\n";

try {
    // Create password_resets table
    echo "<h3>Creating password_resets table...</h3>\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        email VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_expires (expires_at),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ password_resets table created successfully<br>\n";
    
    // Check if audit_trail table exists, if not create it
    echo "<h3>Checking audit_trail table...</h3>\n";
    
    $result = $pdo->query("SHOW TABLES LIKE 'audit_trail'");
    if ($result->rowCount() == 0) {
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
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✓ audit_trail table created successfully<br>\n";
    } else {
        echo "✓ audit_trail table already exists<br>\n";
    }
    
    // Check if users table exists and has required columns
    echo "<h3>Checking users table...</h3>\n";
    
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() == 0) {
        echo "Creating users table...<br>\n";
        
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            role ENUM('admin', 'staff') DEFAULT 'staff',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✓ users table created successfully<br>\n";
        
        // Insert a test admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, full_name, role) 
                VALUES ('admin', 'admin@barangay.gov.ph', ?, 'System Administrator', 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hashedPassword]);
        echo "✓ Default admin user created (username: admin, password: admin123)<br>\n";
    } else {
        echo "✓ users table already exists<br>\n";
        
        // Check if email column exists
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('email', $columns)) {
            echo "Adding email column to users table...<br>\n";
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE AFTER username");
            echo "✓ email column added to users table<br>\n";
        }
    }
    
    // Display table structures
    echo "<h3>Database Tables Summary:</h3>\n";
    
    echo "<h4>password_resets table structure:</h4>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    $result = $pdo->query("DESCRIBE password_resets");
    while ($row = $result->fetch()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h4>audit_trail table structure:</h4>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    $result = $pdo->query("DESCRIBE audit_trail");
    while ($row = $result->fetch()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h4>users table structure:</h4>\n";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    $result = $pdo->query("DESCRIBE users");
    while ($row = $result->fetch()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>✅ Setup completed successfully!</h3>\n";
    echo "<p>You can now use the forgot password functionality.</p>\n";
    echo "<p><strong>Note:</strong> Make sure to configure your email settings in email_config.php before testing the forgot password feature.</p>\n";
    
} catch (PDOException $e) {
    echo "<h3>❌ Error during setup:</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
