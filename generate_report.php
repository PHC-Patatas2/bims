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

$report_type = $_POST['type'] ?? $_GET['type'] ?? '';
$format = $_POST['format'] ?? $_GET['format'] ?? 'pdf';

if (empty($report_type)) {
    die('Report type is required');
}

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

// Generate reports based on type
switch ($report_type) {
    case 'demographics':
        generateDemographicsReport();
        break;
    case 'voters':
        generateVotersReport();
        break;
    case 'pwd':
        generatePWDReport();
        break;
    case 'seniors':
        generateSeniorsReport();
        break;
    case '4ps':
        generate4PsReport();
        break;
    case 'solo_parents':
        generateSoloParentsReport();
        break;
    case 'certificates':
        generateCertificatesReport();
        break;
    case 'purok':
        generatePurokReport();
        break;
    default:
        die('Invalid report type');
}

function generateDemographicsReport() {
    global $conn;
    $settings = getSystemSettings();
    
    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Header
    addReportHeader($pdf, $settings, 'DEMOGRAPHICS REPORT');
    
    // Get demographics data
    $total_query = "SELECT COUNT(*) as total FROM individuals";
    $total_result = $conn->query($total_query);
    $total_residents = $total_result->fetch_assoc()['total'];
    
    // Gender breakdown
    $gender_query = "SELECT gender, COUNT(*) as count FROM individuals GROUP BY gender";
    $gender_result = $conn->query($gender_query);
    
    // Age groups
    $age_query = "SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 18 THEN 'Minors (0-17)'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 18 AND 59 THEN 'Adults (18-59)'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 THEN 'Senior Citizens (60+)'
            ELSE 'Unknown'
        END as age_group,
        COUNT(*) as count
        FROM individuals 
        WHERE birthdate IS NOT NULL 
        GROUP BY age_group";
    $age_result = $conn->query($age_query);
    
    // Civil status
    $civil_query = "SELECT civil_status, COUNT(*) as count FROM individuals GROUP BY civil_status";
    $civil_result = $conn->query($civil_query);
    
    $pdf->SetY(60);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'POPULATION SUMMARY', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Total Residents: $total_residents", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Gender breakdown
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'GENDER DISTRIBUTION', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $gender_result->fetch_assoc()) {
        $percentage = round(($row['count'] / $total_residents) * 100, 1);
        $pdf->Cell(0, 6, "{$row['gender']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Age groups
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'AGE DISTRIBUTION', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $age_result->fetch_assoc()) {
        $percentage = round(($row['count'] / $total_residents) * 100, 1);
        $pdf->Cell(0, 6, "{$row['age_group']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Civil status
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'CIVIL STATUS DISTRIBUTION', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $civil_result->fetch_assoc()) {
        $percentage = round(($row['count'] / $total_residents) * 100, 1);
        $pdf->Cell(0, 6, "{$row['civil_status']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(10);
    
    // Add detailed list of all residents
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'COMPLETE RESIDENTS LIST', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Get all individuals with details
    $individuals_query = "SELECT 
        CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name,
        gender,
        TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age,
        civil_status,
        purok.name as purok_name
        FROM individuals 
        LEFT JOIN purok ON individuals.purok_id = purok.id
        ORDER BY last_name, first_name";
    $individuals_result = $conn->query($individuals_query);
    
    // Table header
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
    $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
    $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
    $pdf->Cell(25, 6, 'CIVIL STATUS', 1, 0, 'C');
    $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
    
    $pdf->SetFont('Arial', '', 7);
    $counter = 1;
    while ($row = $individuals_result->fetch_assoc()) {
        // Check if we need a new page
        if ($pdf->GetY() > 250) {
            $pdf->AddPage();
            // Repeat header
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
            $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
            $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
            $pdf->Cell(25, 6, 'CIVIL STATUS', 1, 0, 'C');
            $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
            $pdf->SetFont('Arial', '', 7);
        }
        
        $pdf->Cell(50, 5, $row['full_name'], 1, 0, 'L');
        $pdf->Cell(15, 5, $row['gender'], 1, 0, 'C');
        $pdf->Cell(10, 5, $row['age'] ?: 'N/A', 1, 0, 'C');
        $pdf->Cell(25, 5, $row['civil_status'] ?: 'N/A', 1, 0, 'L');
        $pdf->Cell(30, 5, $row['purok_name'] ?: 'Unassigned', 1, 1, 'L');
        $counter++;
    }
    
    addReportFooter($pdf);
    outputPDF($pdf, 'demographics_report');
}

function generateVotersReport() {
    global $conn;
    $settings = getSystemSettings();
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    addReportHeader($pdf, $settings, 'VOTER STATISTICS REPORT');
    
    // Get voter data
    $total_query = "SELECT COUNT(*) as total FROM individuals WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 18";
    $total_result = $conn->query($total_query);
    $eligible_voters = $total_result->fetch_assoc()['total'];
    
    $registered_query = "SELECT COUNT(*) as total FROM individuals WHERE is_voter = 1";
    $registered_result = $conn->query($registered_query);
    $registered_voters = $registered_result->fetch_assoc()['total'];
    
    $gender_voters_query = "SELECT gender, COUNT(*) as count FROM individuals WHERE is_voter = 1 GROUP BY gender";
    $gender_voters_result = $conn->query($gender_voters_query);
    
    $pdf->SetY(60);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'VOTER STATISTICS', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Eligible Voters (18+ years): $eligible_voters", 0, 1, 'L');
    $pdf->Cell(0, 8, "Registered Voters: $registered_voters", 0, 1, 'L');
    
    $registration_rate = $eligible_voters > 0 ? round(($registered_voters / $eligible_voters) * 100, 1) : 0;
    $pdf->Cell(0, 8, "Registration Rate: $registration_rate%", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Gender breakdown of registered voters
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'REGISTERED VOTERS BY GENDER', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $gender_voters_result->fetch_assoc()) {
        $percentage = $registered_voters > 0 ? round(($row['count'] / $registered_voters) * 100, 1) : 0;
        $pdf->Cell(0, 6, "{$row['gender']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(10);
    
    // Add detailed list of registered voters
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'LIST OF REGISTERED VOTERS', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Get registered voters with details
    $voters_query = "SELECT 
        CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name,
        gender,
        TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age,
        contact_no,
        purok.name as purok_name
        FROM individuals 
        LEFT JOIN purok ON individuals.purok_id = purok.id
        WHERE is_voter = 1
        ORDER BY last_name, first_name";
    $voters_result = $conn->query($voters_query);
    
    if ($voters_result->num_rows > 0) {
        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
        $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
        $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
        $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 0, 'C');
        $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $counter = 1;
        while ($row = $voters_result->fetch_assoc()) {
            // Check if we need a new page
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Repeat header
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
                $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
                $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
                $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 0, 'C');
                $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
                $pdf->SetFont('Arial', '', 8);
            }
            
            $pdf->Cell(50, 5, $counter . '. ' . $row['full_name'], 1, 0, 'L');
            $pdf->Cell(15, 5, $row['gender'], 1, 0, 'C');
            $pdf->Cell(10, 5, $row['age'] ?: 'N/A', 1, 0, 'C');
            $pdf->Cell(35, 5, $row['contact_no'] ?: 'N/A', 1, 0, 'L');
            $pdf->Cell(30, 5, $row['purok_name'] ?: 'Unassigned', 1, 1, 'L');
            $counter++;
        }
    } else {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, 'No registered voters found in the database.', 0, 1, 'L');
    }
    
    addReportFooter($pdf);
    outputPDF($pdf, 'voters_report');
}

function generatePWDReport() {
    global $conn;
    $settings = getSystemSettings();
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    addReportHeader($pdf, $settings, 'PERSONS WITH DISABILITIES (PWD) REPORT');
    
    // Get PWD data
    $total_query = "SELECT COUNT(*) as total FROM individuals WHERE is_pwd = 1";
    $total_result = $conn->query($total_query);
    $total_pwd = $total_result->fetch_assoc()['total'];
    
    $gender_query = "SELECT gender, COUNT(*) as count FROM individuals WHERE is_pwd = 1 GROUP BY gender";
    $gender_result = $conn->query($gender_query);
    
    $age_query = "SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 18 THEN 'Minors (0-17)'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 18 AND 59 THEN 'Adults (18-59)'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 THEN 'Senior Citizens (60+)'
            ELSE 'Unknown'
        END as age_group,
        COUNT(*) as count
        FROM individuals 
        WHERE is_pwd = 1 AND birthdate IS NOT NULL 
        GROUP BY age_group";
    $age_result = $conn->query($age_query);
    
    $pdf->SetY(60);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'PWD STATISTICS', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Total PWDs: $total_pwd", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Gender breakdown
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'PWD BY GENDER', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $gender_result->fetch_assoc()) {
        $percentage = $total_pwd > 0 ? round(($row['count'] / $total_pwd) * 100, 1) : 0;
        $pdf->Cell(0, 6, "{$row['gender']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Age groups
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'PWD BY AGE GROUP', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $age_result->fetch_assoc()) {
        $percentage = $total_pwd > 0 ? round(($row['count'] / $total_pwd) * 100, 1) : 0;
        $pdf->Cell(0, 6, "{$row['age_group']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(10);
    
    // Add detailed list of PWD individuals
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'LIST OF PERSONS WITH DISABILITIES', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Get PWD individuals with details
    $pwd_query = "SELECT 
        CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name,
        gender,
        TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age,
        contact_no,
        purok.name as purok_name
        FROM individuals 
        LEFT JOIN purok ON individuals.purok_id = purok.id
        WHERE is_pwd = 1
        ORDER BY last_name, first_name";
    $pwd_result = $conn->query($pwd_query);
    
    if ($pwd_result->num_rows > 0) {
        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
        $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
        $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
        $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 0, 'C');
        $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $counter = 1;
        while ($row = $pwd_result->fetch_assoc()) {
            // Check if we need a new page
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Repeat header
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
                $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
                $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
                $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 0, 'C');
                $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
                $pdf->SetFont('Arial', '', 8);
            }
            
            $pdf->Cell(50, 5, $counter . '. ' . $row['full_name'], 1, 0, 'L');
            $pdf->Cell(15, 5, $row['gender'], 1, 0, 'C');
            $pdf->Cell(10, 5, $row['age'] ?: 'N/A', 1, 0, 'C');
            $pdf->Cell(35, 5, $row['contact_no'] ?: 'N/A', 1, 0, 'L');
            $pdf->Cell(30, 5, $row['purok_name'] ?: 'Unassigned', 1, 1, 'L');
            $counter++;
        }
    } else {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, 'No PWD residents found in the database.', 0, 1, 'L');
    }
    
    addReportFooter($pdf);
    outputPDF($pdf, 'pwd_report');
}

