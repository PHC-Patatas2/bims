<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'config.php';

// Include FPDF - using local FPDF for better control and reliability
if (!class_exists('FPDF')) {
    require_once FPDF_PATH;
}

header('Content-Type: application/json');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate required fields
if (!isset($_POST['certificate_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Certificate type is required']);
    exit();
}

$certificate_type = $_POST['certificate_type'];
$resident_id = isset($_POST['resident_id']) ? intval($_POST['resident_id']) : null;
$purpose = $_POST['purpose'] ?? 'Certificate request';

// Get resident data
$resident_data = null;
if ($resident_id) {
    // Existing resident from individuals table
    $stmt = $conn->prepare("SELECT i.*, p.name AS purok_name FROM individuals i LEFT JOIN purok p ON i.purok_id = p.id WHERE i.id = ?");
    $stmt->bind_param('i', $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident_data = $result->fetch_assoc();
    $stmt->close();
    
    if (!$resident_data) {
        http_response_code(404);
        echo json_encode(['error' => 'Resident not found']);
        exit();
    }
} else {
    // New resident (manual entry) - add to individuals table first
    $resident_data = [
        'first_name' => $_POST['first_name'] ?? '',
        'middle_name' => $_POST['middle_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'suffix' => $_POST['suffix'] ?? '',
        'birthdate' => $_POST['birthdate'] ?? '',
        'gender' => $_POST['gender'] ?? '',
        'civil_status' => $_POST['civil_status'] ?? '',
        'purok_name' => $_POST['purok'] ?? '',
        'blood_type' => $_POST['blood_type'] ?? 'Unknown',
        'religion' => 'Unknown',
        'is_pwd' => 0,
        'is_voter' => 0,
        'is_4ps' => 0,
        'is_pregnant' => 0,
        'is_solo_parent' => 0
    ];
    
    // Validate required fields for manual entry
    if (empty($resident_data['first_name']) || empty($resident_data['last_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'First name and last name are required']);
        exit();
    }
    
    // Add to individuals table
    $purok_id = 1; // Default purok, you might want to get this from a purok table
    
    $stmt = $conn->prepare("INSERT INTO individuals (first_name, middle_name, last_name, suffix, gender, birthdate, civil_status, blood_type, religion, is_pwd, is_voter, is_4ps, is_pregnant, is_solo_parent, purok_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('sssssssssiiiii', 
        $resident_data['first_name'],
        $resident_data['middle_name'],
        $resident_data['last_name'],
        $resident_data['suffix'],
        $resident_data['gender'],
        $resident_data['birthdate'],
        $resident_data['civil_status'],
        $resident_data['blood_type'],
        $resident_data['religion'],
        $resident_data['is_pwd'],
        $resident_data['is_voter'],
        $resident_data['is_4ps'],
        $resident_data['is_pregnant'],
        $resident_data['is_solo_parent'],
        $purok_id
    );
    
    if ($stmt->execute()) {
        $resident_id = $conn->insert_id;
        $resident_data['id'] = $resident_id;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to register new resident']);
        exit();
    }
    $stmt->close();
}

// Calculate age
$age = '';
if (!empty($resident_data['birthdate'])) {
    $birthDate = new DateTime($resident_data['birthdate']);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
}

// Validate required information based on certificate type
$validation_result = validateResidentData($certificate_type, $resident_data, $age);
if (!$validation_result['valid']) {
    http_response_code(400);
    echo json_encode(['error' => $validation_result['message']]);
    exit();
}

try {
    // Generate certificate
    $certificate_id = generateCertificate($certificate_type, $resident_data, $age, $purpose);
    
    // Record the certificate request using the certificate_requests table
    $stmt = $conn->prepare("INSERT INTO certificate_requests (individual_id, certificate_type, requested_at) VALUES (?, ?, NOW())");
    $stmt->bind_param('is', $resident_id, $certificate_type);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'certificate_id' => $certificate_id,
        'download_url' => "download_certificate.php?id=" . $certificate_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Certificate generation failed: ' . $e->getMessage()]);
}

$conn->close();

function validateResidentData($certificate_type, $resident_data, $age) {
    $missing_fields = [];
    
    // Common required fields for all certificates
    if (empty($resident_data['first_name'])) $missing_fields[] = 'First Name';
    if (empty($resident_data['last_name'])) $missing_fields[] = 'Last Name';
    if (empty($resident_data['birthdate'])) $missing_fields[] = 'Birthdate';
    if (empty($resident_data['gender'])) $missing_fields[] = 'Gender';
    if (empty($resident_data['civil_status'])) $missing_fields[] = 'Civil Status';
    
    // Certificate-specific validations
    switch ($certificate_type) {
        case 'first_time_job_seeker':
            if ($age < 15 || $age > 30) {
                return ['valid' => false, 'message' => 'First Time Job Seeker certificate is only for ages 15-30. Current age: ' . $age];
            }
            break;
        case 'barangay_id':
            // No additional validations for now
            break;
        case 'clearance':
        case 'residency':
        case 'indigency':
            // Standard validations apply
            break;
    }
    
    if (!empty($missing_fields)) {
        return ['valid' => false, 'message' => 'Missing required information: ' . implode(', ', $missing_fields)];
    }
    
    return ['valid' => true, 'message' => ''];
}

function generateCertificate($type, $resident_data, $age, $purpose) {
    global $conn;
    
    // Get system settings
    $settings = getSystemSettings();
    
    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Header
    addCertificateHeader($pdf, $settings);
    
    // Content based on certificate type
    switch ($type) {
        case 'clearance':
            generateBarangayClearance($pdf, $resident_data, $age, $purpose, $settings);
            break;
        case 'residency':
            generateResidencyCertificate($pdf, $resident_data, $age, $purpose, $settings);
            break;
        case 'indigency':
            generateIndigencyCertificate($pdf, $resident_data, $age, $purpose, $settings);
            break;
        case 'first_time_job_seeker':
            generateFirstTimeJobSeekerCertificate($pdf, $resident_data, $age, $purpose, $settings);
            break;
        case 'barangay_id':
            generateBarangayID($pdf, $resident_data, $age, $settings);
            break;
        default:
            throw new Exception('Invalid certificate type');
    }
    
    // Footer
    addCertificateFooter($pdf, $settings);
    
    // Generate unique certificate ID
    $certificate_id = 'CERT-' . strtoupper($type) . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Save PDF
    $filename = $certificate_id . '.pdf';
    $filepath = __DIR__ . '/certificates/' . $filename;
    
    // Create certificates directory if it doesn't exist
    if (!file_exists(__DIR__ . '/certificates/')) {
        mkdir(__DIR__ . '/certificates/', 0755, true);
    }
    
    $pdf->Output('F', $filepath);
    
    return $certificate_id;
}

function getSystemSettings() {
    global $conn;
    
    $settings = [
        'barangay_name' => 'Barangay Sample',
        'municipality' => 'Sample Municipality',
        'province' => 'Sample Province',
        'barangay_logo_path' => 'img/logo.png',
        'captain_name' => 'Barangay Captain',
        'captain_title' => 'Punong Barangay'
    ];
    
    $result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $settings;
}

function addCertificateHeader($pdf, $settings) {
    // Logo
    if (file_exists($settings['barangay_logo_path'])) {
        $pdf->Image($settings['barangay_logo_path'], 20, 15, 25);
    }
    
    // Header text
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetY(15);
    $pdf->Cell(0, 8, 'REPUBLIC OF THE PHILIPPINES', 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 6, strtoupper($settings['province'] ?? 'PROVINCE'), 0, 1, 'C');
    $pdf->Cell(0, 6, strtoupper($settings['municipality'] ?? 'MUNICIPALITY'), 0, 1, 'C');
    $pdf->Cell(0, 6, strtoupper($settings['barangay_name'] ?? 'BARANGAY'), 0, 1, 'C');
    
    $pdf->Ln(10);
    
    // Horizontal line
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    $pdf->Ln(10);
}

function addCertificateFooter($pdf, $settings) {
    $pdf->Ln(20);
    
    // Signature section
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'Prepared by:', 0, 1, 'L');
    $pdf->Ln(5);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, strtoupper($settings['captain_name'] ?? 'BARANGAY CAPTAIN'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, $settings['captain_title'] ?? 'Punong Barangay', 0, 1, 'L');
    
    $pdf->Ln(10);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 4, 'Date Issued: ' . date('F d, Y'), 0, 1, 'L');
}

function generateBarangayClearance($pdf, $resident_data, $age, $purpose, $settings) {
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'BARANGAY CLEARANCE', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'TO WHOM IT MAY CONCERN:', 0, 1, 'L');
    $pdf->Ln(5);
    
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    
    $text = "This is to certify that " . strtoupper($name) . ", " . $age . " years old, " . 
            $resident_data['civil_status'] . ", " . $resident_data['gender'] . ", is a bonafide resident of " .
            ($resident_data['purok_name'] ?? 'this barangay') . ", " . ($settings['barangay_name'] ?? 'Barangay') . 
            ", " . ($settings['municipality'] ?? 'Municipality') . ", " . ($settings['province'] ?? 'Province') . ".";
    
    $pdf->MultiCell(0, 6, $text, 0, 'J');
    $pdf->Ln(5);
    
    if ($purpose && $purpose !== 'Certificate request') {
        $pdf->MultiCell(0, 6, "This certification is issued for " . $purpose . ".", 0, 'J');
        $pdf->Ln(5);
    }
    
    $pdf->MultiCell(0, 6, "This person is known to me to be of good moral character and a law-abiding citizen in the community.", 0, 'J');
}

function generateResidencyCertificate($pdf, $resident_data, $age, $purpose, $settings) {
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'CERTIFICATE OF RESIDENCY', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'TO WHOM IT MAY CONCERN:', 0, 1, 'L');
    $pdf->Ln(5);
    
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    
    $text = "This is to certify that " . strtoupper($name) . ", " . $age . " years old, " . 
            $resident_data['civil_status'] . ", " . $resident_data['gender'] . ", is a bona fide resident of " .
            ($resident_data['purok_name'] ?? 'this barangay') . ", " . ($settings['barangay_name'] ?? 'Barangay') . 
            ", " . ($settings['municipality'] ?? 'Municipality') . ", " . ($settings['province'] ?? 'Province') . 
            " and has been residing in this place for a considerable length of time.";
    
    $pdf->MultiCell(0, 6, $text, 0, 'J');
    $pdf->Ln(5);
    
    if ($purpose && $purpose !== 'Certificate request') {
        $pdf->MultiCell(0, 6, "This certification is issued for " . $purpose . ".", 0, 'J');
    }
}

