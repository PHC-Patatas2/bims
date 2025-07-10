<?php
require_once 'config.php';

echo "Testing database connection and form data processing...\n";

// Test connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
echo "✓ Database connection successful\n";

// Test parameter binding order
echo "\nTesting parameter binding order:\n";
echo "SQL: INSERT INTO individuals (first_name, middle_name, last_name, suffix, gender, birthdate, civil_status, blood_type, religion, is_pwd, is_voter, is_4ps, is_pregnant, is_solo_parent, is_senior_citizen, purok_id, email)\n";
echo "Bind param types should be: 'sssssssssiiiiiiis'\n";
echo "That's: 9 strings + 6 integers + 1 integer + 1 string = 17 parameters\n";

// Count actual parameters in the INSERT statement
$sql = "INSERT INTO individuals (first_name, middle_name, last_name, suffix, gender, birthdate, civil_status, blood_type, religion, is_pwd, is_voter, is_4ps, is_pregnant, is_solo_parent, is_senior_citizen, purok_id, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$param_count = substr_count($sql, '?');
echo "Parameter count in SQL: $param_count\n";

// Test prepared statement
$stmt = $conn->prepare($sql);
if ($stmt) {
    echo "✓ Prepared statement created successfully\n";
} else {
    echo "✗ Failed to create prepared statement: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
echo "\nTest completed.\n";
?>
