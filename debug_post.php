<?php
// Debug script to check what's being sent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST data received:");
    error_log(print_r($_POST, true));
    
    echo "POST data:\n";
    print_r($_POST);
    echo "\n\nCheckbox values:\n";
    echo "is_pwd: " . (isset($_POST['is_pwd']) ? $_POST['is_pwd'] : 'NOT SET') . "\n";
    echo "is_voter: " . (isset($_POST['is_voter']) ? $_POST['is_voter'] : 'NOT SET') . "\n";
    echo "is_4ps: " . (isset($_POST['is_4ps']) ? $_POST['is_4ps'] : 'NOT SET') . "\n";
    echo "is_pregnant: " . (isset($_POST['is_pregnant']) ? $_POST['is_pregnant'] : 'NOT SET') . "\n";
    echo "is_solo_parent: " . (isset($_POST['is_solo_parent']) ? $_POST['is_solo_parent'] : 'NOT SET') . "\n";
    echo "email: " . (isset($_POST['email']) ? $_POST['email'] : 'NOT SET') . "\n";
} else {
    echo "This script expects POST data";
}
?>
