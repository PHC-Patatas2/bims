<?php
// export_individuals_pdf.php
require_once 'config.php';
if (!file_exists(FPDF_PATH)) {
    die('FPDF library not found. Please download FPDF from http://www.fpdf.org/ and place it in lib/fpdf/.');}
require_once FPDF_PATH;
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$result = $conn->query('SELECT i.*, p.name as purok_name FROM individuals i LEFT JOIN puroks p ON i.purok_id = p.id ORDER BY i.last_name, i.first_name');
$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'Barangay Information Management System - Individuals', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(40, 8, 'Name', 1);
$pdf->Cell(25, 8, 'Birthday', 1);
$pdf->Cell(12, 8, 'Age', 1);
$pdf->Cell(15, 8, 'Gender', 1);
$pdf->Cell(25, 8, 'Purok', 1);
$pdf->Cell(12, 8, 'PWD', 1);
$pdf->Cell(12, 8, '4Ps', 1);
$pdf->Cell(30, 8, 'Contact', 1);
$pdf->Cell(40, 8, 'Address', 1);
$pdf->Ln();
$pdf->SetFont('Arial', '', 9);
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(40, 8, $row['last_name'] . ', ' . $row['first_name'], 1);
    $pdf->Cell(25, 8, $row['birthday'], 1);
    $pdf->Cell(12, 8, $row['age'], 1);
    $pdf->Cell(15, 8, $row['gender'], 1);
    $pdf->Cell(25, 8, $row['purok_name'], 1);
    $pdf->Cell(12, 8, $row['is_pwd'] ? 'Yes' : 'No', 1);
    $pdf->Cell(12, 8, $row['is_4ps'] ? 'Yes' : 'No', 1);
    $pdf->Cell(30, 8, $row['contact_number'], 1);
    $pdf->Cell(40, 8, $row['address'], 1);
    $pdf->Ln();
}
$pdf->Output('D', 'individuals_list.pdf');
exit;
