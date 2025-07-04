<?php
session_start();
require_once 'config.php';

// Fetch system title and logo path
$system_title = 'Resident Information and Certification Management System';
$logo_path = 'img/logo.png';
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('system_title', 'barangay_logo_path')");
    while ($row = $stmt->fetch()) {
        if ($row['setting_key'] === 'system_title') $system_title = $row['setting_value'];
        if ($row['setting_key'] === 'barangay_logo_path') $logo_path = $row['setting_value'];
    }
} catch (Exception $e) {}

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
            // You can add role support here if you add a role column in the future
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'The username or password you entered is incorrect. Please try again.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}

// Handle forgot password AJAX check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_check']) && isset($_POST['credential'])) {
    $credential = trim($_POST['credential']);
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$credential, $credential]);
    $found = $stmt->fetch() ? true : false;
    header('Content-Type: application/json');
    echo json_encode(['found' => $found]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" integrity="sha512-u3fPA7V/q_dR0APDDUuOzvKFBBHlAwKRj5lHZRt1gs3osuTRswblYIWkxVAqkSgM3/CaHXMwEcOuc_2Nqbuhmw==" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <style>
        .fade-out { transition: opacity 0.5s; opacity: 0; }
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
                <a href="#" id="forgotPasswordLink" class="text-sm text-blue-600 hover:underline ml-2" style="white-space:nowrap;">Forgot password?</a>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded transition mt-4">Login</button>
        </form>
    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden transition-opacity duration-300">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6 relative transform transition-all duration-300 scale-95 opacity-0" id="forgotPasswordModalContent">
            <button id="closeForgotPasswordModal" class="absolute top-2 right-2 text-gray-400 hover:text-red-600 text-xl focus:outline-none" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="text-xl font-bold text-blue-700 mb-2 text-center">Forgot Password</h2>
            <p class="text-gray-600 text-sm mb-6 text-center">Enter your username or email to recover your account.</p>
            <form id="forgotPasswordForm" class="flex flex-col gap-4">
                <input type="text" id="forgot_credential" name="forgot_credential" class="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required placeholder="Username or Email">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded transition">Submit</button>
            </form>
            <div id="forgotPasswordMessage" class="mt-2 text-sm text-center"></div>
            <div id="forgotPasswordSuccess" class="hidden flex flex-col items-center gap-6 mt-6">
                <div class="text-green-600 text-sm text-center mb-4">If the account exists, password recovery instructions will be sent.</div>
                <button id="closeForgotPasswordSuccess" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded transition">Close</button>
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
        const forgotPasswordMessage = document.getElementById('forgotPasswordMessage');

        if (forgotPasswordLink && forgotPasswordModal) {
            forgotPasswordLink.addEventListener('click', function(e) {
                e.preventDefault();
                forgotPasswordModal.classList.remove('hidden');
                // Animate modal fade/scale in
                setTimeout(function() {
                    forgotPasswordModal.classList.add('opacity-100');
                    var modalContent = document.getElementById('forgotPasswordModalContent');
                    if (modalContent) {
                        modalContent.classList.remove('scale-95', 'opacity-0');
                        modalContent.classList.add('scale-100', 'opacity-100');
                    }
                }, 10);
            });
        }
        if (closeForgotPasswordModal && forgotPasswordModal) {
            closeForgotPasswordModal.addEventListener('click', function() {
                // Animate modal fade/scale out
                forgotPasswordModal.classList.remove('opacity-100');
                var modalContent = document.getElementById('forgotPasswordModalContent');
                if (modalContent) {
                    modalContent.classList.remove('scale-100', 'opacity-100');
                    modalContent.classList.add('scale-95', 'opacity-0');
                }
                setTimeout(function() {
                    forgotPasswordModal.classList.add('hidden');
                    forgotPasswordForm.reset();
                    forgotPasswordMessage.textContent = '';
                }, 250);
            });
        }
        // Optional: Close modal when clicking outside the modal content
        if (forgotPasswordModal) {
            forgotPasswordModal.addEventListener('click', function(e) {
                if (e.target === forgotPasswordModal) {
                    // Animate modal fade/scale out
                    forgotPasswordModal.classList.remove('opacity-100');
                    var modalContent = document.getElementById('forgotPasswordModalContent');
                    if (modalContent) {
                        modalContent.classList.remove('scale-100', 'opacity-100');
                        modalContent.classList.add('scale-95', 'opacity-0');
                    }
                    setTimeout(function() {
                        forgotPasswordModal.classList.add('hidden');
                        forgotPasswordForm.reset();
                        forgotPasswordMessage.textContent = '';
                    }, 250);
                }
            });
        }
        // AJAX handler for forgot password form
        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const credential = document.getElementById('forgot_credential').value.trim();
                if (!credential) {
                    forgotPasswordMessage.textContent = 'Please enter your username or email.';
                    forgotPasswordMessage.className = 'mt-2 text-sm text-center text-red-600';
                    return;
                }
                // AJAX request to check if user exists
                fetch('login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'forgot_check=1&credential=' + encodeURIComponent(credential)
                })
                .then(response => response.json())
                .then(data => {
                    // Hide form, show success message and close button
                    forgotPasswordForm.classList.add('hidden');
                    document.getElementById('forgotPasswordSuccess').classList.remove('hidden');
                    forgotPasswordMessage.textContent = '';
                })
                .catch(() => {
                    forgotPasswordMessage.textContent = 'An error occurred. Please try again.';
                    forgotPasswordMessage.className = 'mt-2 text-sm text-center text-red-600';
                });
            });
        }
        // Close button for success message
        const closeForgotPasswordSuccess = document.getElementById('closeForgotPasswordSuccess');
        if (closeForgotPasswordSuccess) {
            closeForgotPasswordSuccess.addEventListener('click', function() {
                // Animate modal fade/scale out
                forgotPasswordModal.classList.remove('opacity-100');
                var modalContent = document.getElementById('forgotPasswordModalContent');
                if (modalContent) {
                    modalContent.classList.remove('scale-100', 'opacity-100');
                    modalContent.classList.add('scale-95', 'opacity-0');
                }
                setTimeout(function() {
                    forgotPasswordModal.classList.add('hidden');
                    forgotPasswordForm.reset();
                    forgotPasswordForm.classList.remove('hidden');
                    document.getElementById('forgotPasswordSuccess').classList.add('hidden');
                    forgotPasswordMessage.textContent = '';
                }, 250);
            });
        }
    </script>
    <style>
        /* Smooth modal fade/scale effect */
        #forgotPasswordModal.opacity-100 { opacity: 1; }
        #forgotPasswordModal { opacity: 0; transition: opacity 0.3s; }
        #forgotPasswordModalContent { transition: transform 0.3s, opacity 0.3s; }
        #forgotPasswordModalContent.scale-100 { transform: scale(1); opacity: 1; }
        #forgotPasswordModalContent.scale-95 { transform: scale(0.95); opacity: 0; }
        input[type="text"], input[type="password"] {
            transition: box-shadow 0.2s, border-color 0.2s;
        }
    </style>
</body>
</html>