<?php
// Simple test script for certificate generation
session_start();

// Simulate logged in user
$_SESSION['user_id'] = 1;

// Simulate POST request for Barangay Clearance
$_POST['certificate_type'] = 'clearance';
$_POST['resident_id'] = 4; // Dennis Dacles
$_POST['purpose'] = 'Employment';
$_POST['date_issued'] = date('Y-m-d');

// Additional fields for verification
$_POST['id_verification'] = 'verified';
$_POST['purpose_category'] = 'employment';
$_POST['secretary_verification'] = 'on';
$_POST['kagawad_verification'] = 'on';
$_POST['punong_barangay_verification'] = 'on';

// Include the certificate generation script
include 'generate_certificate.php';
?>
