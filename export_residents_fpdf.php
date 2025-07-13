<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';
require_once 'lib/fpdf/fpdf.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare('SELECT first_name, last_name FROM users WHERE id = ?');
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_full_name = trim($user['first_name'] . ' ' . $user['last_name']);

// Get system settings
function getSystemSettings() {
    global $conn;
    $settings = [
        'barangay_name' => 'Barangay Sucol',
        'municipality' => 'Calumpit',
        'province' => 'Bulacan',
        'system_title' => 'Barangay Information Management System'
    ];
    
    $result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

// Get filter type from request
$filter_type = $_GET['filter_type'] ?? $_POST['filter_type'] ?? '';

// Build query based on filter
$query = "SELECT 
    i.first_name,
    i.middle_name,
    i.last_name,
    i.suffix,
    i.gender,
    i.birthdate,
    i.civil_status,
    i.contact_no,
    i.email,
    p.name as purok_name,
    i.religion,
    i.is_voter,
    i.is_pwd,
    i.is_4ps,
    i.is_solo_parent,
    i.is_pregnant
FROM individuals i
LEFT JOIN purok p ON i.purok_id = p.id";

$where_conditions = [];
$params = [];
$types = '';

// Apply filters based on filter_type
switch ($filter_type) {
    case 'male':
        $where_conditions[] = "i.gender = ?";
        $params[] = 'male';
        $types .= 's';
        break;
    case 'female':
        $where_conditions[] = "i.gender = ?";
        $params[] = 'female';
        $types .= 's';
        break;
    case 'voter':
        $where_conditions[] = "i.is_voter = ?";
        $params[] = 1;
        $types .= 'i';
        break;
    case '4ps':
        $where_conditions[] = "i.is_4ps = ?";
        $params[] = 1;
        $types .= 'i';
        break;
    case 'senior':
        $where_conditions[] = "TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) >= ?";
        $params[] = 60;
        $types .= 'i';
        break;
    case 'pwd':
        $where_conditions[] = "i.is_pwd = ?";
        $params[] = 1;
        $types .= 'i';
        break;
    case 'solo_parent':
        $where_conditions[] = "i.is_solo_parent = ?";
        $params[] = 1;
        $types .= 'i';
        break;
    case 'minor':
        $where_conditions[] = "TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) < ?";
        $params[] = 18;
        $types .= 'i';
        break;
    case 'children_and_youth':
        $where_conditions[] = "TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) BETWEEN ? AND ?";
        $params[] = 0;
        $params[] = 30;
        $types .= 'ii';
        break;
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

$query .= " ORDER BY i.last_name, i.first_name";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get system settings
$settings = getSystemSettings();

// Create extended FPDF class for better table handling
class PDF extends FPDF
{
    private $settings;
    private $filterTitle;
    
    public function __construct($settings, $filterTitle = '') {
        parent::__construct();
        $this->settings = $settings;
        $this->filterTitle = $filterTitle;
    }
    
    // Page header
    function Header()
    {
        // Logo space (if you have a logo)
        // $this->Image('img/logo.png', 10, 6, 30);
        
        // Move to the right
        $this->SetX(10);
        
        // Title
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, $this->settings['system_title'], 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 8, 'RESIDENTS INFORMATION REPORT', 0, 1, 'C');
        
        if (!empty($this->filterTitle)) {
            $this->SetFont('Arial', 'I', 12);
            $this->Cell(0, 6, $this->filterTitle, 0, 1, 'C');
        }
        
        // Address
        $this->SetFont('Arial', '', 10);
        $address = $this->settings['barangay_name'] . ', ' . $this->settings['municipality'] . ', ' . $this->settings['province'];
        $this->Cell(0, 6, $address, 0, 1, 'C');
        
        // Date
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, 'Generated on: ' . date('F d, Y h:i A'), 0, 1, 'C');
        
        // Line break
        $this->Ln(5);
        
        // Draw line
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
        $this->Ln(5);
    }
    
    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        
        // Draw line
        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY(), $this->GetPageWidth() - 10, $this->GetY());
        $this->Ln(2);
        
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
        
        // Generated by
        $this->SetX(10);
        global $user_full_name;
        $this->Cell(0, 10, 'Generated by: ' . $user_full_name, 0, 0, 'L');
    }
    
    // Better table with consistent row heights
    function ImprovedTable($header, $data)
    {
        // Column widths (updated for 9 columns - removed status column)
        $w = array(30, 25, 30, 15, 18, 25, 25, 22, 25);
        
        // Header
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(59, 130, 246); // Blue header
        $this->SetTextColor(255, 255, 255); // White text
        $this->SetDrawColor(0, 0, 0); // Black border
        
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Data
        $this->SetFont('Arial', '', 7);
        $this->SetFillColor(240, 240, 240); // Light gray
        $this->SetTextColor(0, 0, 0); // Black text
        
        $fill = false;
        foreach ($data as $row) {
            // Use fixed row height to prevent overlapping
            $rowHeight = 8;
            
            // Check if we need a new page
            if ($this->GetY() + $rowHeight > $this->GetPageHeight() - 30) {
                $this->AddPage();
                // Repeat header on new page
                $this->SetFont('Arial', 'B', 8);
                $this->SetFillColor(59, 130, 246);
                $this->SetTextColor(255, 255, 255);
                for ($i = 0; $i < count($header); $i++) {
                    $this->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
                }
                $this->Ln();
                $this->SetFont('Arial', '', 7);
                $this->SetTextColor(0, 0, 0);
            }
            
            // Print the cells of the current row using simple Cell method
            for ($i = 0; $i < count($row); $i++) {
                // Truncate text if too long to prevent wrapping
                $cellText = $row[$i];
                if (strlen($cellText) > 20) {
                    $cellText = substr($cellText, 0, 17) . '...';
                }
                $this->Cell($w[$i], $rowHeight, $cellText, 1, 0, 'C', $fill);
            }
            $this->Ln();
            $fill = !$fill;
        }
    }
    
    // Count number of lines needed for MultiCell
    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}

