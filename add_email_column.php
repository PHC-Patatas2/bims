<?php
// add_email_column.php
// This script adds an email column to the individuals table if it doesn't exist

require_once 'config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }

    // Check if email column exists
    $result = $conn->query("SHOW COLUMNS FROM individuals LIKE 'email'");
    
    if ($result->num_rows == 0) {
        // Email column doesn't exist, add it
        $sql = "ALTER TABLE individuals ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER contact_no";
        
        if ($conn->query($sql) === TRUE) {
            echo "Email column added successfully to individuals table.\n";
        } else {
            echo "Error adding email column: " . $conn->error . "\n";
        }
    } else {
        echo "Email column already exists in individuals table.\n";
    }

    $conn->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