function generateIndigencyCertificate($pdf, $resident_data, $age, $purpose, $settings) {
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'CERTIFICATE OF INDIGENCY', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'TO WHOM IT MAY CONCERN:', 0, 1, 'L');
    $pdf->Ln(5);
    
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    
    $text = "This is to certify that " . strtoupper($name) . ", " . $age . " years old, " . 
            $resident_data['civil_status'] . ", " . $resident_data['gender'] . ", is a bonafide resident of " .
            ($resident_data['purok_name'] ?? 'this barangay') . ", " . ($settings['barangay_name'] ?? 'Barangay') . 
            ", " . ($settings['municipality'] ?? 'Municipality') . ", " . ($settings['province'] ?? 'Province') . 
            " and that he/she belongs to an indigent family in this locality.";
    
    $pdf->MultiCell(0, 6, $text, 0, 'J');
    $pdf->Ln(5);
    
    if ($purpose && $purpose !== 'Certificate request') {
        $pdf->MultiCell(0, 6, "This certification is issued for " . $purpose . ".", 0, 'J');
    }
}

function generateFirstTimeJobSeekerCertificate($pdf, $resident_data, $age, $purpose, $settings) {
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'FIRST TIME JOB SEEKER CERTIFICATE', 0, 1, 'C');
    $pdf->Ln(10);
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'TO WHOM IT MAY CONCERN:', 0, 1, 'L');
    $pdf->Ln(5);
    
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    
    $text = "This is to certify that " . strtoupper($name) . ", " . $age . " years old, " . 
            $resident_data['civil_status'] . ", " . $resident_data['gender'] . ", is a bonafide resident of " .
            ($resident_data['purok_name'] ?? 'this barangay') . ", " . ($settings['barangay_name'] ?? 'Barangay') . 
            ", " . ($settings['municipality'] ?? 'Municipality') . ", " . ($settings['province'] ?? 'Province') . 
            " and that he/she is actively looking for work/employment and has not been previously employed.";
    
    $pdf->MultiCell(0, 6, $text, 0, 'J');
    $pdf->Ln(5);
    
    $pdf->MultiCell(0, 6, "This certification is being issued to support his/her application for employment and to avail of the benefits provided under R.A. 11261 or the First Time Jobseekers Assistance Act of 2019.", 0, 'J');
}

