<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';

// Check if export parameter is provided
if (!isset($_GET['export'])) {
    header('Location: individuals.php');
    exit();
}

$export_type = $_GET['export'];

// Get database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Fetch residents data with the specified columns only
$sql = "SELECT 
    i.first_name,
    i.middle_name,
    i.last_name,
    i.suffix,
    i.gender,
    i.birthdate,
    i.civil_status,
    p.name as purok
FROM individuals i 
LEFT JOIN purok p ON i.purok_id = p.id
ORDER BY i.last_name, i.first_name";

$result = $conn->query($sql);

if (!$result) {
    die('Query failed: ' . $conn->error);
}

$residents = [];
while ($row = $result->fetch_assoc()) {
    // Clean and format data
    $resident = [
        'first_name' => $row['first_name'] ?? '',
        'middle_name' => $row['middle_name'] ?? '',
        'last_name' => $row['last_name'] ?? '',
        'suffix' => $row['suffix'] ?? '',
        'gender' => ucfirst($row['gender'] ?? ''),
        'birthdate' => $row['birthdate'] ?? '',
        'civil_status' => $row['civil_status'] ?? '',
        'purok' => $row['purok'] ?? ''
    ];
    
    // Format birthdate
    if ($resident['birthdate']) {
        $date = new DateTime($resident['birthdate']);
        $resident['birthdate'] = $date->format('m/d/Y');
    }
    
    $residents[] = $resident;
}

$conn->close();

// Column headers
$headers = [
    'First Name',
    'Middle Name', 
    'Last Name',
    'Suffix',
    'Gender',
    'Birthdate',
    'Civil Status',
    'Purok'
];

// Generate filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$filename = "residents_export_{$timestamp}";

switch ($export_type) {
    case 'excel':
        // Excel export using simple HTML table format that Excel can interpret
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<!DOCTYPE html>';
        echo '<html><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1">';
        
        // Headers
        echo '<tr>';
        foreach ($headers as $header) {
            echo '<th style="background-color: #3B82F6; color: white; font-weight: bold; text-align: center; padding: 5px;">' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        // Data rows
        foreach ($residents as $resident) {
            echo '<tr>';
            echo '<td style="padding: 3px;">' . htmlspecialchars($resident['first_name']) . '</td>';
            echo '<td style="padding: 3px;">' . htmlspecialchars($resident['middle_name']) . '</td>';
            echo '<td style="padding: 3px;">' . htmlspecialchars($resident['last_name']) . '</td>';
            echo '<td style="padding: 3px;">' . htmlspecialchars($resident['suffix']) . '</td>';
            echo '<td style="padding: 3px; text-align: center;">' . htmlspecialchars($resident['gender']) . '</td>';
            echo '<td style="padding: 3px; text-align: center;">' . htmlspecialchars($resident['birthdate']) . '</td>';
            echo '<td style="padding: 3px; text-align: center;">' . htmlspecialchars($resident['civil_status']) . '</td>';
            echo '<td style="padding: 3px;">' . htmlspecialchars($resident['purok']) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body></html>';
        break;
        
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: max-age=0');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data
        foreach ($residents as $resident) {
            fputcsv($output, [
                $resident['first_name'],
                $resident['middle_name'],
                $resident['last_name'],
                $resident['suffix'],
                $resident['gender'],
                $resident['birthdate'],
                $resident['civil_status'],
                $resident['purok']
            ]);
        }
        
        fclose($output);
        break;
        
    case 'pdf':
        // PDF export using FPDF
        require_once 'lib/fpdf/fpdf.php';
        
        class PDF extends FPDF {
            function Header() {
                $this->SetFont('Arial', 'B', 16);
                $this->Cell(0, 10, 'Residents Information Report', 0, 1, 'C');
                $this->Ln(10);
            }
            
            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - Generated on ' . date('Y-m-d H:i:s'), 0, 0, 'C');
            }
        }
        
        $pdf = new PDF('L', 'mm', 'A4'); // Landscape orientation
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
        
        // Column widths (adjust to fit landscape A4)
        $widths = [30, 25, 30, 15, 20, 25, 25, 25];
        
        // Headers
        $pdf->SetFillColor(59, 130, 246); // Blue background
        $pdf->SetTextColor(255, 255, 255); // White text
        
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        // Data
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(0, 0, 0); // Black text
        $pdf->SetFillColor(240, 240, 240); // Light gray for alternate rows
        
        $fill = false;
        foreach ($residents as $resident) {
            $pdf->Cell($widths[0], 6, $resident['first_name'], 1, 0, 'L', $fill);
            $pdf->Cell($widths[1], 6, $resident['middle_name'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[2], 6, $resident['last_name'], 1, 0, 'L', $fill);
            $pdf->Cell($widths[3], 6, $resident['suffix'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[4], 6, $resident['gender'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[5], 6, $resident['birthdate'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[6], 6, $resident['civil_status'], 1, 0, 'C', $fill);
            $pdf->Cell($widths[7], 6, $resident['purok'], 1, 1, 'L', $fill);
            
            $fill = !$fill; // Alternate row colors
        }
        
        $pdf->Output('D', $filename . '.pdf');
        break;
        
    default:
        header('Location: individuals.php');
        exit();
}

exit();
?>
