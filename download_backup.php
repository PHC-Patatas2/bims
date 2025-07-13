<?php
// download_backup.php - Download backup files
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get the requested file
$filename = $_GET['file'] ?? '';
if (empty($filename)) {
    http_response_code(400);
    die('No file specified');
}

// Sanitize filename to prevent directory traversal
$filename = basename($filename);
$filepath = 'backups/' . $filename;

// Check if file exists and is a backup file
if (!file_exists($filepath) || !preg_match('/^bims_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
    http_response_code(404);
    die('File not found or invalid');
}

// Set headers for file download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output the file
readfile($filepath);
exit();
?>
