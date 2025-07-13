<?php
// reports.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    include 'error_page.php';
    exit();
}
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
    <title>Generate Reports - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .custom-scrollbar { scrollbar-width: thin; scrollbar-color: #2563eb #353535; padding-right: 6px; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #2563eb; border-radius: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #353535; }
        .custom-scrollbar { overflow-y: scroll; }
        .custom-scrollbar::-webkit-scrollbar { background: #353535; }
        .sidebar-border { border-right: 1px solid #e5e7eb; }
        .dropdown-menu { display: none; position: absolute; right: 0; top: 100%; background: white; min-width: 180px; box-shadow: 0 4px 16px #0001; border-radius: 0.5rem; z-index: 50; }
        .dropdown-menu.show { display: block; }
        
        .report-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #d1d5db;
        }
        
        .report-icon {
            transition: transform 0.3s ease;
        }
        
        .report-card:hover .report-icon {
            transform: scale(1.1);
        }
        
        .report-button {
            transition: all 0.3s ease;
        }
        
        .report-button:hover {
            transform: translateY(-1px);
        }
        
        .report-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .report-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Include Navigation -->
    <?php include 'navigation.php'; ?>
    <!-- Main Content -->
    <div class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Generate Reports</h1>
                <div class="text-sm text-gray-600">
                    <i class="fas fa-calendar mr-2"></i>
                    <?php echo date('F d, Y'); ?>
                </div>
            </div>
            
            <!-- Summary Statistics Card -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Quick Statistics</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold" id="total-residents">---</div>
                                <div class="text-sm opacity-90">Total Residents</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold" id="total-voters">---</div>
                                <div class="text-sm opacity-90">Registered Voters</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold" id="total-seniors">---</div>
                                <div class="text-sm opacity-90">Senior Citizens</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold" id="total-certificates">---</div>
                                <div class="text-sm opacity-90">Certificates Issued</div>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <i class="fas fa-chart-bar text-6xl opacity-30"></i>
                    </div>
                </div>
            </div>
            
            <!-- Reports Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Resident Demographics Report -->
                <div class="report-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 fade-in">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4 report-icon">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Demographics Report</h3>
                            <p class="text-sm text-gray-600">Population statistics by age, gender, and civil status</p>
                        </div>
                    </div>
                    <button onclick="generateReport('demographics')" class="report-button w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Voter Statistics Report -->
                <div class="report-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 fade-in" style="animation-delay: 0.1s">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 p-3 rounded-lg mr-4 report-icon">
                            <i class="fas fa-vote-yea text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Voter Statistics</h3>
                            <p class="text-sm text-gray-600">Registered voters and registration rates</p>
                        </div>
                    </div>
                    <button onclick="generateReport('voters')" class="report-button w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- PWD Report -->
                <div class="report-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 fade-in" style="animation-delay: 0.2s">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 p-3 rounded-lg mr-4 report-icon">
                            <i class="fas fa-wheelchair text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">PWD Report</h3>
                            <p class="text-sm text-gray-600">Persons with Disabilities demographics</p>
                        </div>
                    </div>
                    <button onclick="generateReport('pwd')" class="report-button w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Senior Citizens Report -->
                <div class="report-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 fade-in" style="animation-delay: 0.3s">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 p-3 rounded-lg mr-4 report-icon">
                            <i class="fas fa-user-clock text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Senior Citizens</h3>
                            <p class="text-sm text-gray-600">Residents 60 years old and above</p>
                        </div>
                    </div>
                    <button onclick="generateReport('seniors')" class="report-button w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- 4Ps Recipients Report -->
                <div class="report-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 fade-in" style="animation-delay: 0.4s">
                    <div class="flex items-center mb-4">
                        <div class="bg-red-100 p-3 rounded-lg mr-4 report-icon">
                            <i class="fas fa-hand-holding-heart text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">4Ps Recipients</h3>
                            <p class="text-sm text-gray-600">Pantawid Pamilyang Pilipino beneficiaries</p>
                        </div>
                    </div>
                    <button onclick="generateReport('4ps')" class="report-button w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Solo Parents Report -->
                <div class="report-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 fade-in" style="animation-delay: 0.5s">
                    <div class="flex items-center mb-4">
                        <div class="bg-pink-100 p-3 rounded-lg mr-4 report-icon">
                            <i class="fas fa-user-friends text-pink-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Solo Parents</h3>
                            <p class="text-sm text-gray-600">Single parent households</p>
                        </div>
                    </div>
                    <button onclick="generateReport('solo_parents')" class="report-button w-full bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Certificate Statistics -->
                <div class="report-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 fade-in" style="animation-delay: 0.6s">
                    <div class="flex items-center mb-4">
                        <div class="bg-teal-100 p-3 rounded-lg mr-4 report-icon">
                            <i class="fas fa-certificate text-teal-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Certificate Statistics</h3>
                            <p class="text-sm text-gray-600">Issued certificates by type and period</p>
                        </div>
                    </div>
                    <button onclick="generateReport('certificates')" class="report-button w-full bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Purok Summary -->
                <div class="report-card bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6 fade-in" style="animation-delay: 0.7s">
                    <div class="flex items-center mb-4">
                        <div class="bg-yellow-100 p-3 rounded-lg mr-4 report-icon">
                            <i class="fas fa-map-marked-alt text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Purok Summary</h3>
                            <p class="text-sm text-gray-600">Population distribution by purok/sitio</p>
                        </div>
                    </div>
                    <button onclick="generateReport('purok')" class="report-button w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>
            </div>


        </div>
    </div>
    <script>
        // Report functions
        function generateReport(type) {
            // Show loading indicator
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
            button.disabled = true;

            // Create form to submit report request
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'generate_report.php';
            form.target = '_blank';
            
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'type';
            typeInput.value = type;
            
            const formatInput = document.createElement('input');
            formatInput.type = 'hidden';
            formatInput.name = 'format';
            formatInput.value = 'pdf';
            
            form.appendChild(typeInput);
            form.appendChild(formatInput);
            document.body.appendChild(form);
            
            // Submit form
            form.submit();
            document.body.removeChild(form);
            
            // Reset button after short delay
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                showNotification('Report generation started! Check your downloads.', 'success');
            }, 1000);
        }

        function showNotification(message, type = 'info') {
            // Simple notification system
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} mr-2"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }

        // Show loading animation for report cards on hover
        document.addEventListener('DOMContentLoaded', function() {
            const reportCards = document.querySelectorAll('.report-card');
            reportCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
            
            // Load quick statistics
            loadQuickStats();
        });
        
        function loadQuickStats() {
            fetch('get_quick_stats.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-residents').textContent = data.total_residents || '0';
                    document.getElementById('total-voters').textContent = data.total_voters || '0';
                    document.getElementById('total-seniors').textContent = data.total_seniors || '0';
                    document.getElementById('total-certificates').textContent = data.total_certificates || '0';
                })
                .catch(error => {
                    console.error('Error loading quick stats:', error);
                    // Keep the loading indicators if there's an error
                });
        }
    </script>
</body>
</html>
