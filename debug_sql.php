<?php
require_once 'config.php';

// Get the current OTP data
$stmt = $pdo->prepare('SELECT id, username, email, reset_token, reset_token_expires_at FROM users WHERE username = ? OR email = ?');
$stmt->execute(['secretary', 'secretary']);
$user = $stmt->fetch();

echo "<h2>Debug SQL Date Comparison</h2>";

if ($user) {
    echo "<h3>User Data:</h3>";
    echo "ID: " . $user['id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Reset Token: " . $user['reset_token'] . "<br>";
    echo "Token Expires: " . $user['reset_token_expires_at'] . "<br>";
    
    echo "<h3>Date Comparisons:</h3>";
    $current_time = date('Y-m-d H:i:s');
    echo "Current PHP time: " . $current_time . "<br>";
    
    // Get MySQL current time
    $stmt = $pdo->query('SELECT NOW() as mysql_now');
    $mysql_time = $stmt->fetch()['mysql_now'];
    echo "MySQL NOW(): " . $mysql_time . "<br>";
    
    echo "<h3>Comparison Results:</h3>";
    echo "PHP comparison (expires > current): " . ($user['reset_token_expires_at'] > $current_time ? 'TRUE' : 'FALSE') . "<br>";
    
    // Test the exact SQL query from verification
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count
        FROM users 
        WHERE (username = ? OR email = ?) 
        AND reset_token = ? 
        AND reset_token_expires_at > NOW() 
        AND reset_token IS NOT NULL
    ');
    $stmt->execute(['secretary', 'secretary', $user['reset_token']]);
    $count = $stmt->fetch()['count'];
    echo "SQL query result count: " . $count . "<br>";
    
    // Test each condition separately
    echo "<h3>Individual Condition Tests:</h3>";
    
    // Test user match
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?');
    $stmt->execute(['secretary', 'secretary']);
    echo "User found: " . ($stmt->fetch()['count'] > 0 ? 'TRUE' : 'FALSE') . "<br>";
    
    // Test token match
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND reset_token = ?');
    $stmt->execute(['secretary', 'secretary', $user['reset_token']]);
    echo "Token matches: " . ($stmt->fetch()['count'] > 0 ? 'TRUE' : 'FALSE') . "<br>";
    
    // Test token not null
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND reset_token IS NOT NULL');
    $stmt->execute(['secretary', 'secretary']);
    echo "Token not null: " . ($stmt->fetch()['count'] > 0 ? 'TRUE' : 'FALSE') . "<br>";
    
    // Test expiration with different approaches
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND reset_token_expires_at > NOW()');
    $stmt->execute(['secretary', 'secretary']);
    echo "Token not expired (NOW()): " . ($stmt->fetch()['count'] > 0 ? 'TRUE' : 'FALSE') . "<br>";
    
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND reset_token_expires_at > ?');
    $stmt->execute(['secretary', 'secretary', $mysql_time]);
    echo "Token not expired (MySQL time): " . ($stmt->fetch()['count'] > 0 ? 'TRUE' : 'FALSE') . "<br>";
    
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND reset_token_expires_at > ?');
    $stmt->execute(['secretary', 'secretary', $current_time]);
    echo "Token not expired (PHP time): " . ($stmt->fetch()['count'] > 0 ? 'TRUE' : 'FALSE') . "<br>";
    
    // Test the UNIX_TIMESTAMP approach (our fix)
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND UNIX_TIMESTAMP(reset_token_expires_at) > UNIX_TIMESTAMP()');
    $stmt->execute(['secretary', 'secretary']);
    echo "Token not expired (UNIX_TIMESTAMP): " . ($stmt->fetch()['count'] > 0 ? 'TRUE' : 'FALSE') . "<br>";
    
    // Test the complete query with UNIX_TIMESTAMP
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count
        FROM users 
        WHERE (username = ? OR email = ?) 
        AND reset_token = ? 
        AND UNIX_TIMESTAMP(reset_token_expires_at) > UNIX_TIMESTAMP() 
        AND reset_token IS NOT NULL
    ');
    $stmt->execute(['secretary', 'secretary', $user['reset_token']]);
    $count_fixed = $stmt->fetch()['count'];
    echo "Fixed SQL query result count: " . $count_fixed . "<br>";
    
    // Show exact expiration check
    echo "<h3>Detailed Time Analysis:</h3>";
    $expires_at = strtotime($user['reset_token_expires_at']);
    $current_timestamp = time();
    echo "Expires timestamp: " . $expires_at . "<br>";
    echo "Current timestamp: " . $current_timestamp . "<br>";
    echo "Difference (seconds): " . ($expires_at - $current_timestamp) . "<br>";
    echo "Token still valid: " . ($expires_at > $current_timestamp ? 'TRUE' : 'FALSE') . "<br>";
    
} else {
    echo "No user found!";
}
?>