function generateSeniorsReport() {
    global $conn;
    $settings = getSystemSettings();
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    addReportHeader($pdf, $settings, 'SENIOR CITIZENS REPORT');
    
    // Get senior citizens data
    $total_query = "SELECT COUNT(*) as total FROM individuals WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60";
    $total_result = $conn->query($total_query);
    $total_seniors = $total_result->fetch_assoc()['total'];
    
    $gender_query = "SELECT gender, COUNT(*) as count FROM individuals WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60 GROUP BY gender";
    $gender_result = $conn->query($gender_query);
    
    $pdf->SetY(60);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'SENIOR CITIZENS STATISTICS', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Total Senior Citizens (60+ years): $total_seniors", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Gender breakdown
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'SENIOR CITIZENS BY GENDER', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $gender_result->fetch_assoc()) {
        $percentage = $total_seniors > 0 ? round(($row['count'] / $total_seniors) * 100, 1) : 0;
        $pdf->Cell(0, 6, "{$row['gender']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(10);
    
    // Add detailed list of senior citizens
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'LIST OF SENIOR CITIZENS (60+ YEARS OLD)', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Get senior citizens with details
    $seniors_query = "SELECT 
        CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name,
        gender,
        TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age,
        birthdate,
        contact_no,
        purok.name as purok_name
        FROM individuals 
        LEFT JOIN purok ON individuals.purok_id = purok.id
        WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60
        ORDER BY age DESC, last_name, first_name";
    $seniors_result = $conn->query($seniors_query);
    
    if ($seniors_result->num_rows > 0) {
        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(45, 6, 'NAME', 1, 0, 'C');
        $pdf->Cell(12, 6, 'GENDER', 1, 0, 'C');
        $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
        $pdf->Cell(25, 6, 'BIRTHDATE', 1, 0, 'C');
        $pdf->Cell(30, 6, 'CONTACT', 1, 0, 'C');
        $pdf->Cell(28, 6, 'PUROK', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 7);
        $counter = 1;
        while ($row = $seniors_result->fetch_assoc()) {
            // Check if we need a new page
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Repeat header
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(45, 6, 'NAME', 1, 0, 'C');
                $pdf->Cell(12, 6, 'GENDER', 1, 0, 'C');
                $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
                $pdf->Cell(25, 6, 'BIRTHDATE', 1, 0, 'C');
                $pdf->Cell(30, 6, 'CONTACT', 1, 0, 'C');
                $pdf->Cell(28, 6, 'PUROK', 1, 1, 'C');
                $pdf->SetFont('Arial', '', 7);
            }
            
            $pdf->Cell(45, 5, $counter . '. ' . $row['full_name'], 1, 0, 'L');
            $pdf->Cell(12, 5, $row['gender'], 1, 0, 'C');
            $pdf->Cell(10, 5, $row['age'], 1, 0, 'C');
            $pdf->Cell(25, 5, date('M d, Y', strtotime($row['birthdate'])), 1, 0, 'C');
            $pdf->Cell(30, 5, $row['contact_no'] ?: 'N/A', 1, 0, 'L');
            $pdf->Cell(28, 5, $row['purok_name'] ?: 'Unassigned', 1, 1, 'L');
            $counter++;
        }
    } else {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, 'No senior citizens found in the database.', 0, 1, 'L');
    }
    
    addReportFooter($pdf);
    outputPDF($pdf, 'seniors_report');
}

