<?php
// Test certificate generation
session_start();
$_SESSION['user_id'] = 1; // Mock login

$_POST['certificate_type'] = 'clearance';
$_POST['resident_id'] = '219'; // Use valid resident ID
$_POST['purpose'] = 'Test certificate generation';

// Buffer output to catch any errors
ob_start();
try {
    include 'generate_certificate.php';
    $output = ob_get_contents();
    ob_end_clean();
    echo "Certificate generation output:\n";
    echo $output;
} catch (Exception $e) {
    ob_end_clean();
    echo "Error: " . $e->getMessage() . "\n";
}
?>
