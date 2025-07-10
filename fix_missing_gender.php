<?php
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "Finding individuals with empty gender values...\n";

// Get individuals with empty gender
$result = $conn->query("SELECT id, first_name, last_name FROM individuals WHERE gender IS NULL OR gender = ''");
$individuals = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $individuals[] = $row;
        echo "Found: ID {$row['id']} - {$row['first_name']} {$row['last_name']}\n";
    }
}

if (empty($individuals)) {
    echo "No individuals with empty gender found.\n";
    $conn->close();
    exit;
}

echo "\nNote: These individuals need to have their gender manually set since it cannot be automatically determined.\n";
echo "You should edit each resident through the UI to set their correct gender.\n";
echo "Alternatively, if you know the correct genders, you can manually update them here.\n\n";

// Optionally, you could manually set genders if you know them:
// Example:
// $conn->query("UPDATE individuals SET gender = 'Male' WHERE id = 219"); // James Ivan Deric Dacles
// $conn->query("UPDATE individuals SET gender = 'Male' WHERE id = 230"); // Kian Crisanto Dacuyan  
// $conn->query("UPDATE individuals SET gender = 'Male' WHERE id = 232"); // Chris Laurence Dacuyan

echo "Script completed. Please manually edit these residents to set their gender.\n";
$conn->close();
?>
