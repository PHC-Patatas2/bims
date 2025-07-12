<?php
require_once 'config.php';
$stmt = $pdo->prepare('SELECT reset_token, reset_token_expires_at FROM users WHERE username = ?');
$stmt->execute(['secretary']);
$user = $stmt->fetch();
echo "OTP: " . $user['reset_token'] . "\n";
echo "Expires: " . $user['reset_token_expires_at'] . "\n";
?>
