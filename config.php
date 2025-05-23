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
define('BARANGAY_LOGO', 'barangay_logo.png');
