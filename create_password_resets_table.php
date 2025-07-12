<?php
require_once 'config.php';

echo "Creating password_resets table...\n";

try {
    // Create password_resets table
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(6) NOT NULL,
        email VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_expires (expires_at),
        INDEX idx_user_id (user_id)
    )";
    
    $pdo->exec($sql);
    echo "✓ Password resets table created successfully\n";
    
    // Check if the table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table exists in database\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE password_resets");
        echo "\nTable structure:\n";
        while ($row = $stmt->fetch()) {
            echo "- {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
        }
    }
    
    // Clean up any existing expired tokens
    $cleanup_sql = "DELETE FROM password_resets WHERE expires_at < NOW() OR created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmt = $pdo->prepare($cleanup_sql);
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "\n✓ Cleaned up $count old/expired tokens\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // If foreign key constraint fails, let's check the users table structure
    if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
        echo "\nChecking users table structure...\n";
        try {
            $stmt = $pdo->query("DESCRIBE users");
            echo "Users table columns:\n";
            while ($row = $stmt->fetch()) {
                echo "- {$row['Field']}: {$row['Type']}\n";
            }
        } catch (Exception $e2) {
            echo "Could not check users table: " . $e2->getMessage() . "\n";
        }
    }
}

echo "\nSetup complete!\n";
?>
