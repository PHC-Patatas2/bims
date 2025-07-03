<?php
// config.php
// Centralized configuration for BIMS
// Update these settings for your environment

// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'bims_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Path to FPDF library (relative to project root)
define('FPDF_PATH', __DIR__ . '/lib/fpdf/fpdf.php');

// Barangay logo path (relative to project root)
// define('BARANGAY_LOGO', 'barangay_logo.png'); // This can be removed if fetched from DB

// Create PDO connection
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log the error and show a friendly error page
    error_log("Database Connection Error: " . $e->getMessage());
    include __DIR__ . '/error_page.php';
    exit();
}
?>
