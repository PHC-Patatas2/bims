<?php
require_once 'config.php';

echo "Current audit_trail table structure:\n";
$result = $pdo->query('DESCRIBE audit_trail');
foreach ($result as $row) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . $row['Null'] . ' | ' . $row['Key'] . ' | ' . $row['Default'] . ' | ' . $row['Extra'] . "\n";
}

// Check if user_agent column exists
$check = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'audit_trail' AND COLUMN_NAME = 'user_agent'");
if ($check->rowCount() == 0) {
    echo "\nuser_agent column is missing. Adding it...\n";
    try {
        $pdo->exec("ALTER TABLE audit_trail ADD COLUMN user_agent TEXT DEFAULT NULL AFTER ip_address");
        echo "user_agent column added successfully!\n";
    } catch (Exception $e) {
        echo "Error adding user_agent column: " . $e->getMessage() . "\n";
    }
} else {
    echo "\nuser_agent column already exists.\n";
}
?>
