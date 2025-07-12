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
        // Find user with valid token - use UNIX_TIMESTAMP to avoid timezone issues
        $stmt = $pdo->prepare('
            SELECT id, username, email, first_name, last_name
            FROM users 
            WHERE (username = ? OR email = ?) 
            AND reset_token = ? 
            AND UNIX_TIMESTAMP(reset_token_expires_at) > UNIX_TIMESTAMP() 
            AND reset_token IS NOT NULL
            LIMIT 1
        ');
        $stmt->execute([$credential, $credential, $otp]);
        $user_data = $stmt->fetch();
        
        // Log verification attempt first (before sending response)
        try {
            $log_stmt = $pdo->prepare('
                INSERT INTO audit_trail (user_id, action, details, ip_address) 
                VALUES (?, ?, ?, ?)
            ');
            $user_id = $user_data['id'] ?? null;
            $result = $user_data ? 'successful' : 'failed';
            $details = json_encode([
                'action' => 'password_reset_otp_verification',
                'credential' => $credential,
                'result' => $result,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            $log_stmt->execute([
                $user_id,
                'Password Reset OTP Verification',
                $details,
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Exception $log_error) {
            // Don't let logging errors break the main functionality
            error_log("Audit logging error: " . $log_error->getMessage());
        }
        
        if ($user_data) {
            // Valid OTP found - store in session for password reset
            $_SESSION['reset_user_id'] = $user_data['id'];
            $_SESSION['reset_token'] = $otp; // Store the verified token
            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_expires'] = time() + 300; // 5 minutes to complete password reset
            
            echo json_encode([
                'success' => true, 
                'message' => 'Verification code confirmed. You can now set your new password.',
                'show_password_form' => true
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid or expired verification code. Please request a new one.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("OTP verification error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'An error occurred while verifying the code. Please try again.'
        ]);
    }
    
} elseif ($action === 'reset_password') {
    // Verify session
    if (!isset($_SESSION['reset_verified']) || 
        !isset($_SESSION['reset_user_id']) || 
        !isset($_SESSION['reset_expires']) ||
        $_SESSION['reset_expires'] < time()) {
        
        echo json_encode([
            'success' => false, 
            'message' => 'Session expired. Please start the password reset process again.'
        ]);
        exit;
    }
    
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both password fields']);
        exit;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit;
    }
    
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit;
    }
    
    try {
        // Update password and clear reset token
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_token_expires_at = NULL 
            WHERE id = ?
        ');
        $stmt->execute([$hashed_password, $_SESSION['reset_user_id']]);
        
        // Get user info for logging
        $stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['reset_user_id']]);
        $user_info = $stmt->fetch();
        
        // Log password reset
        try {
            $log_stmt = $pdo->prepare('
                INSERT INTO audit_trail (user_id, action, details, ip_address) 
                VALUES (?, ?, ?, ?)
            ');
            $details = json_encode([
                'action' => 'password_reset_completed',
                'username' => $user_info['username'] ?? '',
                'email' => $user_info['email'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            $log_stmt->execute([
                $_SESSION['reset_user_id'],
                'Password Reset Completed',
                $details,
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Exception $log_error) {
            error_log("Audit logging error: " . $log_error->getMessage());
        }
        
        // Clear session
        unset($_SESSION['reset_user_id']);
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_verified']);
        unset($_SESSION['reset_expires']);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Password reset successfully. You can now log in with your new password.'
        ]);
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'An error occurred while resetting your password. Please try again.'
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
