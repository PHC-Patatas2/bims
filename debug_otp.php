<?php
/**
 * Debug OTP Verification
 */

require_once 'config.php';

echo "<h2>OTP Verification Debug</h2>\n";

try {
    // Get the test user
    $stmt = $pdo->query("SELECT id, username, email, reset_token, reset_token_expires_at FROM users LIMIT 1");
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h3>Current User Data</h3>\n";
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Field</th><th>Value</th></tr>\n";
        echo "<tr><td>ID</td><td>{$user['id']}</td></tr>\n";
        echo "<tr><td>Username</td><td>{$user['username']}</td></tr>\n";
        echo "<tr><td>Email</td><td>{$user['email']}</td></tr>\n";
        echo "<tr><td>Reset Token</td><td>" . ($user['reset_token'] ?? 'NULL') . "</td></tr>\n";
        echo "<tr><td>Token Expires</td><td>" . ($user['reset_token_expires_at'] ?? 'NULL') . "</td></tr>\n";
        echo "<tr><td>Current Time</td><td>" . date('Y-m-d H:i:s') . "</td></tr>\n";
        echo "</table>\n";
        
        // Generate a new test OTP
        $otp = sprintf('%06d', random_int(100000, 999999));
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        echo "<h3>Setting New Test OTP</h3>\n";
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
        $stmt->execute([$otp, $expires, $user['id']]);
        
        echo "✓ New OTP set: <strong style='color: red; font-size: 18px;'>$otp</strong><br>\n";
        echo "✓ Expires at: $expires<br>\n";
        
        // Test the verification query
        echo "<h3>Testing Verification Query</h3>\n";
        
        $test_credentials = [$user['username'], $user['email']];
        
        foreach ($test_credentials as $credential) {
            echo "<h4>Testing with: $credential</h4>\n";
            
            $stmt = $pdo->prepare('
                SELECT id, username, email, first_name, last_name, reset_token, reset_token_expires_at
                FROM users 
                WHERE (username = ? OR email = ?) 
                AND reset_token = ? 
                AND reset_token_expires_at > NOW() 
                AND reset_token IS NOT NULL
                LIMIT 1
            ');
            $stmt->execute([$credential, $credential, $otp]);
            $result = $stmt->fetch();
            
            if ($result) {
                echo "✅ Query found user: {$result['username']}<br>\n";
                echo "- Token: {$result['reset_token']}<br>\n";
                echo "- Expires: {$result['reset_token_expires_at']}<br>\n";
            } else {
                echo "❌ Query returned no results<br>\n";
                
                // Debug individual conditions
                echo "<strong>Debugging conditions:</strong><br>\n";
                
                // Check user lookup
                $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE username = ? OR email = ?');
                $stmt->execute([$credential, $credential]);
                $user_check = $stmt->fetch();
                
                if ($user_check) {
                    echo "✓ User found by credential<br>\n";
                } else {
                    echo "❌ User NOT found by credential<br>\n";
                }
                
                // Check token match
                $stmt = $pdo->prepare('SELECT reset_token FROM users WHERE (username = ? OR email = ?) AND reset_token = ?');
                $stmt->execute([$credential, $credential, $otp]);
                $token_check = $stmt->fetch();
                
                if ($token_check) {
                    echo "✓ Token matches<br>\n";
                } else {
                    echo "❌ Token does NOT match<br>\n";
                }
                
                // Check expiration
                $stmt = $pdo->prepare('SELECT reset_token_expires_at FROM users WHERE (username = ? OR email = ?) AND reset_token_expires_at > NOW()');
                $stmt->execute([$credential, $credential]);
                $expiry_check = $stmt->fetch();
                
                if ($expiry_check) {
                    echo "✓ Token not expired<br>\n";
                } else {
                    echo "❌ Token is expired<br>\n";
                }
                
                // Check if token is not null
                $stmt = $pdo->prepare('SELECT reset_token FROM users WHERE (username = ? OR email = ?) AND reset_token IS NOT NULL');
                $stmt->execute([$credential, $credential]);
                $null_check = $stmt->fetch();
                
                if ($null_check) {
                    echo "✓ Token is not NULL<br>\n";
                } else {
                    echo "❌ Token is NULL<br>\n";
                }
            }
            echo "<br>\n";
        }
        
        // Test the exact API call
        echo "<h3>Manual API Test</h3>\n";
        echo "<form method='post' action='verify_reset.php' style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
        echo "<h4>Test OTP Verification</h4>\n";
        echo "<input type='hidden' name='action' value='verify_otp'>\n";
        echo "<label>Credential: <input type='text' name='credential' value='{$user['username']}' style='margin: 5px;'></label><br>\n";
        echo "<label>OTP: <input type='text' name='otp' value='$otp' style='margin: 5px;'></label><br>\n";
        echo "<button type='submit' style='margin: 5px; padding: 5px 10px; background: #2563eb; color: white; border: none; cursor: pointer;'>Test Verify OTP</button>\n";
        echo "</form>\n";
        
        echo "<h3>Use This Information for Testing</h3>\n";
        echo "<ol>\n";
        echo "<li>Go to <a href='http://bims.local/login.php' target='_blank'>login page</a></li>\n";
        echo "<li>Click 'Forgot password?'</li>\n";
        echo "<li>Enter: <strong>{$user['username']}</strong> or <strong>{$user['email']}</strong></li>\n";
        echo "<li>Enter OTP: <strong style='color: red; font-size: 16px;'>$otp</strong></li>\n";
        echo "</ol>\n";
        
    } else {
        echo "❌ No users found in database<br>\n";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error</h3>\n";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #2563eb; }
table { margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ccc; }
th { background: #f0f0f0; }
</style>
