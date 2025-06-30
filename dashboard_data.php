<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// This script returns JSON data for dashboard card tables based on a 'type' GET parameter
require_once 'config.php'; // Defines DB_HOST, DB_USER, DB_PASS, DB_NAME

// Database connection using PDO
$charset = 'utf8mb4';
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) { // Corrected catch block
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Helper functions (can remain as they are, not DB specific)
function formatBoolean($val) {
    return $val == 1 ? '✓' : ($val == 0 ? '✗' : '-');
}

function middle_initial($middle_name) {
    if (!$middle_name || $middle_name === '-') return '-';
    $parts = preg_split('/\s+/', trim($middle_name));
    $initials = array_map(function($p) { return strtoupper($p[0]); }, array_filter($parts));
    return $initials ? implode('', $initials) . '.' : '-';
}

function dash($val) {
    return (isset($val) && $val !== '' && $val !== null) ? $val : '-';
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
// $data = []; // This variable is not used before being overwritten by $stmtData->fetchAll()

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$size = isset($_GET['size']) ? (int)$_GET['size'] : 50; // Default size

$filters = isset($_GET['filter']) && is_array($_GET['filter']) ? $_GET['filter'] : [];
$sorters = isset($_GET['sorter']) && is_array($_GET['sorter']) ? $_GET['sorter'] : [];

if (!$type) {
    http_response_code(400);
    echo json_encode(['error' => 'Type parameter is required.']);
    exit;
}

// Base query parts
// $baseSql = "FROM residents"; // Not used directly, logic incorporated into $finalWhereClause construction
$pdoParams = []; // Renamed from $params to avoid confusion with GET $params, and for PDO context

// Determine the specific query based on 'type'
$typeWhereClause = "";
switch ($type) {
    case 'all_residents':
        break;
    case 'members_with_family_id':
        $typeWhereClause = "family_id IS NOT NULL";
        break;
    case 'male':
        $typeWhereClause = "gender = 'male'"; // These are safe as $type is from a controlled set
        break;
    case 'female':
        $typeWhereClause = "gender = 'female'";
        break;
    case 'voter':
        $typeWhereClause = "is_voter = 1";
        break;
    case '4ps':
        $typeWhereClause = "is_4ps = 1";
        break;
    case 'senior':
        $typeWhereClause = "birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60";
        break;
    case 'pwd':
        $typeWhereClause = "is_pwd = 1";
        break;
    case 'solo_parent':
        $typeWhereClause = "is_solo_parent = 1";
        break;
    case 'newborn':
        $typeWhereClause = "birthdate IS NOT NULL AND DATEDIFF(CURDATE(), birthdate) >= 0 AND DATEDIFF(CURDATE(), birthdate) < 28";
        break;
    case 'minor':
        $typeWhereClause = "birthdate IS NOT NULL AND TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 18 AND is_voter = 0";
        break;
    default:
        // Defaulting to no specific type filter if not matched.
        break;
}

$whereClauses = [];
if (!empty($typeWhereClause)) {
    $whereClauses[] = "(" . $typeWhereClause . ")"; // Wrap type clause for clarity with AND
}

// Apply Tabulator filters
$filterSubClauses = [];
foreach ($filters as $key => $filter) {
    if (!isset($filter['field']) || !isset($filter['type']) || !array_key_exists('value', $filter)) continue;

    $field = $filter['field'];
    $filterType = $filter['type'];
    $value = $filter['value'];

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
        continue; 
    }

    $paramName = ":filter_value_" . $key; // PDO named placeholder

    // Special handling for computed 'age' field
    $sqlField = ($field === 'age') ? 'TIMESTAMPDIFF(YEAR, birthdate, CURDATE())' : ("`" . $field . "`");

    switch ($filterType) {
        case 'like':
            $filterSubClauses[] = $sqlField . " LIKE " . $paramName;
            $pdoParams[$paramName] = '%' . $value . '%';
            break;
        case 'ilike': // Case-insensitive LIKE for MySQL
            $filterSubClauses[] = "LOWER(" . $sqlField . ") LIKE LOWER(" . $paramName . ")";
            $pdoParams[$paramName] = '%' . strtolower($value) . '%';
            break;
        case '=':
            $filterSubClauses[] = $sqlField . " = " . $paramName;
            $pdoParams[$paramName] = $value;
            break;
        case '!=':
            $filterSubClauses[] = $sqlField . " != " . $paramName;
            $pdoParams[$paramName] = $value;
            break;
        case '<':
            $filterSubClauses[] = $sqlField . " < " . $paramName;
            $pdoParams[$paramName] = $value;
            break;
        case '<=':
            $filterSubClauses[] = $sqlField . " <= " . $paramName;
            $pdoParams[$paramName] = $value;
            break;
        case '>':
            $filterSubClauses[] = $sqlField . " > " . $paramName;
            $pdoParams[$paramName] = $value;
            break;
        case '>=':
            $filterSubClauses[] = $sqlField . " >= " . $paramName;
            $pdoParams[$paramName] = $value;
            break;
        case 'tickCross': 
             if ($value === true || $value === 'true' || $value === 1 || $value === '1') {
                $filterSubClauses[] = $sqlField . " = 1"; // No placeholder needed for literal 1/0
            } elseif ($value === false || $value === 'false' || $value === 0 || $value === '0') {
                $filterSubClauses[] = $sqlField . " = 0";
            } 
            break;
    }
}

if (!empty($filterSubClauses)) {
    $whereClauses[] = "(" . implode(" AND ", $filterSubClauses) . ")";
}

$finalWhereClause = "";
if (!empty($whereClauses)) {
    $finalWhereClause = " WHERE " . implode(" AND ", $whereClauses);
}

// Sorters
$orderByClauses = [];
if (!empty($sorters)) {
    foreach ($sorters as $sorter) {
        if (!isset($sorter['field']) || !isset($sorter['dir'])) continue;
        $sortField = $sorter['field'];
        $sortDir = strtoupper($sorter['dir']) === 'DESC' ? 'DESC' : 'ASC'; 

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $sortField)) {
            continue; 
        }
        $orderByClauses[] = "`" . $sortField . "` " . $sortDir;
    }
}
$orderBySql = "";
if (!empty($orderByClauses)) {
    $orderBySql = " ORDER BY " . implode(", ", $orderByClauses);
} else {
    $orderBySql = " ORDER BY id ASC"; 
}