function generate4PsReport() {
    global $conn;
    $settings = getSystemSettings();
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    addReportHeader($pdf, $settings, '4PS BENEFICIARIES REPORT');
    
    // Get 4Ps data
    $total_query = "SELECT COUNT(*) as total FROM individuals WHERE is_4ps = 1";
    $total_result = $conn->query($total_query);
    $total_4ps = $total_result->fetch_assoc()['total'];
    
    $gender_query = "SELECT gender, COUNT(*) as count FROM individuals WHERE is_4ps = 1 GROUP BY gender";
    $gender_result = $conn->query($gender_query);
    
    $pdf->SetY(60);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, '4PS BENEFICIARIES STATISTICS', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Total 4Ps Beneficiaries: $total_4ps", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Gender breakdown
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, '4PS BENEFICIARIES BY GENDER', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $gender_result->fetch_assoc()) {
        $percentage = $total_4ps > 0 ? round(($row['count'] / $total_4ps) * 100, 1) : 0;
        $pdf->Cell(0, 6, "{$row['gender']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(10);
    
    // Add detailed list of 4Ps beneficiaries
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'LIST OF 4PS BENEFICIARIES', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Get 4Ps beneficiaries with details
    $fourps_query = "SELECT 
        CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name,
        gender,
        TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age,
        contact_no,
        purok.name as purok_name
        FROM individuals 
        LEFT JOIN purok ON individuals.purok_id = purok.id
        WHERE is_4ps = 1
        ORDER BY last_name, first_name";
    $fourps_result = $conn->query($fourps_query);
    
    if ($fourps_result->num_rows > 0) {
        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
        $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
        $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
        $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 0, 'C');
        $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $counter = 1;
        while ($row = $fourps_result->fetch_assoc()) {
            // Check if we need a new page
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Repeat header
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
                $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
                $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
                $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 0, 'C');
                $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
                $pdf->SetFont('Arial', '', 8);
            }
            
            $pdf->Cell(50, 5, $counter . '. ' . $row['full_name'], 1, 0, 'L');
            $pdf->Cell(15, 5, $row['gender'], 1, 0, 'C');
            $pdf->Cell(10, 5, $row['age'] ?: 'N/A', 1, 0, 'C');
            $pdf->Cell(35, 5, $row['contact_no'] ?: 'N/A', 1, 0, 'L');
            $pdf->Cell(30, 5, $row['purok_name'] ?: 'Unassigned', 1, 1, 'L');
            $counter++;
        }
    } else {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, 'No 4Ps beneficiaries found in the database.', 0, 1, 'L');
    }
    
    addReportFooter($pdf);
    outputPDF($pdf, '4ps_report');
}

