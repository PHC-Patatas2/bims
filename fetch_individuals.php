<?php
// fetch_individuals.php
require_once 'config.php';
header('Content-Type: application/json');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
$where = [];
$params = [];

// Server-side filtering based on filter_type
if (isset($_GET['filter_type'])) {
    $type = $_GET['filter_type'];
    switch ($type) {
        case 'male':
            $where[] = "LOWER(i.gender) = 'male'";
            break;
        case 'female':
            $where[] = "LOWER(i.gender) = 'female'";
            break;
        case 'voter':
            $where[] = "i.is_voter = 1";
            break;
        case '4ps':
            $where[] = "i.is_4ps = 1";
            break;
        case 'senior':
            $where[] = "i.birthdate IS NOT NULL AND i.birthdate != '' AND TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) >= 60";
            break;
        case 'pwd':
            $where[] = "i.is_pwd = 1";
            break;
        case 'solo_parent':
            $where[] = "i.is_solo_parent = 1";
            break;
        case 'minor':
            $where[] = "i.birthdate IS NOT NULL AND i.birthdate != '' AND TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) <= 17";
            break;
        case 'children_and_youth':
            // Children & Youth: age 0-30 (example, adjust as needed)
            $where[] = "i.birthdate IS NOT NULL AND i.birthdate != '' AND TIMESTAMPDIFF(YEAR, i.birthdate, CURDATE()) BETWEEN 0 AND 30";
            break;
        // Add more cases as needed
    }
}

$sql = "SELECT i.id, i.first_name, i.middle_name, i.last_name, i.suffix, i.gender, i.birthdate, i.civil_status, i.blood_type, i.religion, i.is_pwd, i.is_voter, i.is_4ps, i.is_pregnant, i.is_solo_parent, i.email, p.name AS purok_name FROM individuals i LEFT JOIN purok p ON i.purok_id = p.id";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "id" => $row["id"],
            "first_name" => $row["first_name"],
            "middle_name" => $row["middle_name"],
            "last_name" => $row["last_name"],
            "suffix" => $row["suffix"],
            "gender" => $row["gender"],
            "birthdate" => $row["birthdate"],
            "civil_status" => $row["civil_status"],
            "blood_type" => $row["blood_type"],
            "religion" => $row["religion"],
            "is_pwd" => $row["is_pwd"],
            "is_voter" => $row["is_voter"],
            "is_4ps" => $row["is_4ps"],
            "is_pregnant" => $row["is_pregnant"],
            "is_solo_parent" => $row["is_solo_parent"],
            "email" => $row["email"],
            "purok_name" => $row["purok_name"]
        ];
    }
}
echo json_encode($data);
$conn->close();
