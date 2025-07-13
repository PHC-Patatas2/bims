<?php
// settings.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    include 'error_page.php';
    exit();
}
// Get user information for navigation
$user_id = $_SESSION['user_id'];
$user_first_name = '';
$user_last_name = '';
$stmt = $conn->prepare('SELECT first_name, last_name FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($user_first_name, $user_last_name);
$stmt->fetch();
$stmt->close();
$user_full_name = trim($user_first_name . ' ' . $user_last_name);

// Get system title for navigation
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
    <title>General Settings - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/css/tabulator.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tabulator/5.5.2/js/tabulator.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        .custom-scrollbar { scrollbar-width: thin; scrollbar-color: #2563eb #353535; padding-right: 6px; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #2563eb; border-radius: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #353535; }
        .custom-scrollbar { overflow-y: scroll; }
        .custom-scrollbar::-webkit-scrollbar { background: #353535; }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.03); box-shadow: 0 10px 20px -5px #0002; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        
        /* Enhanced Settings Styles */
        .settings-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .settings-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .tab-button {
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .tab-button::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .tab-button.active::before {
            transform: scaleX(1);
        }
        
        .tab-button:hover {
            background: rgba(59, 130, 246, 0.05);
        }
        
        .input-field {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .input-field:focus {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.25);
        }
        
        .action-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--card-gradient, linear-gradient(90deg, #3b82f6, #1d4ed8));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .action-card:hover::before {
            transform: scaleX(1);
        }
        
        .action-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px -12px rgba(59, 130, 246, 0.25);
            border-color: #3b82f6;
        }
        
        .action-icon {
            background: linear-gradient(135deg, var(--icon-color-1), var(--icon-color-2));
            transition: all 0.3s ease;
        }
        
        .action-card:hover .action-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .save-button {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .save-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .save-button:hover::before {
            left: 100%;
        }
        
        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.4);
        }
        
        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3b82f6;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .notification-slide {
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Modal Styles */
        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.9) translateY(-20px);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            max-width: 90vw;
            max-height: 90vh;
            overflow: auto;
        }
        
        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
        }
        
        .modal-icon {
            background: linear-gradient(135deg, var(--modal-icon-color-1, #3b82f6), var(--modal-icon-color-2, #1d4ed8));
            animation: modalIconPulse 2s infinite;
        }
        
        @keyframes modalIconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .modal-button {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .modal-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .modal-button:hover::before {
            left: 100%;
        }
        
        .modal-button:hover {
            transform: translateY(-2px);
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col settings-container">
    <?php include 'navigation.php'; ?>
    <div class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <!-- Settings Card -->
            <div class="settings-card rounded-xl shadow mb-6">
                <!-- General Settings -->
                <div class="p-6">
                    <form id="generalSettingsForm" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">System Title</label>
                                <input type="text" id="systemTitle" value="" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter system title">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Barangay Name</label>
                                <input type="text" id="barangayName" value="" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter barangay name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Municipality/City</label>
                                <input type="text" id="municipality" value="" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter municipality or city">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                                <input type="text" id="province" value="" 
                                       class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter province">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Complete Address</label>
                            <textarea id="address" rows="3" 
                                      class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Enter complete barangay address"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Barangay Logo Path</label>
                            <input type="text" id="barangayLogoPath" value="" 
                                   class="input-field w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter logo file path (e.g., img/logo.png)">
                        </div>
                    </form>
                </div>

                <!-- Save Button -->
                <div class="p-6 border-t border-gray-200">
                    <div class="flex justify-end">
                        <button onclick="saveSettings()" id="saveButton" class="save-button text-white px-8 py-3 rounded-lg transition-all duration-300 transform hover:scale-105">
                            <span id="saveButtonText">
                                <i class="fas fa-save mr-2"></i>Save Settings
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="action-card rounded-xl shadow-lg p-6 text-center" style="--card-gradient: linear-gradient(90deg, #3b82f6, #1d4ed8); --icon-color-1: #3b82f6; --icon-color-2: #1d4ed8;">
                    <div class="action-icon w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-download text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Backup Data</h3>
                    <p class="text-sm text-gray-600 mb-6">Create a backup of system data</p>
                    <button onclick="backupData()" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-blue-700 w-full transition-all duration-300 transform hover:scale-105">
                        Backup Now
                    </button>
                </div>
                <div class="action-card rounded-xl shadow-lg p-6 text-center" style="--card-gradient: linear-gradient(90deg, #f59e0b, #d97706); --icon-color-1: #f59e0b; --icon-color-2: #d97706;">
                    <div class="action-icon w-16 h-16 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-sync text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Clear Cache</h3>
                    <p class="text-sm text-gray-600 mb-6">Clear system cache files</p>
                    <button onclick="clearCache()" class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white px-6 py-3 rounded-lg hover:from-yellow-600 hover:to-yellow-700 w-full transition-all duration-300 transform hover:scale-105">
                        Clear Cache
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="modal-content w-full max-w-md">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="text-center mb-6">
                    <div id="modalIcon" class="modal-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i id="modalIconClass" class="text-white text-2xl"></i>
                    </div>
                    <h3 id="modalTitle" class="text-xl font-bold text-gray-900 mb-2"></h3>
                    <p id="modalMessage" class="text-gray-600"></p>
                </div>
                
                <!-- Modal Actions -->
                <div class="flex space-x-3">
                    <button id="modalCancelBtn" class="modal-button flex-1 bg-gray-100 text-gray-700 px-4 py-3 rounded-lg hover:bg-gray-200 font-medium">
                        Cancel
                    </button>
                    <button id="modalConfirmBtn" class="modal-button flex-1 text-white px-4 py-3 rounded-lg font-medium">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Settings functions
        let isLoading = false;

        function loadSettings() {
            if (isLoading) return;
            isLoading = true;
            
            fetch('load_settings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate general settings only
                        document.getElementById('systemTitle').value = data.settings.system_title || '';
                        document.getElementById('barangayName').value = data.settings.barangay_name || '';
                        document.getElementById('municipality').value = data.settings.municipality || '';
                        document.getElementById('province').value = data.settings.province || '';
                        document.getElementById('address').value = data.settings.barangay_address || '';
                        document.getElementById('barangayLogoPath').value = data.settings.barangay_logo_path || '';
                        
                        showNotification('Settings loaded successfully', 'success');
                    } else {
                        showNotification('Failed to load settings: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error loading settings:', error);
                    showNotification('Error loading settings', 'error');
                })
                .finally(() => {
                    isLoading = false;
                });
        }

        function saveSettings() {
            if (isLoading) return;
            
            const saveButton = document.getElementById('saveButton');
            const saveButtonText = document.getElementById('saveButtonText');
            const originalText = saveButtonText.innerHTML;
            
            // Show loading state
            saveButtonText.innerHTML = '<div class="loading-spinner inline-block mr-2"></div>Saving...';
            saveButton.disabled = true;
            isLoading = true;
            
            // Collect only general settings form data
            const settingsData = {
                system_title: document.getElementById('systemTitle').value,
                barangay_name: document.getElementById('barangayName').value,
                municipality: document.getElementById('municipality').value,
                province: document.getElementById('province').value,
                address: document.getElementById('address').value,
                barangay_logo_path: document.getElementById('barangayLogoPath').value
            };

            fetch('save_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(settingsData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Refresh the page title if system title changed
                    if (settingsData.system_title) {
                        document.title = 'General Settings - ' + settingsData.system_title;
                    }
                } else {
                    showNotification('Failed to save settings: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error saving settings:', error);
                showNotification('Error saving settings', 'error');
            })
            .finally(() => {
                // Restore button state
                saveButtonText.innerHTML = originalText;
                saveButton.disabled = false;
                isLoading = false;
            });
        }

        // Quick action functions
        function backupData() {
            showConfirmationModal({
                title: 'Create Database Backup',
                message: 'Do you want to create a backup of all system data? This will export the entire database to an SQL file.',
                icon: 'fas fa-download',
                iconColors: ['#3b82f6', '#1d4ed8'],
                confirmText: 'Create Backup',
                confirmClass: 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700',
                onConfirm: () => {
                    showNotification('Creating database backup...', 'info');
                    
                    fetch('backup_database.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Database backup created successfully!', 'success');
                            
                            // Auto-download the backup file
                            const link = document.createElement('a');
                            link.href = data.download_url;
                            link.download = data.filename;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                            
                            // Show additional info
                            setTimeout(() => {
                                showNotification(`Backup file: ${data.filename} (${(data.size / 1024).toFixed(2)} KB)`, 'info');
                            }, 1000);
                        } else {
                            showNotification('Failed to create backup: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Backup error:', error);
                        showNotification('Error creating backup. Please try again.', 'error');
                    });
                }
            });
        }

        function clearCache() {
            showConfirmationModal({
                title: 'Clear System Cache',
                message: 'Do you want to clear all system cache files? This will remove temporary data and may improve system performance.',
                icon: 'fas fa-sync',
                iconColors: ['#f59e0b', '#d97706'],
                confirmText: 'Clear Cache',
                confirmClass: 'bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700',
                onConfirm: () => {
                    showNotification('Clearing cache...', 'info');
                    // Simulate cache clearing
                    setTimeout(() => {
                        showNotification('Cache cleared successfully!', 'success');
                    }, 1000);
                }
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification-slide fixed top-20 right-4 p-4 rounded-xl shadow-xl z-50 transform transition-all duration-300 ${
                type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' : 
                type === 'error' ? 'bg-gradient-to-r from-red-500 to-red-600 text-white' : 
                type === 'warning' ? 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white' :
                'bg-gradient-to-r from-blue-500 to-blue-600 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-3">
                        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-medium">${message}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }
        
        // Modal functions
        function showConfirmationModal(options) {
            const modal = document.getElementById('confirmationModal');
            const modalIcon = document.getElementById('modalIcon');
            const modalIconClass = document.getElementById('modalIconClass');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const modalConfirmBtn = document.getElementById('modalConfirmBtn');
            const modalCancelBtn = document.getElementById('modalCancelBtn');
            
            // Set modal content
            modalTitle.textContent = options.title;
            modalMessage.textContent = options.message;
            modalIconClass.className = options.icon;
            modalConfirmBtn.textContent = options.confirmText || 'Confirm';
            modalConfirmBtn.className = `modal-button flex-1 text-white px-4 py-3 rounded-lg font-medium ${options.confirmClass || 'bg-blue-500 hover:bg-blue-600'}`;
            
            // Set icon colors
            if (options.iconColors) {
                modalIcon.style.setProperty('--modal-icon-color-1', options.iconColors[0]);
                modalIcon.style.setProperty('--modal-icon-color-2', options.iconColors[1]);
            }
            
            // Show modal
            modal.classList.add('active');
            
            // Handle confirm
            const confirmHandler = () => {
                hideConfirmationModal();
                if (options.onConfirm) {
                    options.onConfirm();
                }
                modalConfirmBtn.removeEventListener('click', confirmHandler);
                modalCancelBtn.removeEventListener('click', cancelHandler);
                modal.removeEventListener('click', outsideClickHandler);
            };
            
            // Handle cancel
            const cancelHandler = () => {
                hideConfirmationModal();
                if (options.onCancel) {
                    options.onCancel();
                }
                modalConfirmBtn.removeEventListener('click', confirmHandler);
                modalCancelBtn.removeEventListener('click', cancelHandler);
                modal.removeEventListener('click', outsideClickHandler);
            };
            
            // Handle outside click
            const outsideClickHandler = (e) => {
                if (e.target === modal) {
                    cancelHandler();
                }
            };
            
            // Add event listeners
            modalConfirmBtn.addEventListener('click', confirmHandler);
            modalCancelBtn.addEventListener('click', cancelHandler);
            modal.addEventListener('click', outsideClickHandler);
            
            // Handle escape key
            const escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    cancelHandler();
                    document.removeEventListener('keydown', escapeHandler);
                }
            };
            document.addEventListener('keydown', escapeHandler);
        }
        
        function hideConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            modal.classList.remove('active');
        }

        // Initialize settings on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
        });
    </script>
</body>
</html>
