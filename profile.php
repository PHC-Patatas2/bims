<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare('SELECT username, first_name, last_name, email FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($username, $first_name, $last_name, $email);
$stmt->fetch();
$stmt->close();

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $new_first_name = trim($_POST['first_name'] ?? '');
    $new_last_name = trim($_POST['last_name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    if ($new_username && $new_first_name && $new_last_name && $new_email) {
        $stmt = $conn->prepare('UPDATE users SET username=?, first_name=?, last_name=?, email=?, updated_at=NOW() WHERE id=?');
        $stmt->bind_param('ssssi', $new_username, $new_first_name, $new_last_name, $new_email, $user_id);
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            $username = $new_username;
            $first_name = $new_first_name;
            $last_name = $new_last_name;
            $email = $new_email;
            $_SESSION['username'] = $username;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
        } else {
            $error = 'Failed to update profile.';
        }
        $stmt->close();
    } else {
        $error = 'All fields are required.';
    }
}

$system_title = 'Resident Information and Certification Management System';
$title_result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key='system_title' LIMIT 1");
if ($title_result && $title_row = $title_result->fetch_assoc()) {
    if (!empty($title_row['setting_value'])) {
        $system_title = $title_row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <style>
        .fade-out { transition: opacity 0.5s; opacity: 0; }
        .profile-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-group input:focus + .input-label,
        .input-group input:not(:placeholder-shown) + .input-label {
            transform: translateY(-1.5rem) scale(0.85);
            color: #3b82f6;
        }
        .input-label {
            position: absolute;
            left: 0.75rem;
            top: 0.75rem;
            color: #6b7280;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            pointer-events: none;
            background: white;
            padding: 0 0.25rem;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #f9fafb;
            transition: all 0.2s ease-in-out;
            font-size: 1rem;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .profile-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .notification {
            animation: slideInDown 0.5s ease-out;
        }
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
    <!-- Background Pattern -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-2000"></div>
        <div class="absolute top-40 left-40 w-80 h-80 bg-indigo-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse animation-delay-4000"></div>
    </div>

    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl profile-card rounded-2xl p-8 relative overflow-hidden">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <div class="profile-avatar w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <i class="fas fa-user text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">My Profile</h1>
                <p class="text-gray-600">Manage your account information</p>
            </div>
            <!-- Notifications -->
            <?php if ($success): ?>
                <div id="successBox" class="notification mb-6 bg-gradient-to-r from-green-400 to-green-600 text-white px-6 py-4 rounded-xl shadow-lg border-l-4 border-green-300">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-xl mr-3"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
                <script>
                    setTimeout(function() {
                        var box = document.getElementById('successBox');
                        if (box) { 
                            box.style.transform = 'translateY(-100%)';
                            box.style.opacity = '0';
                            setTimeout(() => box.style.display = 'none', 500); 
                        }
                    }, 4000);
                </script>
            <?php endif; ?>
            <?php if ($error): ?>
                <div id="errorBox" class="notification mb-6 bg-gradient-to-r from-red-400 to-red-600 text-white px-6 py-4 rounded-xl shadow-lg border-l-4 border-red-300">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-xl mr-3"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
                <script>
                    setTimeout(function() {
                        var box = document.getElementById('errorBox');
                        if (box) { 
                            box.style.transform = 'translateY(-100%)';
                            box.style.opacity = '0';
                            setTimeout(() => box.style.display = 'none', 500); 
                        }
                    }, 4000);
                </script>
            <?php endif; ?>
            <!-- Profile Form -->
            <form method="post" class="space-y-6">
                <!-- Username Field (Read-only) -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-gray-500"></i>Username
                    </label>
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 border-2 border-gray-200 rounded-xl px-4 py-3 cursor-not-allowed">
                        <span class="text-gray-600 font-medium"><?php echo htmlspecialchars($username); ?></span>
                        <span class="text-xs text-gray-500 ml-2">(Cannot be changed)</span>
                    </div>
                </div>

                <!-- Name Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="first_name" 
                            value="<?php echo htmlspecialchars($first_name); ?>" 
                            class="form-input" 
                            placeholder=" "
                            required 
                        />
                        <label class="input-label">
                            <i class="fas fa-user-tag mr-1"></i>First Name
                        </label>
                    </div>
                    <div class="input-group">
                        <input 
                            type="text" 
                            name="last_name" 
                            value="<?php echo htmlspecialchars($last_name); ?>" 
                            class="form-input" 
                            placeholder=" "
                            required 
                        />
                        <label class="input-label">
                            <i class="fas fa-user-tag mr-1"></i>Last Name
                        </label>
                    </div>
                </div>

                <!-- Email Field -->
                <div class="input-group">
                    <input 
                        type="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($email); ?>" 
                        class="form-input" 
                        placeholder=" "
                        required 
                    />
                    <label class="input-label">
                        <i class="fas fa-envelope mr-1"></i>Email Address
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6">
                    <button 
                        type="submit" 
                        class="btn-primary flex-1 text-white font-bold py-3 px-6 rounded-xl shadow-lg focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-300"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Save Changes
                    </button>
                    <a 
                        href="dashboard.php" 
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-xl shadow-lg text-center transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-gray-300"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>

                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>" />
            </form>

            <!-- Additional Info -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-center text-sm text-gray-500">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Your information is secure and encrypted
                </div>
            </div>
        </div>
    </div>

    <!-- Add some interactive JavaScript -->
    <script>
        // Add form validation feedback
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-input');
            
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.style.borderColor = '#ef4444';
                        this.parentElement.querySelector('.input-label').style.color = '#ef4444';
                    } else {
                        this.style.borderColor = '#10b981';
                        this.parentElement.querySelector('.input-label').style.color = '#10b981';
                    }
                });

                input.addEventListener('focus', function() {
                    this.style.borderColor = '#3b82f6';
                    this.parentElement.querySelector('.input-label').style.color = '#3b82f6';
                });
            });

            // Add loading state to submit button
            const form = document.querySelector('form');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function() {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>
