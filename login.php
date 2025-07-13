<?php
session_start();

// Security: If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

require_once 'config.php';

// Fetch system title and logo path
$system_title = 'Resident Information and Certification Management System';
$logo_path = 'img/logo.png';
try {
    $stmt = $pdo->query("S        // Close button on success step
        document.getElementById('closeSuccess')?.addEventListener('click', closeForgotPasswordModalHandler);

        // Alias for backward compatibility
        function closeForgotPasswordModal() {
            closeForgotPasswordModalHandler();
        }ECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('system_title', 'barangay_logo_path')");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'system_title') $system_title = $row['setting_value'];
        if ($row['setting_key'] === 'barangay_logo_path') $logo_path = $row['setting_value'];
    }
} catch (Exception $e) {}

// Include audit logging functions
require_once 'audit_logger.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            
            // Log successful login
            logLogin($user['id'], $username, true, [
                'login_method' => 'username_password',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            // You can add role support here if you add a role column in the future
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'The username or password you entered is incorrect. Please try again.';
            
            // Log failed login attempt
            logLogin($user['id'] ?? null, $username, false, [
                'reason' => 'invalid_credentials',
                'attempted_username' => $username
            ]);
        }
    } else {
        $error = 'Please enter both username and password.';
        
        // Log incomplete login attempt
        logLogin(null, $username, false, [
            'reason' => 'missing_credentials',
            'username_provided' => !empty($username),
            'password_provided' => !empty($password)
        ]);
    }
}

