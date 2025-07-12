<?php
require_once 'config.php';

echo "Database: " . DB_NAME . "\n";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        echo "Connection error: " . $conn->connect_error . "\n";
    } else {
        echo "Connected successfully\n";
        
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "Tables:\n";
            while($row = $result->fetch_row()) {
                echo "- " . $row[0] . "\n";
            }
        } else {
            echo "Error showing tables: " . $conn->error . "\n";
        }
        
        // Test if individuals table exists
        $result = $conn->query("SELECT COUNT(*) as count FROM individuals LIMIT 1");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Individuals table exists, record count >= " . $row['count'] . "\n";
        } else {
            echo "Individuals table error: " . $conn->error . "\n";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
