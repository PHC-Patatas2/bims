<?php
require_once 'config.php';

echo "=== Audit Trail Table Structure ===\n";
try {
    $stmt = $pdo->query('DESCRIBE audit_trail');
    echo "Current columns:\n";
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
