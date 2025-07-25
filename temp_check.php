<?php
$conn = new mysqli("localhost", "root", "", "bims_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "DESCRIBE certificate_requests table:" . PHP_EOL;
$result = $conn->query("DESCRIBE certificate_requests");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo sprintf("%-20s %-20s %-10s %-10s %-20s %-10s", 
            $row["Field"], 
            $row["Type"], 
            $row["Null"], 
            $row["Key"], 
            $row["Default"], 
            $row["Extra"]
        ) . PHP_EOL;
    }
} else {
    echo "Error: " . $conn->error . PHP_EOL;
}
?>
