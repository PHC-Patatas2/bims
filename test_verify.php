<?php
// Simple test of verify_reset.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'verify_otp';
$_POST['credential'] = 'secretary';
$_POST['otp'] = '292540';

ob_start();
require 'verify_reset.php';
$output = ob_get_clean();

echo "Output: " . $output . "\n";
echo "Length: " . strlen($output) . "\n";
?>