function generateBarangayID($pdf, $resident_data, $age, $settings) {
    // Different layout for ID format
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'BARANGAY IDENTIFICATION CARD', 0, 1, 'C');
    $pdf->Ln(10);
    
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 8, 'Name:', 0, 0, 'L');
    $pdf->Cell(0, 8, strtoupper($name), 0, 1, 'L');
    
    $pdf->Cell(50, 8, 'Age:', 0, 0, 'L');
    $pdf->Cell(0, 8, $age . ' years old', 0, 1, 'L');
    
    $pdf->Cell(50, 8, 'Gender:', 0, 0, 'L');
    $pdf->Cell(0, 8, $resident_data['gender'], 0, 1, 'L');
    
    $pdf->Cell(50, 8, 'Address:', 0, 0, 'L');
    $pdf->Cell(0, 8, ($resident_data['purok_name'] ?? '') . ', ' . ($settings['barangay_name'] ?? 'Barangay'), 0, 1, 'L');
    
    if (!empty($resident_data['birthdate'])) {
        $pdf->Cell(50, 8, 'Birthdate:', 0, 0, 'L');
        $pdf->Cell(0, 8, date('F d, Y', strtotime($resident_data['birthdate'])), 0, 1, 'L');
    }
    
    $pdf->Cell(50, 8, 'Civil Status:', 0, 0, 'L');
    $pdf->Cell(0, 8, $resident_data['civil_status'], 0, 1, 'L');
    
    if (!empty($resident_data['blood_type'])) {
        $pdf->Cell(50, 8, 'Blood Type:', 0, 0, 'L');
        $pdf->Cell(0, 8, $resident_data['blood_type'], 0, 1, 'L');
    }
}
?>
