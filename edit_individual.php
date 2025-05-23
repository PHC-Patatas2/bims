<?php
// edit_individual.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'secretary') {
    header('Location: index.php');
    exit();
}

require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die('Invalid ID');

// Fetch puroks and families
$puroks = $conn->query('SELECT id, name FROM puroks');
$purok_options = [];
while ($row = $puroks->fetch_assoc()) $purok_options[] = $row;
$families = $conn->query('SELECT id, family_name FROM families');
$family_options = [];
while ($row = $families->fetch_assoc()) $family_options[] = $row;

// Fetch individual
$stmt = $conn->prepare('SELECT * FROM individuals WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$ind = $result->fetch_assoc();
if (!$ind) die('Not found');
$stmt->close();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare('UPDATE individuals SET last_name=?, first_name=?, middle_name=?, birthday=?, age=?, gender=?, purok_id=?, family_id=?, is_pwd=?, is_4ps=?, contact_number=?, address=?, civil_status=?, occupation=?, education=?, religion=?, voter_status=?, email=?, photo=? WHERE id=?');
    $stmt->bind_param(
        'ssssisiiisssssssbssi',
        $_POST['last_name'],
        $_POST['first_name'],
        $_POST['middle_name'],
        $_POST['birthday'],
        $_POST['age'],
        $_POST['gender'],
        $_POST['purok_id'],
        $_POST['family_id'],
        $_POST['is_pwd'],
        $_POST['is_4ps'],
        $_POST['contact_number'],
        $_POST['address'],
        $_POST['civil_status'],
        $_POST['occupation'],
        $_POST['education'],
        $_POST['religion'],
        $_POST['voter_status'],
        $_POST['email'],
        $_POST['photo'],
        $id
    );
    $stmt->execute();
    $stmt->close();
    header('Location: individuals.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Individual - BIMS</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-4">Edit Individual</h1>
        <a href="individuals.php" class="mb-4 inline-block bg-blue-500 text-white px-4 py-2 rounded">Back to List</a>
        <div class="bg-white p-4 rounded shadow">
            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input class="border p-2 rounded" name="last_name" value="<?= htmlspecialchars($ind['last_name']) ?>" placeholder="Last Name" required>
                <input class="border p-2 rounded" name="first_name" value="<?= htmlspecialchars($ind['first_name']) ?>" placeholder="First Name" required>
                <input class="border p-2 rounded" name="middle_name" value="<?= htmlspecialchars($ind['middle_name']) ?>" placeholder="Middle Name">
                <input class="border p-2 rounded" name="birthday" type="date" value="<?= htmlspecialchars($ind['birthday']) ?>" required>
                <input class="border p-2 rounded" name="age" type="number" min="0" value="<?= htmlspecialchars($ind['age']) ?>" required>
                <select class="border p-2 rounded" name="gender" required>
                    <option value="">Gender</option>
                    <option value="Male" <?= $ind['gender']=='Male'?'selected':'' ?>>Male</option>
                    <option value="Female" <?= $ind['gender']=='Female'?'selected':'' ?>>Female</option>
                    <option value="Other" <?= $ind['gender']=='Other'?'selected':'' ?>>Other</option>
                </select>
                <select class="border p-2 rounded" name="purok_id" required>
                    <option value="">Purok</option>
                    <?php foreach ($purok_options as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $ind['purok_id']==$p['id']?'selected':'' ?>><?= $p['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="border p-2 rounded" name="family_id">
                    <option value="">Family (optional)</option>
                    <?php foreach ($family_options as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= $ind['family_id']==$f['id']?'selected':'' ?>><?= $f['family_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="border p-2 rounded" name="is_pwd">
                    <option value="0" <?= !$ind['is_pwd']?'selected':'' ?>>Not PWD</option>
                    <option value="1" <?= $ind['is_pwd']?'selected':'' ?>>PWD</option>
                </select>
                <select class="border p-2 rounded" name="is_4ps">
                    <option value="0" <?= !$ind['is_4ps']?'selected':'' ?>>Not 4Ps</option>
                    <option value="1" <?= $ind['is_4ps']?'selected':'' ?>>4Ps</option>
                </select>
                <input class="border p-2 rounded" name="contact_number" value="<?= htmlspecialchars($ind['contact_number']) ?>" placeholder="Contact Number">
                <input class="border p-2 rounded" name="address" value="<?= htmlspecialchars($ind['address']) ?>" placeholder="Address">
                <input class="border p-2 rounded" name="civil_status" value="<?= htmlspecialchars($ind['civil_status']) ?>" placeholder="Civil Status">
                <input class="border p-2 rounded" name="occupation" value="<?= htmlspecialchars($ind['occupation']) ?>" placeholder="Occupation">
                <input class="border p-2 rounded" name="education" value="<?= htmlspecialchars($ind['education']) ?>" placeholder="Education">
                <input class="border p-2 rounded" name="religion" value="<?= htmlspecialchars($ind['religion']) ?>" placeholder="Religion">
                <select class="border p-2 rounded" name="voter_status">
                    <option value="0" <?= !$ind['voter_status']?'selected':'' ?>>Not a Voter</option>
                    <option value="1" <?= $ind['voter_status']?'selected':'' ?>>Voter</option>
                </select>
                <input class="border p-2 rounded" name="email" value="<?= htmlspecialchars($ind['email']) ?>" placeholder="Email">
                <input class="border p-2 rounded" name="photo" value="<?= htmlspecialchars($ind['photo']) ?>" placeholder="Photo Path (optional)">
                <button class="col-span-1 md:col-span-2 bg-green-500 text-white py-2 rounded" type="submit">Update Individual</button>
            </form>
        </div>
    </div>
</body>
</html>
