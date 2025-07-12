<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$credential = trim($_POST['credential'] ?? '');

if (empty($credential)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your username or email address']);
    exit;
}

try {
    // Check if user exists (username or email)
    $stmt = $pdo->prepare('SELECT id, email, first_name, last_name, username FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$credential, $credential]);
    $user = $stmt->fetch();
    
    // Always return the same generic message for security (don't reveal if user exists)
    $generic_message = 'If an account with that username or email exists, you will receive a password reset code shortly.';
    
    if ($user) {
        // Generate 6-digit OTP
        $otp = sprintf('%06d', random_int(100000, 999999));
        
        // Set expiration time (10 minutes from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Store the OTP directly in users table
        $update_stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?');
        $update_stmt->execute([$otp, $expires_at, $user['id']]);
        
        // Log to audit trail
        $audit_stmt = $pdo->prepare('INSERT INTO audit_trail (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $audit_stmt->execute([
            $user['id'], 
            'Password Reset Requested', 
            'OTP code requested for password reset via email: ' . $user['email'],
            $ip_address
        ]);
        
        // DEBUG: Return additional info for testing
        echo json_encode([
            'success' => true, 
            'message' => $generic_message,
            'show_otp_form' => true,
            'debug' => [
                'user_id' => $user['id'],
                'otp_generated' => $otp,
                'expires_at' => $expires_at,
                'current_time' => date('Y-m-d H:i:s'),
                'credential_used' => $credential
            ]
        ]);
        
    } else {
        // User doesn't exist
        echo json_encode([
            'success' => true, 
            'message' => $generic_message,
            'show_otp_form' => true,
            'debug' => [
                'user_found' => false,
                'credential_used' => $credential
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request. Please try again later.',
        'debug' => [
            'error' => $e->getMessage()
        ]
    ]);
}
?>