function generateSoloParentsReport() {
    global $conn;
    $settings = getSystemSettings();
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    addReportHeader($pdf, $settings, 'SOLO PARENTS REPORT');
    
    // Get solo parents data
    $total_query = "SELECT COUNT(*) as total FROM individuals WHERE is_solo_parent = 1";
    $total_result = $conn->query($total_query);
    $total_solo = $total_result->fetch_assoc()['total'];
    
    $gender_query = "SELECT gender, COUNT(*) as count FROM individuals WHERE is_solo_parent = 1 GROUP BY gender";
    $gender_result = $conn->query($gender_query);
    
    $pdf->SetY(60);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'SOLO PARENTS STATISTICS', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Total Solo Parents: $total_solo", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Gender breakdown
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'SOLO PARENTS BY GENDER', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $gender_result->fetch_assoc()) {
        $percentage = $total_solo > 0 ? round(($row['count'] / $total_solo) * 100, 1) : 0;
        $pdf->Cell(0, 6, "{$row['gender']}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(10);
    
    // Add detailed list of solo parents
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'LIST OF SOLO PARENTS', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Get solo parents with details
    $solo_query = "SELECT 
        CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name,
        gender,
        TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age,
        contact_no,
        purok.name as purok_name
        FROM individuals 
        LEFT JOIN purok ON individuals.purok_id = purok.id
        WHERE is_solo_parent = 1
        ORDER BY last_name, first_name";
    $solo_result = $conn->query($solo_query);
    
    if ($solo_result->num_rows > 0) {
        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
        $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
        $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
        $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 0, 'C');
        $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 8);
        $counter = 1;
        while ($row = $solo_result->fetch_assoc()) {
            // Check if we need a new page
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Repeat header
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
                $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
                $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
                $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 0, 'C');
                $pdf->Cell(30, 6, 'PUROK', 1, 1, 'C');
                $pdf->SetFont('Arial', '', 8);
            }
            
            $pdf->Cell(50, 5, $counter . '. ' . $row['full_name'], 1, 0, 'L');
            $pdf->Cell(15, 5, $row['gender'], 1, 0, 'C');
            $pdf->Cell(10, 5, $row['age'] ?: 'N/A', 1, 0, 'C');
            $pdf->Cell(35, 5, $row['contact_no'] ?: 'N/A', 1, 0, 'L');
            $pdf->Cell(30, 5, $row['purok_name'] ?: 'Unassigned', 1, 1, 'L');
            $counter++;
        }
    } else {
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, 'No solo parents found in the database.', 0, 1, 'L');
    }
    
    addReportFooter($pdf);
    outputPDF($pdf, 'solo_parents_report');
}

