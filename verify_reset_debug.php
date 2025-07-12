<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'verify_otp') {
    $credential = trim($_POST['credential'] ?? '');
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($credential) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both username/email and verification code']);
        exit;
    }
    
    try {
        // Debug: Show what we're looking for
        $debug_info = [
            'searching_for' => [
                'credential' => $credential,
                'otp' => $otp,
                'current_time' => date('Y-m-d H:i:s')
            ]
        ];
        
        // Find user with valid token - use UNIX_TIMESTAMP to avoid timezone issues
        $stmt = $pdo->prepare('
            SELECT id, username, email, first_name, last_name, reset_token, reset_token_expires_at
            FROM users 
            WHERE (username = ? OR email = ?) 
            AND reset_token = ? 
            AND UNIX_TIMESTAMP(reset_token_expires_at) > UNIX_TIMESTAMP() 
            AND reset_token IS NOT NULL
            LIMIT 1
        ');
        $stmt->execute([$credential, $credential, $otp]);
        $user_data = $stmt->fetch();
        
        // Debug: Check individual conditions
        $debug_checks = [];
        
        // Check if user exists
        $stmt = $pdo->prepare('SELECT id, username, email, reset_token, reset_token_expires_at FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$credential, $credential]);
        $user_check = $stmt->fetch();
        
        if ($user_check) {
            $debug_checks['user_found'] = true;
            $debug_checks['user_data'] = [
                'id' => $user_check['id'],
                'username' => $user_check['username'],
                'email' => $user_check['email'],
                'stored_token' => $user_check['reset_token'],
                'token_expires' => $user_check['reset_token_expires_at']
            ];
            
            // Check each condition
            $debug_checks['token_match'] = ($user_check['reset_token'] === $otp);
            $debug_checks['token_not_null'] = ($user_check['reset_token'] !== null);
            $debug_checks['token_not_expired'] = ($user_check['reset_token_expires_at'] > date('Y-m-d H:i:s'));
            
        } else {
            $debug_checks['user_found'] = false;
        }
        
        $debug_info['condition_checks'] = $debug_checks;
        
        if ($user_data) {
            // Valid OTP found - store in session for password reset
            $_SESSION['reset_user_id'] = $user_data['id'];
            $_SESSION['reset_token'] = $user_data['reset_token'];
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_expires'] = time() + 300; // 5 minutes to complete password reset
            
            echo json_encode([
                'success' => true, 
                'message' => 'Verification code confirmed. You can now set your new password.',
                'show_password_form' => true,
                'debug' => $debug_info
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid or expired verification code. Please request a new one.',
                'debug' => $debug_info
            ]);
        }
        
    } catch (Exception $e) {
        error_log("OTP verification error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'An error occurred while verifying the code. Please try again.',
            'debug' => [
                'error' => $e->getMessage()
            ]
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
