<?php
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Updating senior citizen status for all existing residents...\n\n";

// Get all residents with their birthdates
$result = $conn->query("SELECT id, first_name, last_name, birthdate, is_senior_citizen FROM individuals WHERE birthdate IS NOT NULL");

if ($result) {
    $updated_count = 0;
    $total_count = $result->num_rows;
    
    while ($row = $result->fetch_assoc()) {
        $birth_date = new DateTime($row['birthdate']);
        $today = new DateTime();
        $age = $today->diff($birth_date)->y;
        
        // Determine if should be senior citizen (60+ years)
        $should_be_senior = ($age >= 60) ? 1 : 0;
        
        // Only update if the status has changed
        if ($row['is_senior_citizen'] != $should_be_senior) {
            $update_stmt = $conn->prepare("UPDATE individuals SET is_senior_citizen = ? WHERE id = ?");
            $update_stmt->bind_param('ii', $should_be_senior, $row['id']);
            
            if ($update_stmt->execute()) {
                $status = $should_be_senior ? 'SET as Senior Citizen' : 'REMOVED from Senior Citizen';
                echo "✓ {$row['first_name']} {$row['last_name']} (Age: {$age}) - {$status}\n";
                $updated_count++;
            } else {
                echo "✗ Failed to update {$row['first_name']} {$row['last_name']}\n";
            }
            
            $update_stmt->close();
        } else {
            echo "- {$row['first_name']} {$row['last_name']} (Age: {$age}) - Already correct\n";
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Total residents processed: {$total_count}\n";
    echo "Updated records: {$updated_count}\n";
    echo "No changes needed: " . ($total_count - $updated_count) . "\n";
    
} else {
    echo "Error fetching residents: " . $conn->error . "\n";
}

$conn->close();
echo "\nDone!\n";
?>
