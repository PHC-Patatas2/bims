<?php
session_start();
require_once 'config.php';
require_once 'email_config.php';
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
        
        // Store the OTP directly in users table using MySQL time to avoid timezone issues
        $update_stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?');
        $update_stmt->execute([$otp, $user['id']]);
        
        // Log to audit trail
        $audit_stmt = $pdo->prepare('INSERT INTO audit_trail (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)');
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $audit_stmt->execute([
            $user['id'], 
            'Password Reset Requested', 
            'OTP code requested for password reset via email: ' . $user['email'],
            $ip_address
        ]);
        
        // Send email with OTP
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = EMAIL_CHARSET;
            
            // Recipients
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code - BIMS';
            
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
                <div style='background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <h1 style='color: #2563eb; margin: 0; font-size: 24px;'>Password Reset Request</h1>
                        <p style='color: #6b7280; margin: 10px 0 0 0;'>BIMS - Resident Information System</p>
                    </div>
                    
                    <div style='margin-bottom: 30px;'>
                        <p style='color: #374151; margin: 0 0 15px 0; font-size: 16px;'>Hello " . htmlspecialchars($user['first_name']) . ",</p>
                        <p style='color: #374151; margin: 0 0 20px 0; line-height: 1.6;'>
                            You have requested to reset your password for your BIMS account. Please use the verification code below to proceed:
                        </p>
                    </div>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <div style='background-color: #f3f4f6; border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px; display: inline-block;'>
                            <p style='margin: 0 0 10px 0; color: #6b7280; font-size: 14px; font-weight: bold;'>VERIFICATION CODE</p>
                            <p style='margin: 0; font-size: 32px; font-weight: bold; color: #2563eb; letter-spacing: 3px; font-family: monospace;'>" . $otp . "</p>
                        </div>
                    </div>
                    
                    <div style='margin-bottom: 30px;'>
                        <p style='color: #374151; margin: 0 0 15px 0; line-height: 1.6;'>
                            <strong>Important:</strong> This code will expire in <strong>10 minutes</strong> for security reasons.
                        </p>
                        <p style='color: #374151; margin: 0 0 15px 0; line-height: 1.6;'>
                            If you did not request this password reset, please ignore this email and your password will remain unchanged.
                        </p>
                    </div>
                    
                    <div style='border-top: 1px solid #e5e7eb; padding-top: 20px; text-align: center;'>
                        <p style='color: #9ca3af; margin: 0; font-size: 12px;'>
                            This is an automated message from the BIMS system. Please do not reply to this email.
                        </p>
                        <p style='color: #9ca3af; margin: 5px 0 0 0; font-size: 12px;'>
                            Sucol, Calumpit, Bulacan
                        </p>
                    </div>
                </div>
            </div>";
            
            $mail->AltBody = "Password Reset Code: $otp\n\nThis code will expire in 10 minutes.\n\nIf you did not request this, please ignore this email.";
            
            $mail->send();
            
            // Log successful email send (without logging the OTP for security)
            error_log("Password reset OTP sent to user ID: {$user['id']}, email: {$user['email']}");
            
        } catch (Exception $e) {
            // Log email error but don't reveal it to user
            error_log("Failed to send password reset email: " . $mail->ErrorInfo);
            // Still return success message for security
        }
    }
    
    // Always return the same message regardless of whether user was found
    echo json_encode([
        'success' => true, 
        'message' => $generic_message,
        'show_otp_form' => true
    ]);
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request. Please try again later.'
    ]);
}
?>
