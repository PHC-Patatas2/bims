<?php
require_once 'config.php';

echo "<h2>Database Structure Analysis</h2>\n";

try {
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>\n";
    $stmt = $pdo->query("DESCRIBE users");
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    while ($row = $stmt->fetch()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
        }
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Show sample users
    echo "<h3>Sample Users:</h3>\n";
    $stmt = $pdo->query("SELECT * FROM users LIMIT 3");
    $users = $stmt->fetchAll();
    if (!empty($users)) {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr>";
        foreach (array_keys($users[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr>\n";
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No users found.</p>\n";
    }
    
    // Check audit_trail table structure
    echo "<h3>Audit Trail Table Structure:</h3>\n";
    try {
        $stmt = $pdo->query("DESCRIBE audit_trail");
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Check if created_at column exists
        $columns = $pdo->query("SHOW COLUMNS FROM audit_trail")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('created_at', $columns)) {
            echo "<p style='color: orange;'>⚠️ Missing created_at column in audit_trail table</p>\n";
            echo "<p>Fixing audit_trail table...</p>\n";
            $pdo->exec("ALTER TABLE audit_trail ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
            echo "<p style='color: green;'>✓ Added created_at column to audit_trail table</p>\n";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error with audit_trail: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    // Check password_resets table structure
    echo "<h3>Password Resets Table Structure:</h3>\n";
    try {
        $stmt = $pdo->query("DESCRIBE password_resets");
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error with password_resets: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