// Handle forgot password AJAX check - REMOVED (now handled by forgot_password.php)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .fade-out { transition: opacity 0.5s; opacity: 0; }
        
        /* Smooth modal fade/scale effect */
        #forgotPasswordModal {
            opacity: 0; 
            transition: opacity 0.3s;
        }
        #forgotPasswordModal.show {
            display: flex !important;
        }
        #forgotPasswordModal.opacity-100 { 
            opacity: 1; 
        }
        #forgotPasswordModalContent { 
            transition: transform 0.3s, opacity 0.3s; 
        }
        #forgotPasswordModalContent.scale-100 { 
            transform: scale(1); 
            opacity: 1; 
        }
        #forgotPasswordModalContent.scale-95 { 
            transform: scale(0.95); 
            opacity: 0; 
        }
        
        input[type="text"], input[type="password"], input[type="email"] {
            transition: box-shadow 0.2s, border-color 0.2s;
        }
        
        .forgot-step {
            transition: opacity 0.3s ease-in-out;
        }
        
        /* OTP input styling */
        #otp_code {
            letter-spacing: 0.5em;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 flex flex-col items-center">
        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Barangay Logo" class="w-24 h-24 mb-4 rounded-full border-2 border-blue-500 object-cover">
        <div class="text-lg font-bold text-gray-700 mb-1 text-center tracking-wide" style="letter-spacing:0.5px;">Sucol, Calumpit, Bulacan</div>
        <h1 class="text-2xl font-bold text-blue-700 mb-6 text-center"><?php echo htmlspecialchars($system_title); ?></h1>
        <?php if ($error): ?>
            <div id="errorBox" class="w-full bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-center animate-pulse">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <script>
                setTimeout(function() {
                    var box = document.getElementById('errorBox');
                    if (box) { box.classList.add('fade-out'); setTimeout(() => box.style.display = 'none', 500); }
                }, 3000);
            </script>
        <?php endif; ?>
        <form method="post" class="w-full flex flex-col gap-4">
            <div>
                <input type="text" id="username" name="username" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-200 focus:shadow-lg hover:shadow-md hover:border-blue-400" required autofocus autocomplete="username" placeholder="Username or Email">
            </div>
            <div>
                <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-200 focus:shadow-lg hover:shadow-md hover:border-blue-400" required autocomplete="current-password" placeholder="Password">
            </div>
            <div class="flex items-center mt-2 justify-between">
                <div class="flex items-center">
                    <input id="show-password" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="show-password" class="ml-2 block text-sm text-gray-900">Show Password</label>
                </div>
                <button type="button" id="forgotPasswordLink" class="text-sm text-blue-600 hover:underline ml-2" style="white-space:nowrap; background: none; border: none; cursor: pointer;">Forgot password?</button>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded transition mt-4">Login</button>
        </form>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-40 hidden transition-opacity duration-300" style="display: none;">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 relative transform transition-all duration-300 scale-95 opacity-0" id="forgotPasswordModalContent">
            <button id="closeForgotPasswordModal" class="absolute top-2 right-2 text-gray-400 hover:text-red-600 text-xl focus:outline-none" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Step 1: Enter Username/Email -->
            <div id="step1" class="forgot-step">
                <h2 class="text-xl font-bold text-blue-700 mb-2 text-center">Forgot Password</h2>
                <p class="text-gray-600 text-sm mb-6 text-center">Enter your username or email to receive a verification code.</p>
                <form id="forgotPasswordForm" class="flex flex-col gap-4">
                    <input type="text" id="forgot_credential" name="forgot_credential" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required placeholder="Username or Email">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded transition">Send Verification Code</button>
                </form>
                <div id="step1Message" class="mt-2 text-sm text-center"></div>
            </div>
            
            <!-- Step 2: Enter OTP -->
            <div id="step2" class="forgot-step hidden">
                <h2 class="text-xl font-bold text-blue-700 mb-2 text-center">Enter Verification Code</h2>
                <p class="text-gray-600 text-sm mb-6 text-center">Check your email for a 6-digit verification code.</p>
                <form id="otpForm" class="flex flex-col gap-4">
                    <input type="hidden" id="otp_credential" name="credential">
                    <input type="text" id="otp_code" name="otp" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 text-center text-2xl font-mono" required placeholder="123456" maxlength="6" pattern="\d{6}">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded transition">Verify Code</button>
                    <button type="button" id="backToStep1" class="text-blue-600 hover:underline text-sm">Back to Username/Email</button>
                </form>
                <div id="step2Message" class="mt-2 text-sm text-center"></div>
            </div>
            
            <!-- Step 3: Reset Password -->
            <div id="step3" class="forgot-step hidden">
                <h2 class="text-xl font-bold text-blue-700 mb-2 text-center">Set New Password</h2>
                <p class="text-gray-600 text-sm mb-6 text-center">Enter your new password below.</p>
                <form id="passwordResetForm" class="flex flex-col gap-4">
                    <input type="password" id="new_password" name="new_password" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required placeholder="New Password" minlength="8">
                    <input type="password" id="confirm_password" name="confirm_password" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required placeholder="Confirm Password" minlength="8">
                    <p class="text-xs text-gray-500">Password must be at least 8 characters long.</p>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded transition">Reset Password</button>
                </form>
                <div id="step3Message" class="mt-2 text-sm text-center"></div>
            </div>
            
            <!-- Success Step -->
            <div id="successStep" class="forgot-step hidden">
                <div class="text-center">
                    <div class="text-green-600 text-5xl mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="text-xl font-bold text-green-700 mb-2">Password Reset Successful!</h2>
                    <p class="text-gray-600 text-sm mb-6">Your password has been successfully reset. You can now log in with your new password.</p>
                    <button id="closeSuccess" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded transition">Continue to Login</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const showPasswordCheckbox = document.getElementById('show-password');
        const passwordInput = document.getElementById('password');

        showPasswordCheckbox.addEventListener('change', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
        });

        // Forgot Password Modal Logic
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');
        const forgotPasswordModal = document.getElementById('forgotPasswordModal');
        const closeForgotPasswordModal = document.getElementById('closeForgotPasswordModal');
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        const otpForm = document.getElementById('otpForm');
        const passwordResetForm = document.getElementById('passwordResetForm');

        // Show modal
        if (forgotPasswordLink && forgotPasswordModal) {
            forgotPasswordLink.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Forgot password link clicked!');
                showForgotPasswordModal();
                showStep(1);
            });
            console.log('Forgot password event listener attached');
        } else {
            console.log('Elements not found:', {
                link: !!forgotPasswordLink,
                modal: !!forgotPasswordModal
            });
        }

        // Close modal
        if (closeForgotPasswordModal) {
            closeForgotPasswordModal.addEventListener('click', function(e) {
                e.preventDefault();
                closeForgotPasswordModalHandler();
            });
        }

        // Close modal when clicking outside
        if (forgotPasswordModal) {
            forgotPasswordModal.addEventListener('click', function(e) {
                if (e.target === forgotPasswordModal) {
                    closeForgotPasswordModalHandler();
                }
            });
        }

        // Close button on success step
        document.getElementById('closeSuccess')?.addEventListener('click', closeForgotPasswordModalHandler);

        // Back to step 1 button
        document.getElementById('backToStep1')?.addEventListener('click', function() {
            showStep(1);
        });

        // Step 1: Send verification code
        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const credential = document.getElementById('forgot_credential').value.trim();
                const messageEl = document.getElementById('step1Message');
                
                if (!credential) {
                    showMessage(messageEl, 'Please enter your username or email address', 'error');
                    return;
                }

                showMessage(messageEl, 'Sending verification code...', 'info');

                fetch('forgot_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'credential=' + encodeURIComponent(credential)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Forgot password response:', data);
                    if (data.success) {
                        showMessage(messageEl, data.message, 'success');
                        document.getElementById('otp_credential').value = credential;
                        setTimeout(() => showStep(2), 1500);
                    } else {
                        showMessage(messageEl, data.message, 'error');
                    }
                })
                .catch(() => {
                    showMessage(messageEl, 'An error occurred. Please try again.', 'error');
                });
            });
        }

        // Step 2: Verify OTP
        if (otpForm) {
            otpForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const credential = document.getElementById('otp_credential').value;
                const otp = document.getElementById('otp_code').value.trim();
                const messageEl = document.getElementById('step2Message');
                
                if (!otp || otp.length !== 6) {
                    showMessage(messageEl, 'Please enter a valid 6-digit verification code', 'error');
                    return;
                }

                showMessage(messageEl, 'Verifying code...', 'info');

                fetch('verify_reset.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=verify_otp&credential=' + encodeURIComponent(credential) + '&otp=' + encodeURIComponent(otp)
                })
                .then(response => response.json())
                .then(data => {
                    console.log('OTP verification response:', data);
                    if (data.success) {
                        showMessage(messageEl, data.message, 'success');
                        setTimeout(() => showStep(3), 1500);
                    } else {
                        showMessage(messageEl, data.message, 'error');
                    }
                })
                .catch(() => {
                    showMessage(messageEl, 'An error occurred. Please try again.', 'error');
                });
            });
        }

        // Step 3: Reset password
        if (passwordResetForm) {
            passwordResetForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const messageEl = document.getElementById('step3Message');
                
                if (!newPassword || !confirmPassword) {
                    showMessage(messageEl, 'Please fill in both password fields', 'error');
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    showMessage(messageEl, 'Passwords do not match', 'error');
                    return;
                }
                
                if (newPassword.length < 8) {
                    showMessage(messageEl, 'Password must be at least 8 characters long', 'error');
                    return;
                }

                showMessage(messageEl, 'Resetting password...', 'info');

                fetch('verify_reset.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=reset_password&new_password=' + encodeURIComponent(newPassword) + '&confirm_password=' + encodeURIComponent(confirmPassword)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(messageEl, data.message, 'success');
                        setTimeout(() => showStep('success'), 1500);
                    } else {
                        showMessage(messageEl, data.message, 'error');
                    }
                })
                .catch(() => {
                    showMessage(messageEl, 'An error occurred. Please try again.', 'error');
                });
            });
        }

        // Auto-format OTP input
        document.getElementById('otp_code')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 6);
        });

        // Utility functions
        function showForgotPasswordModal() {
            console.log('Showing forgot password modal');
            
            // Show the modal
            forgotPasswordModal.style.display = 'flex';
            forgotPasswordModal.classList.remove('hidden');
            
            // Trigger animation after a short delay
            setTimeout(() => {
                forgotPasswordModal.classList.add('opacity-100');
                const modalContent = document.getElementById('forgotPasswordModalContent');
                if (modalContent) {
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }
            }, 50);
        }

        function closeForgotPasswordModalHandler() {
            console.log('Closing forgot password modal');
            
            // Start closing animation
            forgotPasswordModal.classList.remove('opacity-100');
            const modalContent = document.getElementById('forgotPasswordModalContent');
            if (modalContent) {
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');
            }
            
            // Hide the modal after animation
            setTimeout(() => {
                forgotPasswordModal.style.display = 'none';
                forgotPasswordModal.classList.add('hidden');
                resetForgotPasswordModal();
            }, 300);
        }

        function resetForgotPasswordModal() {
            // Reset all forms
            forgotPasswordForm?.reset();
            otpForm?.reset();
            passwordResetForm?.reset();
            
            // Clear all messages
            ['step1Message', 'step2Message', 'step3Message'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '';
            });
            
            // Show step 1
            showStep(1);
        }

        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.forgot-step').forEach(el => el.classList.add('hidden'));
            
            // Show target step
            const targetStep = step === 'success' ? 'successStep' : `step${step}`;
            const stepEl = document.getElementById(targetStep);
            if (stepEl) {
                stepEl.classList.remove('hidden');
                
                // Focus on first input
                const firstInput = stepEl.querySelector('input:not([type="hidden"])');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            }
        }

        function showMessage(element, message, type) {
            if (!element) return;
            
            element.textContent = message;
            element.className = 'mt-2 text-sm text-center ';
            
            switch(type) {
                case 'success':
                    element.className += 'text-green-600';
                    break;
                case 'error':
                    element.className += 'text-red-600';
                    break;
                case 'info':
                    element.className += 'text-blue-600';
                    break;
                default:
                    element.className += 'text-gray-600';
            }
        }
    </script>
</body>
</html>