<?php
// email_config.php
// Email configuration for sending announcements

// SMTP Configuration - Update these settings according to your email provider
define('SMTP_HOST', 'smtp.gmail.com'); // For Gmail
define('SMTP_PORT', 587); // For TLS
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_USERNAME', 'bimssys@gmail.com'); // Your email address
define('SMTP_PASSWORD', 'rdya adur qaji qmer'); // Your app password
define('SMTP_FROM_EMAIL', 'bimssys@gmail.com'); // From email address
define('SMTP_FROM_NAME', 'BIMS System'); // From name

// Other email settings
define('EMAIL_CHARSET', 'UTF-8');
define('EMAIL_DEBUG', 0); // Set to 2 for debug mode, 0 for production

/* 
INSTRUCTIONS FOR GMAIL SETUP:
1. Go to your Google Account settings: https://myaccount.google.com/
2. Enable 2-Factor Authentication
3. Go to Security → App passwords
4. Generate an App Password for "Mail"
5. Use the App Password (16 characters) in SMTP_PASSWORD above
6. Update SMTP_USERNAME and SMTP_FROM_EMAIL with your Gmail address

QUICK TEST:
After configuring, visit: http://your-domain/bims/test_email.php to test the connection

FOR OTHER EMAIL PROVIDERS:
- Outlook/Hotmail: smtp-mail.outlook.com, port 587, TLS
- Yahoo: smtp.mail.yahoo.com, port 587, TLS
- Custom SMTP: Contact your hosting provider for settings

SECURITY NOTES:
- Never use your regular email password
- Always use App Passwords for Gmail/Google Workspace
- Keep this file secure and never commit passwords to version control
*/
?>