function generateCertificatesReport() {
    global $conn;
    $settings = getSystemSettings();
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    addReportHeader($pdf, $settings, 'CERTIFICATE STATISTICS REPORT');
    
    // Get certificate data
    $total_query = "SELECT COUNT(*) as total FROM certificate_requests WHERE status = 'Issued'";
    $total_result = $conn->query($total_query);
    $total_certs = $total_result->fetch_assoc()['total'];
    
    $type_query = "SELECT certificate_type, COUNT(*) as count FROM certificate_requests WHERE status = 'Issued' GROUP BY certificate_type ORDER BY count DESC";
    $type_result = $conn->query($type_query);
    
    $monthly_query = "SELECT DATE_FORMAT(requested_at, '%Y-%m') as month, COUNT(*) as count FROM certificate_requests WHERE status = 'Issued' GROUP BY month ORDER BY month DESC LIMIT 12";
    $monthly_result = $conn->query($monthly_query);
    
    $pdf->SetY(60);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'CERTIFICATE STATISTICS', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Total Certificates Issued: $total_certs", 0, 1, 'L');
    $pdf->Ln(5);
    
    // By certificate type
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'CERTIFICATES BY TYPE', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $type_result->fetch_assoc()) {
        $type_formatted = ucwords(str_replace('_', ' ', $row['certificate_type']));
        $percentage = $total_certs > 0 ? round(($row['count'] / $total_certs) * 100, 1) : 0;
        $pdf->Cell(0, 6, "{$type_formatted}: {$row['count']} ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(5);
    
    // Monthly statistics
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'MONTHLY CERTIFICATE ISSUANCE (Last 12 Months)', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $monthly_result->fetch_assoc()) {
        $month_formatted = date('F Y', strtotime($row['month'] . '-01'));
        $pdf->Cell(0, 6, "{$month_formatted}: {$row['count']} certificates", 0, 1, 'L');
    }
    
    addReportFooter($pdf);
    outputPDF($pdf, 'certificates_report');
}

