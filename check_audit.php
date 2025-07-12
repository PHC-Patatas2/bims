<?php
require_once 'config.php';

try {
    $stmt = $pdo->query('DESCRIBE audit_trail');
    echo "Audit trail table structure:\n";
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
