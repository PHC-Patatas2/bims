<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: certificate.php');
    exit();
}

$certificate_id = $_GET['id'];
$filepath = __DIR__ . '/certificates/' . $certificate_id . '.pdf';

if (!file_exists($filepath)) {
    header('Location: certificate.php?error=Certificate not found');
    exit();
}

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $certificate_id . '.pdf"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
header('Pragma: public');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Output the file
readfile($filepath);
exit();
?>
