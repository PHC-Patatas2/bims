<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get parameters
$type = $_GET['type'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$size = (int)($_GET['size'] ?? 50);
$offset = ($page - 1) * $size;

// Get sorting parameters
$sort = $_GET['sort'] ?? 'id';
$dir = $_GET['dir'] ?? 'asc';

// Validate sort direction
$dir = in_array(strtolower($dir), ['asc', 'desc']) ? strtolower($dir) : 'asc';

// Get filter parameters
$filters = [];
foreach ($_GET as $key => $value) {
    if (strpos($key, 'filter[') === 0 && $value !== '') {
        $field = str_replace(['filter[', ']'], '', $key);
        $filters[$field] = $value;
    }
}

// Base query with purok join
$baseQuery = "FROM individuals i 
              LEFT JOIN purok p ON i.purok_id = p.id";

// Build WHERE clause based on type
$whereConditions = [];
$params = [];
$types = '';

switch ($type) {
    case 'male':
        $whereConditions[] = "i.gender = ?";
        $params[] = 'male';
        $types .= 's';
        break;
    case 'female':
        $whereConditions[] = "i.gender = ?";
        $params[] = 'female';
        $types .= 's';
        break;
    case 'voter':
        $whereConditions[] = "i.is_voter = 1";
        break;
    case 'minor':
        $whereConditions[] = "i.birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) <= 17";
        break;
    case 'senior':
        $whereConditions[] = "i.birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) >= 60";
        break;
    case 'pwd':
        $whereConditions[] = "i.is_pwd = 1";
        break;
    case '4ps':
        $whereConditions[] = "i.is_4ps = 1";
        break;
    case 'solo_parent':
        $whereConditions[] = "i.is_solo_parent = 1";
        break;
    default:
        // No additional filter for 'all' or unknown types
        break;
}

// Add filters to WHERE clause
foreach ($filters as $field => $value) {
    if ($field === 'purok') {
        $whereConditions[] = "p.name LIKE ?";
        $params[] = "%$value%";
        $types .= 's';
    } elseif (strpos($field, 'is_') === 0) {
        // Handle boolean filters
        if ($value === 'true' || $value === '1') {
            $whereConditions[] = "i.$field = 1";
        } elseif ($value === 'false' || $value === '0') {
            $whereConditions[] = "i.$field = 0";
        }
    } else {
        $whereConditions[] = "i.$field LIKE ?";
        $params[] = "%$value%";
        $types .= 's';
    }
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Count total records
$countQuery = "SELECT COUNT(*) as total $baseQuery $whereClause";
$countStmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Validate sort field to prevent SQL injection
$allowedSortFields = [
    'id', 'first_name', 'middle_name', 'last_name', 'suffix', 'gender', 
    'birthdate', 'house_no', 'street', 'purok', 'barangay', 'municipality', 
    'province', 'contact_no', 'is_voter', 'is_pwd', 'is_4ps', 'is_solo_parent'
];

if ($sort === 'purok') {
    $orderBy = "p.name $dir";
} elseif (in_array($sort, $allowedSortFields)) {
    $orderBy = "i.$sort $dir";
} else {
    $orderBy = "i.id $dir";
}

// Main data query
$dataQuery = "SELECT 
    i.id,
    i.first_name,
    i.middle_name,
    i.last_name,
    i.suffix,
    i.gender,
    i.birthdate,
    i.house_no,
    i.street,
    p.name as purok,
    i.barangay,
    i.municipality,
    i.province,
    i.contact_no,
    i.is_voter,
    CASE WHEN i.birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) >= 60 THEN 1 ELSE 0 END as is_senior,
    i.is_pwd,
    i.is_solo_parent as is_single_parent,
    0 as is_student,
    0 as is_employed,
    0 as is_out_of_school_youth
    $baseQuery 
    $whereClause 
    ORDER BY $orderBy 
    LIMIT ? OFFSET ?";

$dataStmt = $conn->prepare($dataQuery);
$allParams = $params;
$allParams[] = $size;
$allParams[] = $offset;
$allTypes = $types . 'ii';

if (!empty($allParams)) {
    $dataStmt->bind_param($allTypes, ...$allParams);
}

$dataStmt->execute();
$result = $dataStmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // Convert boolean fields to proper format
    $row['is_voter'] = (bool)$row['is_voter'];
    $row['is_senior'] = (bool)$row['is_senior'];
    $row['is_pwd'] = (bool)$row['is_pwd'];
    $row['is_single_parent'] = (bool)$row['is_single_parent'];
    $row['is_student'] = (bool)$row['is_student'];
    $row['is_employed'] = (bool)$row['is_employed'];
    $row['is_out_of_school_youth'] = (bool)$row['is_out_of_school_youth'];
    
    $data[] = $row;
}

$dataStmt->close();
$conn->close();

// Return response in Tabulator format
$response = [
    'data' => $data,
    'last_page' => ceil($totalRecords / $size),
    'total' => $totalRecords
];

header('Content-Type: application/json');
echo json_encode($response);
?>
