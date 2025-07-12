<?php
require_once 'config.php';

echo "<h2>Deep Timestamp Debug</h2>";

// Get the current OTP data
$stmt = $pdo->prepare('SELECT id, username, email, reset_token, reset_token_expires_at FROM users WHERE username = ?');
$stmt->execute(['secretary']);
$user = $stmt->fetch();

if ($user) {
    echo "<h3>Raw Database Values:</h3>";
    echo "Reset Token: " . $user['reset_token'] . "<br>";
    echo "Raw Expires Value: " . $user['reset_token_expires_at'] . "<br>";
    
    echo "<h3>Timestamp Comparisons:</h3>";
    
    // Get MySQL timestamps
    $stmt = $pdo->query('SELECT NOW() as mysql_now, UNIX_TIMESTAMP() as mysql_timestamp');
    $mysql_times = $stmt->fetch();
    echo "MySQL NOW(): " . $mysql_times['mysql_now'] . "<br>";
    echo "MySQL UNIX_TIMESTAMP(): " . $mysql_times['mysql_timestamp'] . "<br>";
    
    // Convert stored expiration to timestamp
    $stored_expires = $user['reset_token_expires_at'];
    $stmt = $pdo->prepare('SELECT UNIX_TIMESTAMP(?) as stored_timestamp');
    $stmt->execute([$stored_expires]);
    $stored_timestamp = $stmt->fetch()['stored_timestamp'];
    echo "Stored expiration as timestamp: " . $stored_timestamp . "<br>";
    echo "Current PHP timestamp: " . time() . "<br>";
    echo "Difference: " . ($stored_timestamp - $mysql_times['mysql_timestamp']) . " seconds<br>";
    
    echo "<h3>Testing Simple Comparison:</h3>";
    // Test a simple direct comparison
    $stmt = $pdo->prepare('SELECT ? > UNIX_TIMESTAMP() as is_future');
    $stmt->execute([$stored_timestamp]);
    $is_future = $stmt->fetch()['is_future'];
    echo "Is stored timestamp > MySQL timestamp: " . ($is_future ? 'TRUE' : 'FALSE') . "<br>";
    
    echo "<h3>Regenerate OTP with Correct Timezone:</h3>";
    // Let's set a new OTP with MySQL time
    $new_otp = sprintf("%06d", mt_rand(0, 999999));
    $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE username = ?');
    $stmt->execute([$new_otp, 'secretary']);
    
    echo "New OTP set: <strong style='color: red; font-size: 18px;'>" . $new_otp . "</strong><br>";
    
    // Verify the new OTP
    $stmt = $pdo->prepare('SELECT reset_token, reset_token_expires_at FROM users WHERE username = ?');
    $stmt->execute(['secretary']);
    $updated_user = $stmt->fetch();
    echo "New expiration time: " . $updated_user['reset_token_expires_at'] . "<br>";
    
    // Test the query with new OTP
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count
        FROM users 
        WHERE username = ? 
        AND reset_token = ? 
        AND UNIX_TIMESTAMP(reset_token_expires_at) > UNIX_TIMESTAMP() 
        AND reset_token IS NOT NULL
    ');
    $stmt->execute(['secretary', $new_otp]);
    $count = $stmt->fetch()['count'];
    echo "New OTP verification query result: " . $count . "<br>";
    
} else {
    echo "No user found!";
}
?>
