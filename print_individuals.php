<?php
session_start();
require_once 'config.php';
require_once __DIR__ . '/lib/fpdf/fpdf.php'; // Use absolute path to ensure correct inclusion

if (!isset($_SESSION['user_id'])) {
    die('Access denied. Please log in.');
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Fetch system settings for header/footer or other info if needed
$system_title = 'Resident Information and Certification Management System';
$barangay_name = 'Barangay Name'; // Default
$municipality_name = 'Municipality'; // Default
$province_name = 'Province'; // Default

$settings_keys = ['system_title', 'barangay_name', 'municipality_name', 'province_name'];
$placeholders = rtrim(str_repeat('?,', count($settings_keys)), ',');
$stmt_settings = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ($placeholders)");
$stmt_settings->bind_param(str_repeat('s', count($settings_keys)), ...$settings_keys);
$stmt_settings->execute();
$result_settings = $stmt_settings->get_result();
while ($row = $result_settings->fetch_assoc()) {
    if ($row['setting_key'] == 'system_title') $system_title = $row['setting_value'];
    if ($row['setting_key'] == 'barangay_name') $barangay_name = $row['setting_value'];
    if ($row['setting_key'] == 'municipality_name') $municipality_name = $row['setting_value'];
    if ($row['setting_key'] == 'province_name') $province_name = $row['setting_value'];
}
$stmt_settings->close();

$ids_string = isset($_GET['ids']) ? $_GET['ids'] : '';
if (empty($ids_string)) {
    die('No resident IDs provided.');
}

$ids_array = explode(',', $ids_string);
$sanitized_ids = array_map('intval', $ids_array);
$sanitized_ids = array_filter($sanitized_ids, function($id) { return $id > 0; });

if (empty($sanitized_ids)) {
    die('No valid resident IDs provided.');
}

$placeholders_sql = implode(',', array_fill(0, count($sanitized_ids), '?'));
$types_sql = str_repeat('i', count($sanitized_ids));

// Fetch resident data
// Added DATE_FORMAT for birthdate and TIMESTAMPDIFF for age calculation
$sql = "SELECT id, last_name, first_name, middle_name, suffix, DATE_FORMAT(birthdate, '%m/%d/%Y') AS birthdate, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age, gender, civil_status, purok, contact_number, email FROM individuals WHERE id IN ($placeholders_sql) ORDER BY last_name ASC, first_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types_sql, ...$sanitized_ids);
$stmt->execute();
$result = $stmt->get_result();
$residents = [];
while ($row = $result->fetch_assoc()) {
    $residents[] = $row;
}
$stmt->close();
$conn->close();

if (empty($residents)) {
    die('No resident data found for the provided IDs.');
}

class PDF extends FPDF
{
    private $barangay_name;
    private $municipality_name;
    private $province_name;
    private $report_title;

    function __construct($orientation='P', $unit='mm', $size='A4', $barangay_name, $municipality_name, $province_name, $report_title) {
        parent::__construct($orientation, $unit, $size);
        $this->barangay_name = $barangay_name;
        $this->municipality_name = $municipality_name;
        $this->province_name = $province_name;
        $this->report_title = $report_title;
        $this->SetAutoPageBreak(true, 15); // Set auto page break with margin
    }

    // Page header
    function Header()
    {
        // Logo (optional, if you have a logo path in settings)
        // $this->Image('path/to/logo.png',10,6,30);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');
        $this->Cell(0, 5, 'Province of ' . $this->province_name, 0, 1, 'C');
        $this->Cell(0, 5, 'Municipality of ' . $this->municipality_name, 0, 1, 'C');
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 7, 'Barangay ' . $this->barangay_name, 0, 1, 'C');
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, $this->report_title, 0, 1, 'C');
        $this->Ln(2); // Add a little space before the table
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->SetX(-35); // Position for Printed by
        $this->Cell(0,10, 'Printed on: ' . date('m/d/Y h:i A'), 0, 0, 'R');
    }

    // Table
    function CreateTable($header, $data)
    {
        // Column widths
        // Adjusted widths: ID, Name (wider), Sex, Age, Birthdate, Civil Status, Purok, Contact
        $w = [10, 65, 15, 15, 25, 25, 20, 35]; // Total width: 210 for landscape, adjust if needed for portrait
        $this->SetFillColor(230, 230, 230); // Header fill color
        $this->SetTextColor(0);
        $this->SetDrawColor(128, 128, 128); // Border color
        $this->SetFont('Arial', 'B', 8);
        for ($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        $this->Ln();

        $this->SetFont('Arial', '', 8);
        $this->SetFillColor(255);
        $fill = false;
        foreach ($data as $row)
        {
            $this->Cell($w[0], 6, $row['id'], 'LR', 0, 'C', $fill);
            $fullName = trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['suffix']);
            $this->Cell($w[1], 6, $fullName, 'LR', 0, 'L', $fill);
            $this->Cell($w[2], 6, ucfirst($row['gender']), 'LR', 0, 'L', $fill);
            $this->Cell($w[3], 6, $row['age'], 'LR', 0, 'C', $fill);
            $this->Cell($w[4], 6, $row['birthdate'], 'LR', 0, 'C', $fill);
            $this->Cell($w[5], 6, $row['civil_status'], 'LR', 0, 'L', $fill);
            $this->Cell($w[6], 6, $row['purok'], 'LR', 0, 'L', $fill);
            $this->Cell($w[7], 6, $row['contact_number'], 'LR', 0, 'L', $fill);
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T'); // Closing line
    }
}

// Create PDF
$report_title_pdf = 'List of Selected Residents';
$pdf = new PDF('L', 'mm', 'Letter', $barangay_name, $municipality_name, $province_name, $report_title_pdf); // Use Letter size, Landscape
$pdf->AliasNbPages(); // Enables page numbering {nb}
$pdf->AddPage();

// Define table header
$header = ['ID', 'Name', 'Sex', 'Age', 'Birthdate', 'Civil Status', 'Purok', 'Contact #'];

// Prepare data for the table
$data_for_table = [];
foreach ($residents as $resident) {
    $data_for_table[] = [
        'id' => $resident['id'],
        'last_name' => $resident['last_name'],
        'first_name' => $resident['first_name'],
        'middle_name' => $resident['middle_name'],
        'suffix' => $resident['suffix'],
        'gender' => $resident['gender'],
        'age' => $resident['age'],
        'birthdate' => $resident['birthdate'],
        'civil_status' => $resident['civil_status'],
        'purok' => $resident['purok'],
        'contact_number' => $resident['contact_number']
    ];
}

$pdf->CreateTable($header, $data_for_table);

$pdf->Output('I', 'Selected_Residents_List.pdf'); // I: Inline, D: Download

?>
