<?php
session_start();
require_once 'config.php';
require_once 'email_config.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get form data
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate input
if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Subject and message are required']);
    exit();
}

try {
    // Connect to database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Get user info for logging
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT first_name, last_name FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($user_first_name, $user_last_name);
    $stmt->fetch();
    $stmt->close();
    $user_full_name = trim($user_first_name . ' ' . $user_last_name);

    // Get all residents with email addresses
    $stmt = $conn->prepare("SELECT first_name, last_name, email FROM individuals WHERE email IS NOT NULL AND email != '' AND email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'");
    $stmt->execute();
    $result = $stmt->get_result();
    $recipients = [];
    
    while ($row = $result->fetch_assoc()) {
        $recipients[] = [
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'email' => $row['email']
        ];
    }
    $stmt->close();

    if (empty($recipients)) {
        echo json_encode(['success' => false, 'message' => 'No residents found with valid email addresses']);
        exit();
    }

    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = EMAIL_CHARSET;
    $mail->SMTPDebug = EMAIL_DEBUG;

    // Sender info
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

    // Email content settings
    $mail->isHTML(true);
    $mail->Subject = $subject;
    
    // Create HTML message
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8fafc; padding: 30px; border-radius: 0 0 8px 8px; }
            .message { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📢 Barangay Announcement</h1>
            </div>
            <div class='content'>
                <div class='message'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <div class='footer'>
                    <p>This announcement was sent by " . htmlspecialchars($user_full_name) . " from the Barangay Information Management System.</p>
                    <p><small>Sent on " . date('F j, Y g:i A') . "</small></p>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
    $mail->Body = $html_message;
    $mail->AltBody = strip_tags(str_replace('<br>', '\n', $message)) . '\n\n---\nThis announcement was sent by ' . $user_full_name . ' from the Barangay Information Management System.';

    // Send emails
    $sent_count = 0;
    $failed_emails = [];

    foreach ($recipients as $recipient) {
        try {
            // Clear previous recipients
            $mail->clearAddresses();
            
            // Add current recipient
            $mail->addAddress($recipient['email'], $recipient['name']);
            
            // Send email
            $mail->send();
            $sent_count++;
            
            // Small delay to prevent overwhelming the SMTP server
            usleep(100000); // 0.1 second delay
            
        } catch (Exception $e) {
            $failed_emails[] = $recipient['email'] . ' (' . $e->getMessage() . ')';
        }
    }

    // Log the activity
    $details = "Sent announcement '{$subject}' to {$sent_count} recipients";
    if (!empty($failed_emails)) {
        $details .= ". Failed: " . count($failed_emails) . " emails";
    }
    
    $log_stmt = $conn->prepare("INSERT INTO audit_trail (user_id, action, details, timestamp) VALUES (?, 'send_announcement', ?, NOW())");
    $log_stmt->bind_param('is', $user_id, $details);
    $log_stmt->execute();
    $log_stmt->close();

    $conn->close();

    // Return response
    if ($sent_count > 0) {
        $response = [
            'success' => true,
            'count' => $sent_count,
            'message' => "Announcement sent successfully to {$sent_count} recipients"
        ];
        
        if (!empty($failed_emails)) {
            $response['warning'] = count($failed_emails) . " emails failed to send";
            $response['failed_emails'] = $failed_emails;
        }
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send announcement to any recipients',
            'failed_emails' => $failed_emails
        ]);
    }

} catch (Exception $e) {
    error_log('Send Announcement Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while sending the announcement: ' . $e->getMessage()
    ]);
}
?>
