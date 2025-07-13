<?php
// backup_database.php - Database backup functionality
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'config.php';
require_once 'audit_logger.php';

// Set proper content type
header('Content-Type: application/json');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Create backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backupFileName = "bims_backup_{$timestamp}.sql";
    $backupPath = "backups/" . $backupFileName;
    
    // Create backups directory if it doesn't exist
    if (!file_exists('backups')) {
        mkdir('backups', 0755, true);
    }

    // Start building the SQL dump
    $sqlDump = "-- BIMS Database Backup\n";
    $sqlDump .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $sqlDump .= "-- Database: " . DB_NAME . "\n\n";
    $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sqlDump .= "SET AUTOCOMMIT = 0;\n";
    $sqlDump .= "START TRANSACTION;\n";
    $sqlDump .= "SET time_zone = \"+00:00\";\n\n";

    // Get all tables from the database
    $tablesResult = $conn->query("SHOW TABLES");
    if (!$tablesResult) {
        throw new Exception('Failed to get tables list');
    }

    while ($table = $tablesResult->fetch_array()) {
        $tableName = $table[0];
        
        // Get table structure
        $createTableResult = $conn->query("SHOW CREATE TABLE `$tableName`");
        if ($createTableResult) {
            $createTableRow = $createTableResult->fetch_array();
            $sqlDump .= "\n-- Table structure for table `$tableName`\n";
            $sqlDump .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $sqlDump .= $createTableRow[1] . ";\n\n";
        }

        // Get table data
        $dataResult = $conn->query("SELECT * FROM `$tableName`");
        if ($dataResult && $dataResult->num_rows > 0) {
            $sqlDump .= "-- Dumping data for table `$tableName`\n";
            
            while ($row = $dataResult->fetch_assoc()) {
                $sqlDump .= "INSERT INTO `$tableName` (";
                $columns = array_keys($row);
                $sqlDump .= "`" . implode("`, `", $columns) . "`";
                $sqlDump .= ") VALUES (";
                
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }
                $sqlDump .= implode(", ", $values);
                $sqlDump .= ");\n";
            }
            $sqlDump .= "\n";
        }
    }

    $sqlDump .= "COMMIT;\n";

    // Write the backup file
    if (file_put_contents($backupPath, $sqlDump) === false) {
        throw new Exception('Failed to write backup file');
    }

    // Log the backup activity
    logSystemChange($_SESSION['user_id'], 'database_backup', 'N/A', "Database backup created: $backupFileName");

    // Return success response with download info
    echo json_encode([
        'success' => true,
        'message' => 'Database backup created successfully',
        'filename' => $backupFileName,
        'filepath' => $backupPath,
        'size' => filesize($backupPath),
        'download_url' => 'download_backup.php?file=' . urlencode($backupFileName)
    ]);

} catch (Exception $e) {
    error_log('Database backup error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create backup: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