function generatePurokReport() {
    global $conn;
    $settings = getSystemSettings();
    
    $pdf = new FPDF();
    $pdf->AddPage();
    
    addReportHeader($pdf, $settings, 'PUROK SUMMARY REPORT');
    
    // Get purok data
    $purok_query = "SELECT p.name as purok_name, COUNT(i.id) as residents_count 
                    FROM purok p 
                    LEFT JOIN individuals i ON p.id = i.purok_id 
                    GROUP BY p.id, p.name 
                    ORDER BY residents_count DESC";
    $purok_result = $conn->query($purok_query);
    
    $total_query = "SELECT COUNT(*) as total FROM individuals";
    $total_result = $conn->query($total_query);
    $total_residents = $total_result->fetch_assoc()['total'];
    
    $pdf->SetY(60);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'POPULATION BY PUROK', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Total Residents: $total_residents", 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'RESIDENTS BY PUROK/SITIO', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    
    while ($row = $purok_result->fetch_assoc()) {
        $percentage = $total_residents > 0 ? round(($row['residents_count'] / $total_residents) * 100, 1) : 0;
        $purok_name = $row['purok_name'] ?: 'Unassigned';
        $pdf->Cell(0, 6, "{$purok_name}: {$row['residents_count']} residents ({$percentage}%)", 0, 1, 'L');
    }
    $pdf->Ln(10);
    
    // Add detailed list of residents by purok
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'DETAILED RESIDENTS LIST BY PUROK', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Get all puroks again for detailed listing
    $purok_detail_query = "SELECT p.id, p.name as purok_name 
                          FROM purok p 
                          ORDER BY p.name";
    $purok_detail_result = $conn->query($purok_detail_query);
    
    while ($purok_row = $purok_detail_result->fetch_assoc()) {
        // Get residents for this purok
        $residents_query = "SELECT 
            CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name,
            gender,
            TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age,
            contact_no
            FROM individuals 
            WHERE purok_id = ? 
            ORDER BY last_name, first_name";
        
        $stmt = $conn->prepare($residents_query);
        $stmt->bind_param('i', $purok_row['id']);
        $stmt->execute();
        $residents_result = $stmt->get_result();
        
        if ($residents_result->num_rows > 0) {
            // Check if we need a new page
            if ($pdf->GetY() > 230) {
                $pdf->AddPage();
            }
            
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, strtoupper($purok_row['purok_name']) . " ({$residents_result->num_rows} residents)", 0, 1, 'L');
            $pdf->Ln(2);
            
            // Table header
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
            $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
            $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
            $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 1, 'C');
            
            $pdf->SetFont('Arial', '', 7);
            $counter = 1;
            while ($resident = $residents_result->fetch_assoc()) {
                // Check if we need a new page
                if ($pdf->GetY() > 260) {
                    $pdf->AddPage();
                    // Repeat purok header and table header
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 8, strtoupper($purok_row['purok_name']) . " (continued)", 0, 1, 'L');
                    $pdf->Ln(2);
                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
                    $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
                    $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
                    $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 1, 'C');
                    $pdf->SetFont('Arial', '', 7);
                }
                
                $pdf->Cell(50, 4, $counter . '. ' . $resident['full_name'], 1, 0, 'L');
                $pdf->Cell(15, 4, $resident['gender'], 1, 0, 'C');
                $pdf->Cell(10, 4, $resident['age'] ?: 'N/A', 1, 0, 'C');
                $pdf->Cell(35, 4, $resident['contact_no'] ?: 'N/A', 1, 1, 'L');
                $counter++;
            }
            $pdf->Ln(5);
        }
        $stmt->close();
    }
    
    // Handle unassigned residents
    $unassigned_query = "SELECT 
        CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) as full_name,
        gender,
        TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) as age,
        contact_no
        FROM individuals 
        WHERE purok_id IS NULL OR purok_id = 0
        ORDER BY last_name, first_name";
    $unassigned_result = $conn->query($unassigned_query);
    
    if ($unassigned_result->num_rows > 0) {
        // Check if we need a new page
        if ($pdf->GetY() > 230) {
            $pdf->AddPage();
        }
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, "UNASSIGNED RESIDENTS ({$unassigned_result->num_rows} residents)", 0, 1, 'L');
        $pdf->Ln(2);
        
        // Table header
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
        $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
        $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
        $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 1, 'C');
        
        $pdf->SetFont('Arial', '', 7);
        $counter = 1;
        while ($resident = $unassigned_result->fetch_assoc()) {
            // Check if we need a new page
            if ($pdf->GetY() > 260) {
                $pdf->AddPage();
                // Repeat header
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 8, "UNASSIGNED RESIDENTS (continued)", 0, 1, 'L');
                $pdf->Ln(2);
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell(50, 6, 'NAME', 1, 0, 'C');
                $pdf->Cell(15, 6, 'GENDER', 1, 0, 'C');
                $pdf->Cell(10, 6, 'AGE', 1, 0, 'C');
                $pdf->Cell(35, 6, 'CONTACT NUMBER', 1, 1, 'C');
                $pdf->SetFont('Arial', '', 7);
            }
            
            $pdf->Cell(50, 4, $counter . '. ' . $resident['full_name'], 1, 0, 'L');
            $pdf->Cell(15, 4, $resident['gender'], 1, 0, 'C');
            $pdf->Cell(10, 4, $resident['age'] ?: 'N/A', 1, 0, 'C');
            $pdf->Cell(35, 4, $resident['contact_no'] ?: 'N/A', 1, 1, 'L');
            $counter++;
        }
    }
    
    addReportFooter($pdf);
    outputPDF($pdf, 'purok_report');
}

function addReportHeader($pdf, $settings, $title) {
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Republic of the Philippines', 0, 1, 'C');
    $pdf->Cell(0, 8, 'Province of ' . $settings['province'], 0, 1, 'C');
    $pdf->Cell(0, 8, 'Municipality of ' . $settings['municipality'], 0, 1, 'C');
    $pdf->Cell(0, 8, $settings['barangay_name'], 0, 1, 'C');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->Ln(5);
}

function addReportFooter($pdf) {
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 6, 'Generated on: ' . date('F d, Y h:i A'), 0, 1, 'L');
    $pdf->Cell(0, 6, 'BIMS - Barangay Information Management System', 0, 1, 'L');
}

function outputPDF($pdf, $filename) {
    $filename = $filename . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output('D', $filename);
}

$conn->close();
?>
