<?php
/**
 * Cleanup script to remove barangay_id certificate records from the database
 * Run this script once to clean up any existing barangay ID certificate requests
 */

require_once 'config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    echo "Starting barangay ID cleanup...\n";

    // Remove barangay_id certificate requests
    $stmt = $conn->prepare("DELETE FROM certificate_requests WHERE certificate_type = 'barangay_id'");
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        echo "Removed {$affected_rows} barangay ID certificate request(s) from database.\n";
    } else {
        echo "Error removing barangay ID certificate requests: " . $conn->error . "\n";
    }
    $stmt->close();

    // Check for any barangay ID related PDFs in certificates folder
    $certificates_dir = __DIR__ . '/certificates/';
    if (is_dir($certificates_dir)) {
        $files = glob($certificates_dir . 'CERT-BARANGAY_ID-*.pdf');
        $removed_files = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $removed_files++;
                echo "Removed PDF file: " . basename($file) . "\n";
            } else {
                echo "Failed to remove PDF file: " . basename($file) . "\n";
            }
        }
        
        if ($removed_files > 0) {
            echo "Removed {$removed_files} barangay ID PDF file(s).\n";
        } else {
            echo "No barangay ID PDF files found to remove.\n";
        }
    }

    echo "Barangay ID cleanup completed successfully!\n";
    echo "\nWhat was removed:\n";
    echo "- Barangay ID validation from generate_certificate.php\n";
    echo "- generateBarangayID() function from generate_certificate.php\n";
    echo "- Barangay ID case from certificate generation switch\n";
    echo "- Barangay ID button from individuals.php\n";
    echo "- Barangay ID card from certificate.php\n";
    echo "- Barangay ID option from issued_documents.php\n";
    echo "- Barangay ID JavaScript references\n";
    echo "- Database records for barangay_id certificate type\n";
    echo "- Generated barangay ID PDF files\n";

} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
