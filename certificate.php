<?php
// certificate.php
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

$stmt = $conn->prepare('SELECT i.*, p.name as purok_name, f.family_name FROM individuals i LEFT JOIN puroks p ON i.purok_id = p.id LEFT JOIN families f ON i.family_id = f.id WHERE i.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$ind = $result->fetch_assoc();
if (!$ind) die('Not found');
$stmt->close();

// Barangay logo (place your logo in the same folder and set the filename here)
$barangay_logo = 'barangay_logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - BIMS</title>
    <link href="lib/assets/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="lib/assets/all.min.css">
    <script src="lib/assets/all.min.js" defer></script>
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-white min-h-screen flex items-center justify-center">
    <?php include 'navbar.php'; ?>
    <div class="flex items-center justify-center min-h-screen w-full" style="padding-top:4.5rem">
        <!-- Main Content -->
        <div class="w-full max-w-2xl p-8 border border-gray-400 rounded shadow relative bg-white mt-8">
            <div class="flex justify-between items-center mb-4">
                <img src="<?= $barangay_logo ?>" alt="Barangay Logo" style="height:80px;">
                <div class="text-center flex-1">
                    <h2 class="text-xl font-bold">Republic of the Philippines</h2>
                    <h3 class="text-lg">Province of [Your Province]</h3>
                    <h3 class="text-lg">Municipality of [Your Municipality]</h3>
                    <h3 class="text-lg font-bold">Barangay [Your Barangay]</h3>
                </div>
                <div style="width:80px;"></div>
            </div>
            <h1 class="text-2xl font-bold text-center mb-6">Certificate of Residency</h1>
            <p class="mb-6 text-lg">This is to certify that <span class="font-bold underline"><?= htmlspecialchars($ind['first_name'] . ' ' . $ind['middle_name'] . ' ' . $ind['last_name']) ?></span>,
                born on <span class="underline"><?= htmlspecialchars($ind['birthday']) ?></span>,
                currently residing at <span class="underline"><?= htmlspecialchars($ind['address']) ?></span>,
                Purok <span class="underline"><?= htmlspecialchars($ind['purok_name']) ?></span>, Barangay [Your Barangay],
                is a bonafide resident of this barangay.</p>
            <p class="mb-6">Issued this <span class="underline"><?= date('F j, Y') ?></span> upon request for whatever legal purpose it may serve.</p>
            <div class="flex justify-between mt-12">
                <div></div>
                <div class="text-center">
                    <p class="font-bold">[Secretary Name]</p>
                    <p>Barangay Secretary</p>
                </div>
            </div>
            <button onclick="window.print()" class="no-print mt-8 bg-blue-500 text-white px-4 py-2 rounded">Print Certificate</button>
            <a href="individuals.php" class="no-print ml-2 bg-gray-400 text-white px-4 py-2 rounded">Back</a>
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
        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !openSidebar.contains(e.target) && window.innerWidth < 768) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    </script>
</body>
</html>