// Determine filter title
$filterTitle = '';
switch ($filter_type) {
    case 'male':
        $filterTitle = '(Male Residents Only)';
        break;
    case 'female':
        $filterTitle = '(Female Residents Only)';
        break;
    case 'voter':
        $filterTitle = '(Registered Voters Only)';
        break;
    case '4ps':
        $filterTitle = '(4Ps Beneficiaries Only)';
        break;
    case 'senior':
        $filterTitle = '(Senior Citizens - 60 years and above)';
        break;
    case 'pwd':
        $filterTitle = '(Persons with Disabilities Only)';
        break;
    case 'solo_parent':
        $filterTitle = '(Solo Parents Only)';
        break;
    case 'minor':
        $filterTitle = '(Minors - Below 18 years old)';
        break;
    case 'children_and_youth':
        $filterTitle = '(Children and Youth - 0 to 30 years old)';
        break;
}

// Create PDF
$pdf = new PDF($settings, $filterTitle);
$pdf->AliasNbPages();
$pdf->AddPage('L'); // Landscape orientation for better table fit

// PDF Export Specific Formatting:
// 1. Birthdate: Shows only date without age (different from table display)
// 2. Purok: Shows simplified "Purok X" format (different from table display)  
// 3. Status: Completely removed from PDF export (still shown in table)

// Prepare data for the table
$header = array(
    'First Name',
    'Middle Name', 
    'Last Name',
    'Suffix',
    'Gender',
    'Birthdate',
    'Civil Status',
    'Contact No.',
    'Purok'
);

$data = array();
while ($row = $result->fetch_assoc()) {
    // Format birthdate (PDF only - no age)
    $birthdate = '';
    if ($row['birthdate']) {
        $date = new DateTime($row['birthdate']);
        $birthdate = $date->format('M d, Y');
    }
    
    // Format purok name (PDF only - simplified format)
    $purokName = '';
    if ($row['purok_name']) {
        // Extract only the purok number/name and format as "Purok X"
        $purokRaw = $row['purok_name'];
        
        // Try to extract number from various formats
        if (preg_match('/(\d+)/', $purokRaw, $matches)) {
            $purokName = 'Purok ' . $matches[1];
        } else if (stripos($purokRaw, 'purok') !== false) {
            // If it already contains "purok" but no number found
            $purokName = ucwords(strtolower($purokRaw));
        } else {
            // Fallback - assume it's a purok number or name
            $purokName = 'Purok ' . $purokRaw;
        }
    }
    
    // Clean suffix and middle name
    $suffix = $row['suffix'] ?: '';
    $middleName = $row['middle_name'] ?: '';
    
    // Prepare row data (removed status column)
    $data[] = array(
        $row['first_name'] ?: '',
        $middleName,
        $row['last_name'] ?: '',
        $suffix,
        ucfirst($row['gender'] ?: ''),
        $birthdate,
        $row['civil_status'] ?: '',
        $row['contact_no'] ?: '',
        $purokName
    );
}

// Add summary information
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, 'SUMMARY', 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, 'Total Residents: ' . count($data), 0, 1, 'L');
$pdf->Ln(5);

// Generate the table
$pdf->ImprovedTable($header, $data);

// Generate filename
$timestamp = date('Y-m-d_H-i-s');
$filename = 'residents_export_' . ($filter_type ? $filter_type . '_' : '') . $timestamp . '.pdf';

// Output the PDF
$pdf->Output('D', $filename);
?>
