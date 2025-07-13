<?php
session_start();

// Set error reporting to log errors but not display them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header early
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    require_once 'config.php';
    require_once 'audit_logger.php'; // Include audit logging functions
    
    // Include FPDF - using local FPDF for better control and reliability
    if (!class_exists('FPDF')) {
        if (!file_exists(FPDF_PATH)) {
            throw new Exception('FPDF library not found at: ' . FPDF_PATH);
        }
        require_once FPDF_PATH;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'System initialization failed: ' . $e->getMessage()]);
    exit();
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
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
    
    // Record the certificate request using the certificate_requests table with complete information
    $stmt = $conn->prepare("INSERT INTO certificate_requests (individual_id, certificate_type, purpose, requested_at, status, certificate_number, processed_at, processed_by, requested_by) VALUES (?, ?, ?, NOW(), 'Issued', ?, NOW(), ?, ?)");
    $stmt->bind_param('isssii', $resident_id, $certificate_type, $purpose, $certificate_id, $user_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Log the certificate generation action
    $resident_name = trim($resident_data['first_name'] . ' ' . $resident_data['middle_name'] . ' ' . $resident_data['last_name'] . ' ' . $resident_data['suffix']);
    $additional_info = [
        'purpose' => $purpose ?? 'Not specified',
        'certificate_id' => $certificate_id
    ];
    logCertificateGeneration($_SESSION['user_id'], $certificate_type, $resident_id, $resident_name, $additional_info);
    
    echo json_encode([
        'success' => true,
        'certificate_id' => $certificate_id,
        'download_url' => "download_certificate.php?id=" . $certificate_id
    ]);
    
} catch (Exception $e) {
    error_log("Certificate generation error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Certificate generation failed: ' . $e->getMessage()]);
    exit();
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
    
    try {
        // Get system settings
        $settings = getSystemSettings();
        
        // Generate unique certificate ID first
        $certificate_id = 'CERT-' . strtoupper($type) . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Create PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // Content based on certificate type
        switch ($type) {
            case 'clearance':
                // Barangay Clearance has its own header format
                generateBarangayClearance($pdf, $resident_data, $age, $purpose, $settings, $certificate_id);
                break;
            case 'residency':
                // Certificate of Residency has its own header format like clearance
                generateResidencyCertificate($pdf, $resident_data, $age, $purpose, $settings);
                break;
            case 'indigency':
                // Certificate of Indigency has its own header format like clearance
                generateIndigencyCertificate($pdf, $resident_data, $age, $purpose, $settings);
                break;
            case 'first_time_job_seeker':
                // First Time Job Seeker has its own header format like clearance
                generateFirstTimeJobSeekerCertificate($pdf, $resident_data, $age, $purpose, $settings, $certificate_id);
                break;
            default:
                throw new Exception('Invalid certificate type: ' . $type);
        }
        
        // Footer - only add for certificates that don't have their own custom format
        if ($type !== 'clearance' && $type !== 'first_time_job_seeker' && $type !== 'residency' && $type !== 'indigency') {
            addCertificateFooter($pdf, $settings);
        }
        
        // Save PDF
        $filename = $certificate_id . '.pdf';
        $filepath = __DIR__ . '/certificates/' . $filename;
        
        // Create certificates directory if it doesn't exist
        if (!file_exists(__DIR__ . '/certificates/')) {
            if (!mkdir(__DIR__ . '/certificates/', 0755, true)) {
                throw new Exception('Failed to create certificates directory');
            }
        }
        
        // Check if directory is writable
        if (!is_writable(__DIR__ . '/certificates/')) {
            throw new Exception('Certificates directory is not writable');
        }
        
        $pdf->Output('F', $filepath);
        
        // Verify the file was created successfully
        if (!file_exists($filepath)) {
            throw new Exception('Failed to generate PDF file');
        }
        
        return $certificate_id;
        
    } catch (Exception $e) {
        error_log("PDF Generation Error: " . $e->getMessage());
        throw new Exception('PDF generation failed: ' . $e->getMessage());
    }
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
    
    try {
        $result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    } catch (Exception $e) {
        // Log error but continue with default settings
        error_log("System settings query error: " . $e->getMessage());
    }
    
    return $settings;
}

function addCertificateHeader($pdf, $settings) {
    // Logo
    $logoPath = $settings['barangay_logo_path'] ?? 'img/logo.png';
    if (file_exists($logoPath)) {
        try {
            $pdf->Image($logoPath, 20, 15, 25);
        } catch (Exception $e) {
            // Log error but continue without logo
            error_log("Logo loading error: " . $e->getMessage());
        }
    } else {
        // Log missing logo but continue
        error_log("Logo file not found: " . $logoPath);
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

function generateBarangayClearance($pdf, $resident_data, $age, $purpose, $settings, $certificate_id) {
    // Set 1-inch margins (25.4mm) on left and right
    $pdf->SetMargins(25.4, 10, 25.4);
    
    // Add logo on top left - positioned to balance with header text
    $logoPath = $settings['barangay_logo_path'] ?? 'img/logo.png';
    if (file_exists($logoPath)) {
        try {
            $pdf->Image($logoPath, 25.4, 11, 25);
        } catch (Exception $e) {
            // Log error but continue without logo
            error_log("Logo loading error: " . $e->getMessage());
        }
    } else {
        // Log missing logo but continue
        error_log("Logo file not found: " . $logoPath);
    }
    
    // Add header with formal address - positioned to align with logo
    $pdf->SetY(11); // Start at same Y position as logo
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 6, 'Republic of the Philippines', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Province of Bulacan', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Municipality of Calumpit', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Barangay Sucol', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Dividing line - adjusted for 1-inch margins
    $pdf->Line(25.4, $pdf->GetY(), 210 - 25.4, $pdf->GetY());
    $pdf->Ln(8);
    
    // Main title
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, 'B A R A N G A Y  C L E A R A N C E', 0, 1, 'C');
    $pdf->Ln(10);
    
    // TO WHOM IT MAY CONCERN
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'TO WHOM IT MAY CONCERN,', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Main certificate content with indentation and justified text
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    
    // First paragraph - with indent and justified text
    $first_paragraph = "          This is to certify that " . strtoupper($name) . ", " . $age . " years old, according to the existing record, " . $resident_data['civil_status'] . ", is a bonafide resident of Barangay Sucol, Calumpit, Bulacan and officially known to me as person of GOOD MORAL CHARACTER AND CONDUCT. I have no information and belief that he/she has committed nor been accused of any offense and or no crime has been committed which will blemish his/her personality thus no derogatory record been filed to this office.";
    
    $pdf->MultiCell(0, 6, $first_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Second paragraph - Purpose statement with indent and justified text
    $second_paragraph = "         This further certifies that the above person filed and requested for a BARANGAY CLEARANCE for " . ($purpose ?: "general purposes") . ".";
    
    $pdf->MultiCell(0, 6, $second_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Third paragraph - Legal compliance with indent and justified text
    $third_paragraph = "          This said application having been found to have no violation was GRANTED in compliance with Section 152 of R.A. 7160 otherwise known as the Local Government Code of the Philippines 1991.";
    
    $pdf->MultiCell(0, 6, $third_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Issued location statement with indent and justified text
    $fourth_paragraph = "          Issued this at the Office of the Punong Barangay, Sucol, Calumpit, Bulacan.";
    
    $pdf->MultiCell(0, 6, $fourth_paragraph, 0, 'J');
    
    // Move to bottom for signature section
    $pdf->SetY(200); // Position higher up on page
    
    // Punong Barangay signature section (bottom right) - adjusted for 1-inch margins
    $signature_x = 210 - 25.4 - 75; // Right margin - signature width
    $pdf->SetXY($signature_x, $pdf->GetY() + 5);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Issued this ' . date('jS') . ' day of ' . date('F Y'), 0, 1, 'C');
    $pdf->Ln(15);
    
    // Get Punong Barangay name
    global $conn;
    $punong_barangay_query = "SELECT 
        CONCAT(first_name, 
               CASE WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                    THEN CONCAT(' ', middle_initial, ' ') 
                    ELSE ' ' 
               END, 
               last_name,
               CASE WHEN suffix IS NOT NULL AND suffix != '' 
                    THEN CONCAT(' ', suffix) 
                    ELSE '' 
               END) as name 
        FROM barangay_officials 
        WHERE position = 'Punong Barangay' AND status = 'Active' 
        LIMIT 1";
    $punong_result = $conn->query($punong_barangay_query);
    $punong_name = "PUNONG BARANGAY";
    
    if ($punong_result && $punong_result->num_rows > 0) {
        $punong_data = $punong_result->fetch_assoc();
        $punong_name = strtoupper($punong_data['name']);
    }
    
    // Get Barangay Secretary name
    $secretary_query = "SELECT 
        CONCAT(first_name, 
               CASE WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                    THEN CONCAT(' ', middle_initial, ' ') 
                    ELSE ' ' 
               END, 
               last_name,
               CASE WHEN suffix IS NOT NULL AND suffix != '' 
                    THEN CONCAT(' ', suffix) 
                    ELSE '' 
               END) as name 
        FROM barangay_officials 
        WHERE position = 'Barangay Secretary' AND status = 'Active' 
        LIMIT 1";
    $secretary_result = $conn->query($secretary_query);
    $secretary_name = "BARANGAY SECRETARY";
    
    if ($secretary_result && $secretary_result->num_rows > 0) {
        $secretary_data = $secretary_result->fetch_assoc();
        $secretary_name = strtoupper($secretary_data['name']);
    }
    
    // Barangay Secretary signature section (bottom left)
    $secretary_x = 25.4; // Left margin
    $current_y = $pdf->GetY(); // Get current Y position to align both signatures
    $pdf->SetXY($secretary_x, $current_y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(75, 6, $secretary_name, 0, 1, 'C');
    $pdf->SetXY($secretary_x, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Barangay Secretary', 0, 1, 'C');
    
    // Punong Barangay signature section (bottom right) - same Y position as secretary
    $pdf->SetXY($signature_x, $current_y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(75, 6, $punong_name, 0, 1, 'C');
    $pdf->SetXY($signature_x, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Punong Barangay', 0, 1, 'C');
    
    // Add footer message at the bottom
    $pdf->SetY(270); // Position closer to signatures
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 4, 'Not valid without dry seal and signature.', 0, 1, 'C');
}

function generateResidencyCertificate($pdf, $resident_data, $age, $purpose, $settings) {
    // Set 1-inch margins (25.4mm) on left and right
    $pdf->SetMargins(25.4, 10, 25.4);
    
    // Add logo on top left - positioned to balance with header text
    $logoPath = $settings['barangay_logo_path'] ?? 'img/logo.png';
    if (file_exists($logoPath)) {
        try {
            $pdf->Image($logoPath, 25.4, 11, 25);
        } catch (Exception $e) {
            // Log error but continue without logo
            error_log("Logo loading error: " . $e->getMessage());
        }
    } else {
        // Log missing logo but continue
        error_log("Logo file not found: " . $logoPath);
    }
    
    // Add header with formal address - positioned to align with logo
    $pdf->SetY(11); // Start at same Y position as logo
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 6, 'Republic of the Philippines', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Province of Bulacan', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Municipality of Calumpit', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Barangay Sucol', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Dividing line - adjusted for 1-inch margins
    $pdf->Line(25.4, $pdf->GetY(), 210 - 25.4, $pdf->GetY());
    $pdf->Ln(8);
    
    // Main title
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, 'CERTIFICATE OF RESIDENCY', 0, 1, 'C');
    $pdf->Ln(10);
    
    // TO WHOM IT MAY CONCERN
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'TO WHOM IT MAY CONCERN,', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Main certificate content with indentation and justified text
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    
    // First paragraph - with indent and justified text
    $first_paragraph = "          This is to certify that " . strtoupper($name) . ", " . $age . " years old, " . $resident_data['civil_status'] . ", Filipino citizen, is a PERMANENT RESIDENT of Barangay Sucol, Calumpit, Bulacan.";
    
    $pdf->MultiCell(0, 6, $first_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Second paragraph - Residency statement with indent and justified text
    $second_paragraph = "         Based on records of this office, he has been residing at Barangay Sucol, Calumpit, Bulacan.";
    
    $pdf->MultiCell(0, 6, $second_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Third paragraph - Purpose statement with indent and justified text
    $third_paragraph = "          This CERTIFICATION is being issued upon the request of the above-named person for whatever legal purpose it may serve.";
    
    $pdf->MultiCell(0, 6, $third_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Fourth paragraph - Issued statement with indent and justified text
    $fourth_paragraph = "          Issued this " . date('jS') . " day of " . date('F Y') . " at Barangay Sucol, Calumpit, Bulacan.";
    
    $pdf->MultiCell(0, 6, $fourth_paragraph, 0, 'J');
    
    // Move to bottom for signature section
    $pdf->SetY(200); // Position higher up on page
    
    // Punong Barangay signature section (bottom right) - adjusted for 1-inch margins
    $signature_x = 210 - 25.4 - 75; // Right margin - signature width
    $pdf->SetXY($signature_x, $pdf->GetY() + 5);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Issued this ' . date('jS') . ' day of ' . date('F Y'), 0, 1, 'C');
    $pdf->Ln(15);
    
    // Get Punong Barangay name
    global $conn;
    $punong_barangay_query = "SELECT 
        CONCAT(first_name, 
               CASE WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                    THEN CONCAT(' ', middle_initial, ' ') 
                    ELSE ' ' 
               END, 
               last_name,
               CASE WHEN suffix IS NOT NULL AND suffix != '' 
                    THEN CONCAT(' ', suffix) 
                    ELSE '' 
               END) as name 
        FROM barangay_officials 
        WHERE position = 'Punong Barangay' AND status = 'Active' 
        LIMIT 1";
    $punong_result = $conn->query($punong_barangay_query);
    $punong_name = "PUNONG BARANGAY";
    
    if ($punong_result && $punong_result->num_rows > 0) {
        $punong_data = $punong_result->fetch_assoc();
        $punong_name = strtoupper($punong_data['name']);
    }
    
    // Get Barangay Secretary name
    $secretary_query = "SELECT 
        CONCAT(first_name, 
               CASE WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                    THEN CONCAT(' ', middle_initial, ' ') 
                    ELSE ' ' 
               END, 
               last_name,
               CASE WHEN suffix IS NOT NULL AND suffix != '' 
                    THEN CONCAT(' ', suffix) 
                    ELSE '' 
               END) as name 
        FROM barangay_officials 
        WHERE position = 'Barangay Secretary' AND status = 'Active' 
        LIMIT 1";
    $secretary_result = $conn->query($secretary_query);
    $secretary_name = "BARANGAY SECRETARY";
    
    if ($secretary_result && $secretary_result->num_rows > 0) {
        $secretary_data = $secretary_result->fetch_assoc();
        $secretary_name = strtoupper($secretary_data['name']);
    }
    
    // Barangay Secretary signature section (bottom left)
    $secretary_x = 25.4; // Left margin
    $current_y = $pdf->GetY(); // Get current Y position to align both signatures
    $pdf->SetXY($secretary_x, $current_y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(75, 6, $secretary_name, 0, 1, 'C');
    $pdf->SetXY($secretary_x, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Barangay Secretary', 0, 1, 'C');
    
    // Punong Barangay signature section (bottom right) - same Y position as secretary
    $pdf->SetXY($signature_x, $current_y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(75, 6, $punong_name, 0, 1, 'C');
    $pdf->SetXY($signature_x, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Punong Barangay', 0, 1, 'C');
    
    // Add footer message at the bottom
    $pdf->SetY(270); // Position closer to signatures
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 4, 'Not valid without dry seal and signature.', 0, 1, 'C');
}

function generateIndigencyCertificate($pdf, $resident_data, $age, $purpose, $settings) {
    // Set 1-inch margins (25.4mm) on left and right
    $pdf->SetMargins(25.4, 10, 25.4);
    
    // Add logo on top left - positioned to balance with header text
    $logoPath = $settings['barangay_logo_path'] ?? 'img/logo.png';
    if (file_exists($logoPath)) {
        try {
            $pdf->Image($logoPath, 25.4, 11, 25);
        } catch (Exception $e) {
            // Log error but continue without logo
            error_log("Logo loading error: " . $e->getMessage());
        }
    } else {
        // Log missing logo but continue
        error_log("Logo file not found: " . $logoPath);
    }
    
    // Add header with formal address - positioned to align with logo
    $pdf->SetY(11); // Start at same Y position as logo
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 6, 'Republic of the Philippines', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Province of Bulacan', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Municipality of Calumpit', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Barangay Sucol', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Dividing line - adjusted for 1-inch margins
    $pdf->Line(25.4, $pdf->GetY(), 210 - 25.4, $pdf->GetY());
    $pdf->Ln(8);
    
    // Main title
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, 'CERTIFICATE OF INDIGENCY', 0, 1, 'C');
    $pdf->Ln(10);
    
    // TO WHOM IT MAY CONCERN
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'TO WHOM IT MAY CONCERN,', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Main certificate content with indentation and justified text
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    
    // First paragraph - with indent and justified text
    $first_paragraph = "          This is to certify that " . strtoupper($name) . ", of legal age, " . $resident_data['civil_status'] . ", Filipino citizen, is a resident of this Barangay and is one of the INDIGENTS in our barangay.";
    
    $pdf->MultiCell(0, 6, $first_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Second paragraph - Purpose statement with indent and justified text
    $second_paragraph = "         This certification is being issued upon the request of the above-named person for whatever legal purpose it may serve her best.";
    
    $pdf->MultiCell(0, 6, $second_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Third paragraph - Issued statement with indent and justified text
    $third_paragraph = "          Issued this " . date('jS') . " day of " . date('F Y') . " at Barangay Sucol, Calumpit, Bulacan.";
    
    $pdf->MultiCell(0, 6, $third_paragraph, 0, 'J');
    
    // Move to bottom for signature section
    $pdf->SetY(200); // Position higher up on page
    
    // Punong Barangay signature section (bottom right) - adjusted for 1-inch margins
    $signature_x = 210 - 25.4 - 75; // Right margin - signature width
    $pdf->SetXY($signature_x, $pdf->GetY() + 5);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Issued this ' . date('jS') . ' day of ' . date('F Y'), 0, 1, 'C');
    $pdf->Ln(15);
    
    // Get Punong Barangay name
    global $conn;
    $punong_barangay_query = "SELECT 
        CONCAT(first_name, 
               CASE WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                    THEN CONCAT(' ', middle_initial, ' ') 
                    ELSE ' ' 
               END, 
               last_name,
               CASE WHEN suffix IS NOT NULL AND suffix != '' 
                    THEN CONCAT(' ', suffix) 
                    ELSE '' 
               END) as name 
        FROM barangay_officials 
        WHERE position = 'Punong Barangay' AND status = 'Active' 
        LIMIT 1";
    $punong_result = $conn->query($punong_barangay_query);
    $punong_name = "PUNONG BARANGAY";
    
    if ($punong_result && $punong_result->num_rows > 0) {
        $punong_data = $punong_result->fetch_assoc();
        $punong_name = strtoupper($punong_data['name']);
    }
    
    // Get Barangay Secretary name
    $secretary_query = "SELECT 
        CONCAT(first_name, 
               CASE WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                    THEN CONCAT(' ', middle_initial, ' ') 
                    ELSE ' ' 
               END, 
               last_name,
               CASE WHEN suffix IS NOT NULL AND suffix != '' 
                    THEN CONCAT(' ', suffix) 
                    ELSE '' 
               END) as name 
        FROM barangay_officials 
        WHERE position = 'Barangay Secretary' AND status = 'Active' 
        LIMIT 1";
    $secretary_result = $conn->query($secretary_query);
    $secretary_name = "BARANGAY SECRETARY";
    
    if ($secretary_result && $secretary_result->num_rows > 0) {
        $secretary_data = $secretary_result->fetch_assoc();
        $secretary_name = strtoupper($secretary_data['name']);
    }
    
    // Barangay Secretary signature section (bottom left)
    $secretary_x = 25.4; // Left margin
    $current_y = $pdf->GetY(); // Get current Y position to align both signatures
    $pdf->SetXY($secretary_x, $current_y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(75, 6, $secretary_name, 0, 1, 'C');
    $pdf->SetXY($secretary_x, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Barangay Secretary', 0, 1, 'C');
    
    // Punong Barangay signature section (bottom right) - same Y position as secretary
    $pdf->SetXY($signature_x, $current_y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(75, 6, $punong_name, 0, 1, 'C');
    $pdf->SetXY($signature_x, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Punong Barangay', 0, 1, 'C');
    
    // Add footer message at the bottom
    $pdf->SetY(270); // Position closer to signatures
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 4, 'Not valid without dry seal and signature.', 0, 1, 'C');
}

function generateFirstTimeJobSeekerCertificate($pdf, $resident_data, $age, $purpose, $settings, $certificate_id) {
    // Set 1-inch margins (25.4mm) on left and right
    $pdf->SetMargins(25.4, 10, 25.4);
    
    // Add logo on top left - positioned to balance with header text
    $logoPath = $settings['barangay_logo_path'] ?? 'img/logo.png';
    if (file_exists($logoPath)) {
        try {
            $pdf->Image($logoPath, 25.4, 11, 25);
        } catch (Exception $e) {
            // Log error but continue without logo
            error_log("Logo loading error: " . $e->getMessage());
        }
    } else {
        // Log missing logo but continue
        error_log("Logo file not found: " . $logoPath);
    }
    
    // Add header with formal address - positioned to align with logo
    $pdf->SetY(11); // Start at same Y position as logo
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 6, 'Republic of the Philippines', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Province of Bulacan', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Municipality of Calumpit', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Barangay Sucol', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Dividing line - adjusted for 1-inch margins
    $pdf->Line(25.4, $pdf->GetY(), 210 - 25.4, $pdf->GetY());
    $pdf->Ln(8);
    
    // Main title
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, 'FIRST TIME JOB SEEKER CERTIFICATE', 0, 1, 'C');
    $pdf->Ln(10);
    
    // TO WHOM IT MAY CONCERN
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'TO WHOM IT MAY CONCERN,', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Main certificate content with indentation and justified text
    $name = trim($resident_data['first_name'] . ' ' . ($resident_data['middle_name'] ?? '') . ' ' . $resident_data['last_name'] . ' ' . ($resident_data['suffix'] ?? ''));
    $gender_prefix = ($resident_data['gender'] === 'Female') ? 'Ms.' : 'Mr.';
    
    // First paragraph - with indent and justified text
    $first_paragraph = "          This is to certify that " . $gender_prefix . " " . strtoupper($name) . ", a resident of Barangay Sucol, Calumpit, Bulacan, for " . $age . " years, is a qualified availee of RA 11261 or the First Time Job Seekers Act of 2019.";
    
    $pdf->MultiCell(0, 6, $first_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Second paragraph - Oath statement with indent and justified text
    $second_paragraph = "         I further certify that the holder/bearer was informed of his/her rights, including the duties and responsibilities accorded by RA 11261 through the Oath of Undertaking he/she has signed and executed in the presence of our Barangay Official/s.";
    
    $pdf->MultiCell(0, 6, $second_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Third paragraph - Signing statement with indent and justified text
    $third_paragraph = "          Signed this " . date('jS') . " day of " . date('F Y') . " in Barangay Sucol, Calumpit, Bulacan.";
    
    $pdf->MultiCell(0, 6, $third_paragraph, 0, 'J');
    $pdf->Ln(5);
    
    // Fourth paragraph - Validity statement with indent and justified text
    $fourth_paragraph = "          This certification is valid only one (1) year from the date of issuance.";
    
    $pdf->MultiCell(0, 6, $fourth_paragraph, 0, 'J');
    
    // Move to bottom for signature section
    $pdf->SetY(200); // Position higher up on page
    
    // Punong Barangay signature section (bottom right) - adjusted for 1-inch margins
    $signature_x = 210 - 25.4 - 75; // Right margin - signature width
    $pdf->SetXY($signature_x, $pdf->GetY() + 5);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Issued this ' . date('jS') . ' day of ' . date('F Y'), 0, 1, 'C');
    $pdf->Ln(15);
    
    // Get Punong Barangay name
    global $conn;
    $punong_barangay_query = "SELECT 
        CONCAT(first_name, 
               CASE WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                    THEN CONCAT(' ', middle_initial, ' ') 
                    ELSE ' ' 
               END, 
               last_name,
               CASE WHEN suffix IS NOT NULL AND suffix != '' 
                    THEN CONCAT(' ', suffix) 
                    ELSE '' 
               END) as name 
        FROM barangay_officials 
        WHERE position = 'Punong Barangay' AND status = 'Active' 
        LIMIT 1";
    $punong_result = $conn->query($punong_barangay_query);
    $punong_name = "PUNONG BARANGAY";
    
    if ($punong_result && $punong_result->num_rows > 0) {
        $punong_data = $punong_result->fetch_assoc();
        $punong_name = strtoupper($punong_data['name']);
    }
    
    // Get Barangay Secretary name
    $secretary_query = "SELECT 
        CONCAT(first_name, 
               CASE WHEN middle_initial IS NOT NULL AND middle_initial != '' 
                    THEN CONCAT(' ', middle_initial, ' ') 
                    ELSE ' ' 
               END, 
               last_name,
               CASE WHEN suffix IS NOT NULL AND suffix != '' 
                    THEN CONCAT(' ', suffix) 
                    ELSE '' 
               END) as name 
        FROM barangay_officials 
        WHERE position = 'Barangay Secretary' AND status = 'Active' 
        LIMIT 1";
    $secretary_result = $conn->query($secretary_query);
    $secretary_name = "BARANGAY SECRETARY";
    
    if ($secretary_result && $secretary_result->num_rows > 0) {
        $secretary_data = $secretary_result->fetch_assoc();
        $secretary_name = strtoupper($secretary_data['name']);
    }
    
    // Barangay Secretary signature section (bottom left)
    $secretary_x = 25.4; // Left margin
    $current_y = $pdf->GetY(); // Get current Y position to align both signatures
    $pdf->SetXY($secretary_x, $current_y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(75, 6, $secretary_name, 0, 1, 'C');
    $pdf->SetXY($secretary_x, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Barangay Secretary', 0, 1, 'C');
    
    // Punong Barangay signature section (bottom right) - same Y position as secretary
    $pdf->SetXY($signature_x, $current_y);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(75, 6, $punong_name, 0, 1, 'C');
    $pdf->SetXY($signature_x, $pdf->GetY());
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(75, 6, 'Punong Barangay', 0, 1, 'C');
    
    // Add footer message at the bottom
    $pdf->SetY(270); // Position closer to signatures
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 4, 'Not valid without dry seal and signature.', 0, 1, 'C');
}

?>
