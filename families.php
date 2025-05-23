<?php
// families.php
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
// Add family
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_family'])) {
    $stmt = $conn->prepare('INSERT INTO families (family_name, purok_id, address) VALUES (?, ?, ?)');
    $stmt->bind_param('sis', $_POST['family_name'], $_POST['purok_id'], $_POST['address']);
    $stmt->execute();
    $stmt->close();
    header('Location: families.php');
    exit();
}
// Fetch puroks
$puroks = $conn->query('SELECT id, name FROM puroks');
$purok_options = [];
while ($row = $puroks->fetch_assoc()) $purok_options[] = $row;
// Fetch families
$families = $conn->query('SELECT f.*, p.name as purok_name FROM families f LEFT JOIN puroks p ON f.purok_id = p.id ORDER BY f.family_name');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Families - BIMS</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php include 'navbar.php'; ?>
    <div class="flex items-center justify-center min-h-screen" style="padding-top:4.5rem">
        <!-- Page content here -->
        <div class="max-w-4xl w-full bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-4">Families</h1>
            <form action="families.php" method="POST" class="mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="family_name" class="block text-sm font-medium text-gray-700">Family Name</label>
                        <input type="text" name="family_name" id="family_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="purok_id" class="block text-sm font-medium text-gray-700">Purok</label>
                        <select name="purok_id" id="purok_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a purok</option>
                            <?php foreach ($purok_options as $purok): ?>
                                <option value="<?= $purok['id'] ?>"><?= $purok['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" name="address" id="address" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="submit" name="add_family" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Add Family
                    </button>
                </div>
            </form>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Family Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purok</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($family = $families->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($family['family_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($family['purok_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($family['address']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <!-- Action buttons (Edit, Delete) -->
                                    <div class="flex gap-2">
                                        <a href="edit_family.php?id=<?= $family['id'] ?>" class="text-blue-600 hover:text-blue-900">Edit</a>
                                        <form action="delete_family.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this family?');">
                                            <input type="hidden" name="id" value="<?= $family['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        // Sidebar open/close logic
        const sidebar = document.getElementById('sidebar');
        const openSidebar = document.getElementById('openSidebar');
        const closeSidebar = document.getElementById('closeSidebar');
        if (openSidebar) {
            openSidebar.onclick = () => sidebar.classList.remove('-translate-x-full');
        }
        if (closeSidebar) {
            closeSidebar.onclick = () => sidebar.classList.add('-translate-x-full');
        }
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !openSidebar.contains(e.target) && window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    </script>
</body>
</html>
