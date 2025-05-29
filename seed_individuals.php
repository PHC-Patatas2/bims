<?php
// seed_individuals.php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

function randomDate($start, $end) {
    $min = strtotime($start);
    $max = strtotime($end);
    $val = rand($min, $max);
    return date('Y-m-d', $val);
}

function randomNameParts() {
    $first = ['Juan', 'Maria', 'Pedro', 'Ana', 'Josefa', 'Carlos', 'Liza', 'Miguel', 'Sofia', 'Rosa', 'Mark', 'Ella', 'John', 'Grace', 'Paul', 'Daisy', 'Luke', 'Mia', 'Noah', 'Lea'];
    $middle = ['A.', 'B.', 'C.', 'D.', 'E.', 'F.', 'G.', 'H.', 'I.', 'J.', null, null, null];
    $last = ['Dela Cruz', 'Santos', 'Reyes', 'Lopez', 'Cruz', 'Lim', 'Tan', 'Ramos', 'Gomez', 'Flores', 'Garcia', 'Torres', 'Rivera', 'Morales', 'Castro', 'Ortiz', 'Mendoza', 'Silva', 'Rojas', 'Vega'];
    $suffix = [null, null, null, 'Jr.', 'Sr.', 'III', 'IV'];
    return [
        $first[array_rand($first)],
        $middle[array_rand($middle)],
        $last[array_rand($last)],
        $suffix[array_rand($suffix)]
    ];
}

$valid_puroks = [
    "Purok 1 (pulongtingga)",
    "Purok 2 (looban)",
    "Purok 3 (proper)"
];

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['insert_valid'])) {
        $conn->query("DELETE FROM individuals");
        $conn->query("DELETE FROM families");
        // Insert 100 families
        $fam_stmt = $conn->prepare("INSERT INTO families (id, family_head, address) VALUES (?, ?, ?)");
        for ($i = 1; $i <= 100; $i++) {
            list($first, $middle, $last, $suffix) = randomNameParts();
            $family_head = $first . ' ' . ($middle ? $middle . ' ' : '') . $last . ($suffix ? ' ' . $suffix : '');
            $address = 'Sample Address ' . $i;
            $fam_stmt->bind_param('iss', $i, $family_head, $address);
            $fam_stmt->execute();
        }
        $fam_stmt->close();
        // Now insert individuals
        $stmt = $conn->prepare("INSERT INTO individuals (first_name, middle_name, last_name, suffix, gender, birthdate, purok, is_voter, is_4ps, is_pwd, is_solo_parent, is_pregnant, family_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        for ($i = 1; $i <= 100; $i++) {
            list($first, $middle, $last, $suffix) = randomNameParts();
            $gender = ($i % 2 == 0) ? 'male' : 'female';
            $birthdate = randomDate('1950-01-01', '2025-05-01');
            $purok = $valid_puroks[array_rand($valid_puroks)];
            $is_voter = rand(0, 1);
            $is_4ps = rand(0, 1);
            $is_pwd = rand(0, 1);
            $is_solo_parent = rand(0, 1);
            $is_pregnant = ($gender == 'female') ? rand(0, 1) : 0;
            $family_id = $i;
            $stmt->bind_param('ssssssssiiiii', $first, $middle, $last, $suffix, $gender, $birthdate, $purok, $is_voter, $is_4ps, $is_pwd, $is_solo_parent, $is_pregnant, $family_id);
            $stmt->execute();
        }
        $stmt->close();
        $messages[] = "100 valid test families and individuals inserted.";
    }
    if (isset($_POST['insert_invalid'])) {
        // Try to insert some invalid records
        $errors = 0;
        // Invalid purok, gender, nulls, negative family_id
        $sql = "INSERT INTO individuals (first_name, middle_name, last_name, suffix, gender, birthdate, purok, is_voter, is_4ps, is_pwd, is_solo_parent, is_pregnant, family_id) VALUES
        ('Invalid', NULL, 'Purok', NULL, 'male', '2000-01-01', 'Purok X', 1, 0, 0, 0, 0, 101),
        ('Invalid', NULL, 'Gender', NULL, 'other', '2000-01-01', 'Purok 1 (pulongtingga)', 1, 0, 0, 0, 0, 102),
        ('Null', NULL, 'Gender', NULL, NULL, '2000-01-01', 'Purok 1 (pulongtingga)', 1, 0, 0, 0, 0, 103),
        ('Null', NULL, 'Purok', NULL, 'female', '2000-01-01', NULL, 1, 0, 0, 0, 1, 104),
        ('Null', NULL, 'Birthdate', NULL, 'male', NULL, 'Purok 2 (looban)', 1, 0, 0, 0, 0, 105),
        ('Negative', NULL, 'Family', NULL, 'female', '2000-01-01', 'Purok 3 (proper)', 1, 0, 0, 0, 0, -1)
        ";
        if (!$conn->query($sql)) {
            $errors++;
        }
        $messages[] = "Attempted to insert invalid records. Check your dashboard and database for error handling.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seed Individuals Table</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">
    <div class="bg-white rounded-xl shadow-lg p-8 mt-10 w-full max-w-lg flex flex-col gap-6">
        <h1 class="text-2xl font-bold text-blue-700 mb-2">Seed Individuals Table</h1>
        <?php foreach ($messages as $msg): ?>
            <div class="bg-blue-100 text-blue-800 rounded p-2 mb-2"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
        <form method="post" class="flex flex-col gap-4">
            <button name="insert_valid" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition">Insert 100 Valid Test Records</button>
            <button name="insert_invalid" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition">Insert Invalid Test Records</button>
        </form>
        <a href="dashboard.php" class="text-blue-600 hover:underline mt-4">Back to Dashboard</a>
    </div>
</body>
</html>
