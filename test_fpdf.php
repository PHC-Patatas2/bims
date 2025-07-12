<?php
require_once 'config.php';

echo "Testing FPDF library...\n";

try {
    if (!file_exists(FPDF_PATH)) {
        throw new Exception('FPDF library not found at: ' . FPDF_PATH);
    }
    
    require_once FPDF_PATH;
    
    if (!class_exists('FPDF')) {
        throw new Exception('FPDF class not loaded');
    }
    
    echo "FPDF library loaded successfully\n";
    
    // Test creating a simple PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Test PDF');
    
    // Test output to string (to check if it works without saving)
    $output = $pdf->Output('S');
    if (strlen($output) > 0) {
        echo "PDF generation test successful (generated " . strlen($output) . " bytes)\n";
    } else {
        echo "PDF generation test failed - no output\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