// Get total records for pagination
$countSql = "SELECT COUNT(*) FROM individuals" . $finalWhereClause;
$stmtTotal = $pdo->prepare($countSql);
$stmtTotal->execute($pdoParams); // Pass filter parameters for accurate count
$totalRecords = $stmtTotal->fetchColumn();
$lastPage = ($size > 0 && $totalRecords > 0) ? ceil($totalRecords / $size) : 1;

if ($page > $lastPage && $totalRecords > 0) { // Prevent page from being out of bounds
    $page = $lastPage;
}
$offset = ($page - 1) * $size;


// Final data query
// $sql = "SELECT *, DATE_FORMAT(birthdate, '%Y-%m-%d') AS birthdate, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age FROM individuals" . $finalWhereClause . $orderBySql . " LIMIT :limit OFFSET :offset";

// Updated SQL to format birthdate as MM/DD/YYYY directly
$sql = "SELECT *, DATE_FORMAT(birthdate, '%m/%d/%Y') AS birthdate, TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) AS age FROM individuals" . $finalWhereClause . $orderBySql . " LIMIT :limit OFFSET :offset";


$stmtData = $pdo->prepare($sql);

// Bind parameters for the data query
// Filter parameters are in $pdoParams
foreach ($pdoParams as $paramName => $paramValue) {
    $stmtData->bindValue($paramName, $paramValue); // Use bindValue for simplicity here
}
// Bind LIMIT and OFFSET
$stmtData->bindParam(':limit', $size, PDO::PARAM_INT);
$stmtData->bindParam(':offset', $offset, PDO::PARAM_INT);

$stmtData->execute();
$data = $stmtData->fetchAll(); // PDOStatement::fetchAll()

// Prepare response for Tabulator
$response = [
    "last_page" => (int)$lastPage,
    "data" => $data,
    // Debug info for troubleshooting advanced search
    "debug_sql" => $sql,
    "debug_params" => $pdoParams,
    "debug_where" => $finalWhereClause,
];

header('Content-Type: application/json');
echo json_encode($response);

?>
