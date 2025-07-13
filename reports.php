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
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Include Navigation -->
    <?php include 'navigation.php'; ?>
    <!-- Main Content -->
    <div class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full px-4 md:px-8">
            <div class="flex items-center mb-4">
                <h1 class="text-2xl font-bold">Generate Reports</h1>
            </div>
            <!-- Reports Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- Resident Demographics Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-users text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Demographics Report</h3>
                            <p class="text-sm text-gray-600">Population statistics by age, gender, and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('demographics')" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Voter Statistics Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-vote-yea text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Voter Statistics</h3>
                            <p class="text-sm text-gray-600">Registered voters and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('voters')" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- PWD Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-wheelchair text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">PWD Report</h3>
                            <p class="text-sm text-gray-600">Persons with Disabilities and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('pwd')" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Senior Citizens Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-user-clock text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Senior Citizens</h3>
                            <p class="text-sm text-gray-600">Residents 60 years old and above, and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('seniors')" class="w-full bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- 4Ps Recipients Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-red-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-hand-holding-heart text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">4Ps Recipients</h3>
                            <p class="text-sm text-gray-600">Pantawid Pamilyang Pilipino beneficiaries and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('4ps')" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Solo Parents Report -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-pink-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-user-friends text-pink-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Solo Parents</h3>
                            <p class="text-sm text-gray-600">Single parent households and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('solo_parents')" class="w-full bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>

                <!-- Certificate Statistics -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-teal-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-certificate text-teal-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Certificate Statistics</h3>
                            <p class="text-sm text-gray-600">Issued certificates and etc.</p>
                        </div>
                    </div>
                    <button onclick="generateReport('certificates')" class="w-full bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>Generate Report
                    </button>
                </div>



                <!-- Purok Summary -->
                <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-center mb-4">
                        <div class="bg-yellow-100 p-3 rounded-lg mr-4">
                            <i class="fas fa-map-marked-alt text-yellow-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Purok Summary</h3>
                            <p class="text-sm text-gray-600">Population by purok/sitio</p>
                        </div>
                    </div>
                    <button onclick="generateReport('purok')" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors">
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
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
            button.disabled = true;

            // Simulate report generation (replace with actual implementation)
            setTimeout(() => {
                // Create a link to download the report
                const link = document.createElement('a');
                link.href = `generate_report.php?type=${type}&format=pdf`;
                link.download = `${type}_report_${new Date().toISOString().split('T')[0]}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Reset button
                button.innerHTML = originalText;
                button.disabled = false;

                // Show success message
                showNotification('Report generated successfully!', 'success');
            }, 2000);
        }

        function showNotification(message, type = 'info') {
            // Simple notification system
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation' : 'info'} mr-2"></i>
                    ${message}
                </div>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }
    </script>
</body>
</html>
