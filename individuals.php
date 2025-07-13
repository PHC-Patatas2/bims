<?php
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
// Fetch system title from system_settings table
$system_title = 'Resident Information and Certification Management System'; // default fallback
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
    <title>Individuals - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Comprehensive console warning suppression for development environment
        // TODO: For production, replace with proper Tailwind CSS installation
        if (typeof console !== 'undefined') {
            const originalWarn = console.warn;
            const originalError = console.error;
            
            console.warn = function(...args) {
                const message = args.join(' ');
                if (!message.includes('should not be used in production') && 
                    !message.includes('cdn.tailwindcss.com') &&
                    !message.includes('Tailwind CSS')) {
                    originalWarn.apply(console, args);
                }
            };
            
            console.error = function(...args) {
                const message = args.join(' ');
                if (!message.includes('Failed to find a valid digest') && 
                    !message.includes('integrity attribute') &&
                    !message.includes('Unexpected token') &&
                    !message.includes('not valid JSON')) {
                    originalError.apply(console, args);
                }
            };
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Enhanced Tabulator Styling - Optimized for Large Datasets */
        .tabulator {
            border-radius: 12px !important;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
            border: 1px solid #e2e8f0 !important;
        }
        .tabulator-header {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%) !important;
            color: black !important;
            font-weight: 600 !important;
            border: none !important;
        }
        .tabulator-header .tabulator-col {
            border-right: 1px solid rgba(0,0,0,0.1) !important;
            text-align: center !important;
            padding: 10px 6px !important;
            font-size: 0.8rem !important;
        }
        .tabulator-header .tabulator-col:last-child {
            border-right: none !important;
            text-align: center !important;
            justify-content: center !important;
        }
        .tabulator-row {
            border-bottom: 1px solid #f1f5f9 !important;
            transition: background-color 0.15s ease;
        }
        .tabulator-row:hover {
            background-color: #f8fafc !important;
        }
        .tabulator-row:nth-child(even) {
            background-color: #fdfdfd !important;
        }
        .tabulator-cell {
            text-align: center !important;
            vertical-align: middle !important;
            padding: 8px 6px !important;
            border-right: 1px solid #f1f5f9 !important;
            font-size: 0.8rem !important;
            line-height: 1.3;
        }
        .tabulator-cell:last-child {
            border-right: none !important;
        }
        .tabulator-footer {
            background: #f8fafc !important;
            border-top: 1px solid #e2e8f0 !important;
            border-radius: 0 0 12px 12px !important;
        }

        /* Action Button Styling */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
            margin: 0 2px;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .action-btn.view-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }
        .action-btn.edit-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        .action-btn.delete-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        /* Enhanced Form Styling */
        .form-input, .form-select {
            background-color: #ffffff;
            border: 2px solid #e2e8f0;
            color: #1a202c;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            width: 100%;
        }
        .form-input:focus, .form-select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background-color: #fafbfc;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            display: block;
            font-size: 0.875rem;
        }
        .form-checkbox {
            accent-color: #667eea;
            transform: scale(1.1);
        }

        /* Modal Enhancements */
        .modal-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 24px;
            border-radius: 16px 16px 0 0;
            position: relative;
        }
        .modal-close-btn {
            position: absolute;
            top: 16px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .modal-close-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }

        /* Modal Display Logic */
        .modal-overlay.show {
            display: flex !important;
            opacity: 1;
        }
        .modal-overlay {
            display: none;
        }

        /* Button Enhancements */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: #f8fafc;
            color: #374151;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        /* Search and Filter Enhancements */
        .search-container {
            position: relative;
        }
        .search-input {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px 12px 44px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            width: 280px;
        }
        @media (min-width: 769px) {
            .search-container {
                width: auto;
            }
        }
        .search-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }

        /* Stats Cards */
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }

        /* Loading indicator for large datasets */
        .table-loading {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            border-radius: 12px;
        }
        .table-loading.hidden {
            display: none !important;
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .search-input {
                width: 100%;
            }
            .modal-container {
                margin: 16px;
                max-width: calc(100vw - 32px);
            }
            .page-header > div {
                flex-direction: column;
            }
            .page-header > div > div:last-child {
                margin-top: 20px;
                align-self: flex-start;
            }
            /* Action Bar responsive behavior */
            .bg-white.rounded-xl .flex.flex-row {
                flex-direction: column;
                align-items: flex-start;
            }
            .bg-white.rounded-xl .flex.flex-row > div:last-child {
                flex-direction: column;
                align-items: flex-start;
                width: 100%;
                margin-top: 15px;
                gap: 10px;
            }
            .bg-white.rounded-xl .search-container {
                width: 100%;
                margin-bottom: 10px;
            }
            #export-btn {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Add Resident Modal -->
    <div id="addResidentModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-40 hidden opacity-0 transition-opacity duration-300" style="display: none;">
        <div class="modal-container w-full max-w-2xl mx-4 relative scale-95 transition-transform duration-300">
            <div class="modal-header">
                <h2 class="text-xl font-bold">Add New Resident</h2>
                <button id="closeAddResidentModal" class="modal-close-btn" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="addResidentForm" class="space-y-4">
                    <!-- Personal Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-user text-blue-600"></i>
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">First Name <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" required class="form-input" placeholder="e.g., Juan">
                            </div>
                            <div>
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-input" placeholder="(optional)">
                            </div>
                            <div>
                                <label class="form-label">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" required class="form-input" placeholder="e.g., Dela Cruz">
                            </div>
                            <div>
                                <label class="form-label">Suffix</label>
                                <input type="text" name="suffix" class="form-input" placeholder="e.g., III, Jr., Sr., II">
                            </div>
                        </div>
                    </div>

                    <!-- Basic Details Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-600"></i>
                            Basic Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Gender <span class="text-red-500">*</span></label>
                                <select name="gender" required class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Birthdate <span class="text-red-500">*</span></label>
                                <input type="date" name="birthdate" required class="form-input">
                            </div>
                            <div>
                                <label class="form-label">Civil Status <span class="text-red-500">*</span></label>
                                <select name="civil_status" required class="form-select">
                                    <option value="">Select Civil Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Separated">Separated</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Annulled">Annulled</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Blood Type</label>
                                <select name="blood_type" class="form-select">
                                    <option value="">Select Blood Type</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Religion <span class="text-red-500">*</span></label>
                                <select name="religion" id="religionSelect" required class="form-select">
                                    <option value="">Loading...</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Residing Purok <span class="text-red-500">*</span></label>
                                <select name="purok_id" id="purokSelect" required class="form-select">
                                    <option value="">Loading...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-address-book text-green-600"></i>
                            Contact Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-input" placeholder="e.g., juan.delacruz@email.com">
                            </div>
                        </div>
                    </div>

                    <!-- Status Information -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-tags text-blue-600"></i>
                            Status Information
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="is_pwd" value="1" class="form-checkbox">
                                <span class="text-sm font-medium">PWD</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="is_voter" value="1" class="form-checkbox">
                                <span class="text-sm font-medium">Registered Voter</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="is_4ps" value="1" class="form-checkbox">
                                <span class="text-sm font-medium">4Ps Beneficiary</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="is_pregnant" value="1" class="form-checkbox">
                                <span class="text-sm font-medium">Pregnant</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="is_solo_parent" value="1" class="form-checkbox">
                                <span class="text-sm font-medium">Solo Parent</span>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" id="cancelAddResident" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Save Resident
                        </button>
                    </div>
                    <div id="addResidentMsg" class="mt-4 text-center text-sm"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Export Options Modal -->
    <div id="exportModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-40 hidden opacity-0 transition-opacity duration-300" style="display: none;">
        <div class="modal-container w-full max-w-md mx-4 relative scale-95 transition-transform duration-300">
            <div class="bg-white rounded-xl shadow-xl overflow-hidden">
                <div class="modal-header bg-blue-600 text-white p-4 flex items-center justify-between">
                    <h2 class="text-xl font-bold">Export Residents Data</h2>
                    <button id="closeExportModal" class="text-white hover:text-blue-200 focus:outline-none" title="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <p class="text-gray-600 text-sm">Choose your preferred export format:</p>
                        <div class="grid gap-3">
                            <button type="button" id="exportCsv" class="btn-secondary justify-start">
                                <i class="fas fa-file-csv text-green-600"></i>
                                Export as CSV
                                <span class="text-xs text-gray-500 ml-auto">Excel compatible</span>
                            </button>
                            <button type="button" id="exportExcel" class="btn-secondary justify-start">
                                <i class="fas fa-file-excel text-green-600"></i>
                                Export as Excel
                                <span class="text-xs text-gray-500 ml-auto">.xlsx format</span>
                            </button>
                            <button type="button" id="exportPdf" class="btn-secondary justify-start">
                                <i class="fas fa-file-pdf text-red-600"></i>
                                Export as PDF
                                <span class="text-xs text-gray-500 ml-auto">Print ready</span>
                            </button>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                            <button type="button" id="cancelExport" class="btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Resident Modal -->
    <div id="editResidentModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-40 hidden opacity-0 transition-opacity duration-300" style="display: none;">
        <div class="modal-overlay absolute inset-0" onclick="closeEditResidentModal()"></div>
        <div class="modal-container w-full max-w-2xl mx-4 relative scale-95 transition-transform duration-300">
            <div class="bg-white rounded-xl shadow-xl overflow-hidden">
                <div class="modal-header bg-blue-600 text-white p-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold">Edit Resident</h3>
                    <button id="closeEditResidentModalBtn" class="text-white hover:text-blue-200 focus:outline-none" onclick="closeEditResidentModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form id="editResidentForm" class="space-y-4">
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Resident Modal -->
    <div id="viewResidentModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-40 hidden opacity-0 transition-opacity duration-300" style="display: none;">
        <div class="modal-overlay absolute inset-0" onclick="closeViewResidentModal()"></div>
        <div class="modal-container w-full max-w-2xl mx-4 relative scale-95 transition-transform duration-300">
            <div class="bg-white rounded-xl shadow-xl overflow-hidden">
                <div class="modal-header bg-blue-600 text-white p-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold">Resident Details</h3>
                    <button id="closeViewResidentModal" class="text-white hover:text-blue-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="viewResidentContent" class="p-6">
                    <div class="text-center py-10">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-3 text-gray-600">Loading resident details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Resident Confirmation Modal -->
    <div id="deleteResidentModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-40 hidden opacity-0 transition-opacity duration-300" style="display: none;">
        <div class="modal-overlay absolute inset-0" onclick="closeDeleteResidentModal()"></div>
        <div class="modal-container w-full max-w-md mx-4 relative scale-95 transition-transform duration-300">
            <div class="bg-white rounded-xl shadow-xl overflow-hidden">
                <div class="modal-header bg-red-600 text-white p-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold">Confirm Deletion</h3>
                    <button id="closeDeleteResidentModal" class="text-white hover:text-red-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="deleteResidentContent" class="p-6">
                    <div class="text-center">
                        <div class="text-red-500 text-5xl mb-4">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <p class="text-gray-700 font-medium mb-2">Are you sure you want to delete this resident?</p>
                        <p class="text-gray-500 text-sm mb-6">This action cannot be undone.</p>
                        
                        <div id="deleteResidentMsg" class="mb-4"></div>
                        
                        <div class="flex justify-center gap-3">
                            <button id="cancelDeleteResident" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                Cancel
                            </button>
                            <button id="confirmDeleteResident" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                                <i class="fas fa-trash mr-1"></i>
                                <span id="deleteButtonText">Delete (4)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- More Options Modal -->
    <div id="moreOptionsModal" class="fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-40 hidden opacity-0 transition-opacity duration-300" style="display: none;">
        <div class="modal-overlay absolute inset-0" onclick="closeMoreOptionsModal()"></div>
        <div class="modal-container w-full max-w-md mx-4 relative scale-95 transition-transform duration-300">
            <div class="bg-white rounded-xl shadow-xl overflow-hidden">
                <div class="modal-header bg-blue-600 text-white p-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold">Generate Certificate</h3>
                    <button id="closeMoreOptionsModal" class="text-white hover:text-gray-200 focus:outline-none" onclick="closeMoreOptionsModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <button onclick="generateCertificate('clearance')" class="w-full flex items-center gap-3 px-4 py-3 text-left bg-gray-50 hover:bg-blue-50 rounded-lg transition-colors">
                            <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-certificate"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Barangay Clearance</div>
                                <div class="text-sm text-gray-500">Generate general clearance certificate</div>
                            </div>
                        </button>
                        
                        <button onclick="generateCertificate('residency')" class="w-full flex items-center gap-3 px-4 py-3 text-left bg-gray-50 hover:bg-green-50 rounded-lg transition-colors">
                            <div class="w-10 h-10 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-home"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Certificate of Residency</div>
                                <div class="text-sm text-gray-500">Proof of residence in the barangay</div>
                            </div>
                        </button>
                        
                        <button onclick="generateCertificate('indigency')" class="w-full flex items-center gap-3 px-4 py-3 text-left bg-gray-50 hover:bg-yellow-50 rounded-lg transition-colors">
                            <div class="w-10 h-10 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-hand-holding-heart"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Certificate of Indigency</div>
                                <div class="text-sm text-gray-500">Financial status certification</div>
                            </div>
                        </button>
                        
                        <button onclick="generateCertificate('first_time_job_seeker')" class="w-full flex items-center gap-3 px-4 py-3 text-left bg-gray-50 hover:bg-purple-50 rounded-lg transition-colors">
                            <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">First Time Job Seeker</div>
                                <div class="text-sm text-gray-500">Certificate for first-time job seekers</div>
                            </div>
                        </button>
                        
                        <button onclick="generateCertificate('barangay_id')" class="w-full flex items-center gap-3 px-4 py-3 text-left bg-gray-50 hover:bg-indigo-50 rounded-lg transition-colors">
                            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Barangay ID</div>
                                <div class="text-sm text-gray-500">Official barangay identification</div>
                            </div>
                        </button>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 mt-6">
                        <button onclick="closeMoreOptionsModal()" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
    .edit-modal-style input[readonly], .edit-modal-style select[disabled], .edit-modal-style textarea[readonly] {
        background-color: #f3f4f6 !important;
        color: #22223b !important;
        border-color: #cbd5e1 !important;
        cursor: default !important;
        pointer-events: none;
    }
    .edit-modal-style label {
        color: #1e293b;
    }
    
    /* View resident modal styles */
    .resident-info-group {
        margin-bottom: 1.25rem;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 1rem;
    }
    .resident-info-group:last-child {
        border-bottom: 0;
        padding-bottom: 0;
        margin-bottom: 0;
    }
    .resident-info-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.75rem;
    }
    .resident-info-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 0.75rem;
    }
    @media (min-width: 640px) {
        .resident-info-row {
            grid-template-columns: 1fr 1fr;
        }
    }
    .resident-info-item {
        margin-bottom: 0.75rem;
    }
    .resident-info-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 500;
        color: #6b7280;
    }
    .resident-info-value {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #1f2937;
    }
    .resident-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
    }
    </style>
    <!-- Include Navigation -->
    <?php include 'navigation.php'; ?>
    <div style="height:64px;"></div>
    <!-- Main content -->
    <div class="flex-1 p-4 md:p-8 bg-gray-50">
        <!-- Page Header -->
        <div class="page-header mb-6">
            <div class="flex justify-start items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                        Residents Management
                    </h1>
                    <p class="text-gray-600 mt-2">Manage and view all registered residents in the barangay</p>
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-6">
                <div class="flex flex-row items-center justify-between flex-wrap gap-4">
                    <div>
                        <button id="add-resident-btn" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            Add New Resident
                        </button>
                    </div>
                    <div class="flex flex-row items-center gap-3">
                        <div class="search-container">
                            <input type="text" id="resident-search" placeholder="Search residents..." class="search-input">
                            <i class="fas fa-search search-icon"></i>
                        </div>
                        <button id="export-btn" class="btn-secondary">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Residents Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 relative">
            <div class="p-2">
                <div id="table-loading" class="table-loading hidden">
                    <div class="text-center">
                        <div class="loading-spinner mx-auto mb-3"></div>
                        <div class="text-gray-600 font-medium">Loading residents...</div>
                    </div>
                </div>
                <div id="residents-table" class="w-full"></div>
            </div>
        </div>
    </div>
        <script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css" />
        
        <!-- Libraries for Tabulator export functionality -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("DOM Content Loaded - Starting table initialization");
            
            // Reset loading indicator state
            const loadingElement = document.getElementById('table-loading');
            if (loadingElement) {
                console.log("Loading indicator found - ensuring it shows while data loads");
                loadingElement.classList.remove('hidden');
            } else {
                console.error("Loading indicator element NOT found!");
            }

            // Optimized table configuration for large datasets
            var rowHeight = 42; // Optimized for performance and readability
            var pageSize = 10; // Default 10 records as requested
            var headerHeight = 40;
            var footerHeight = 40;
            var tableHeight = (rowHeight * pageSize) + headerHeight + footerHeight;

            // URL parameter handling
            function getUrlParam(name) {
                const url = new URL(window.location.href);
                return url.searchParams.get(name);
            }

            function getTabulatorFilter(filterType) {
                if (!filterType) return null;
                switch (filterType) {
                    case 'male':
                        return {field: 'gender', type: 'like', value: 'male'};
                    case 'female':
                        return {field: 'gender', type: 'like', value: 'female'};
                    case 'voter':
                        return {field: 'is_voter', type: '=', value: 1};
                    case '4ps':
                        return {field: 'is_4ps', type: '=', value: 1};
                    case 'senior':
                        return null;
                    case 'pwd':
                        return {field: 'is_pwd', type: '=', value: 1};
                    case 'solo_parent':
                        return {field: 'is_solo_parent', type: '=', value: 1};
                    case 'minor':
                        return null;
                    case 'children_and_youth':
                        return null;
                    default:
                        return null;
                }
            }

            var filterType = getUrlParam('filter_type');
            var tabFilter = getTabulatorFilter(filterType);

            console.log("Creating Tabulator table...");
            
            // Fallback timeout to hide loading indicator after 10 seconds
            setTimeout(function() {
                const loadingElement = document.getElementById('table-loading');
                if (loadingElement && !loadingElement.classList.contains('hidden')) {
                    console.log("Fallback timeout - hiding loading indicator");
                    loadingElement.classList.add('hidden');
                    loadingElement.style.display = 'none';
                }
            }, 10000);
            
            window.table = new Tabulator("#residents-table", {
                ajaxURL: "fetch_individuals.php",
                ajaxConfig: "GET",
                ajaxParams: filterType ? { filter_type: filterType } : {},
                layout: "fitDataFill",
                height: tableHeight,
                responsiveLayout: "hide",
                pagination: "local",
                paginationSize: pageSize,
                paginationSizeSelector: [10, 20, 50, 100],
                rowHeight: rowHeight,
                virtualDom: true, // Enable virtual DOM for better performance with large datasets
                virtualDomBuffer: 500, // Increased buffer for smoother scrolling with large datasets
                virtualDomHoz: true, // Enable horizontal virtual DOM for wide tables
                columnDefaults: {
                    resizable: true,
                    headerSort: true,
                },
                columns: [
                    {
                        title: "First Name", 
                        field: "first_name", 
                        headerSort: true, 
                        hozAlign: "center", 
                        vertAlign: "middle",
                        width: 180,
                        minWidth: 120,
                        responsive: 1,
                        formatter: function(cell, formatterParams, onRendered) {
                            var value = cell.getValue() || '';
                            return `<div class="font-medium text-black truncate">${value}</div>`;
                        }
                    },
                    {
                        title: "Middle Name", 
                        field: "middle_name", 
                        headerSort: true, 
                        hozAlign: "center", 
                        vertAlign: "middle",
                        width: 130,
                        minWidth: 100,
                        responsive: 5,
                        formatter: function(cell, formatterParams, onRendered) {
                            var value = cell.getValue() || '';
                            if (!value) return '<span class="text-gray-400 text-xs">—</span>';
                            return `<div class="text-gray-700 truncate">${value}</div>`;
                        }
                    },
                    {
                        title: "Last Name", 
                        field: "last_name", 
                        headerSort: true, 
                        hozAlign: "center", 
                        vertAlign: "middle",
                        width: 140,
                        minWidth: 120,
                        responsive: 2,
                        formatter: function(cell, formatterParams, onRendered) {
                            var value = cell.getValue() || '';
                            return `<div class="font-medium text-black truncate">${value}</div>`;
                        }
                    },
                    {
                        title: "Suffix", 
                        field: "suffix", 
                        headerSort: true, 
                        hozAlign: "center", 
                        vertAlign: "middle",
                        width: 80,
                        minWidth: 60,
                        responsive: 6,
                        download: true, // Ensure it's included in exports
                        formatter: function(cell, formatterParams, onRendered) {
                            var value = cell.getValue() || '';
                            if (!value) return '<span class="text-gray-400 text-xs">—</span>';
                            return `<div class="text-gray-700 text-sm">${value}</div>`;
                        }
                    },
                    {
                        title: "Gender", 
                        field: "gender", 
                        headerSort: true, 
                        hozAlign: "center", 
                        vertAlign: "middle", 
                        width: 100,
                        minWidth: 80,
                        responsive: 4,
                        formatter: function(cell, formatterParams, onRendered) {
                            var value = cell.getValue();
                            var text = value ? value.charAt(0).toUpperCase() + value.slice(1) : '';
                            return `<div class="text-sm text-black">${text}</div>`;
                        }
                    },
                    {
                        title: "Birthdate", 
                        field: "birthdate", 
                        headerSort: true, 
                        hozAlign: "center", 
                        vertAlign: "middle", 
                        width: 120,
                        minWidth: 100,
                        responsive: 3,
                        formatter: function(cell, formatterParams, onRendered) {
                            var birthdate = cell.getValue();
                            if (!birthdate) return '';
                            var date = new Date(birthdate);
                            var formatted = date.toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric' 
                            });
                            var today = new Date();
                            var age = today.getFullYear() - date.getFullYear();
                            // Check if birthday has not occurred yet this year
                            var hasBirthdayOccurred = (today.getMonth() > date.getMonth()) || (today.getMonth() === date.getMonth() && today.getDate() >= date.getDate());
                            if (!hasBirthdayOccurred) {
                                age = age - 1;
                            }
                            return `<div class="text-center"><div class="text-xs text-gray-600">${formatted}</div><div class="text-xs text-blue-600 font-medium">(${age} yrs)</div></div>`;
                        }
                    },
                    {
                        title: "Civil Status", 
                        field: "civil_status", 
                        headerSort: true, 
                        hozAlign: "center", 
                        vertAlign: "middle", 
                        width: 110,
                        formatter: function(cell, formatterParams, onRendered) {
                            var value = cell.getValue() || '';
                            return `<div class="text-sm text-gray-700">${value}</div>`;
                        }
                    },
                    {
                        title: "Purok", 
                        field: "purok", 
                        headerSort: true, 
                        hozAlign: "center", 
                        vertAlign: "middle", 
                        width: 90,
                        formatter: function(cell) {
                            var value = cell.getValue() || '';
                            value = value.replace(/\s*\([^)]*\)/g, '').trim();
                            return `<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">${value}</span>`;
                        }
                    },

                    {
                        title: "Status", 
                        field: "status", 
                        headerSort: false, 
                        hozAlign: "center", 
                        vertAlign: "middle", 
                        width: 250,
                        download: false, // Exclude from exports
                        formatter: function(cell, formatterParams, onRendered) {
                            var row = cell.getRow().getData();
                            var badges = [];
                            
                            if (row.is_voter == 1) badges.push('<span class="inline-block px-1 py-0.5 bg-green-100 text-green-700 rounded text-xs font-medium mb-0.5">Voter</span>');
                            if (row.is_pwd == 1) badges.push('<span class="inline-block px-1 py-0.5 bg-purple-100 text-purple-700 rounded text-xs font-medium mb-0.5">PWD</span>');
                            if (row.is_4ps == 1) badges.push('<span class="inline-block px-1 py-0.5 bg-orange-100 text-orange-700 rounded text-xs font-medium mb-0.5">4Ps</span>');
                            if (row.is_solo_parent == 1) badges.push('<span class="inline-block px-1 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium mb-0.5">Solo</span>');
                            if (row.is_pregnant == 1) badges.push('<span class="inline-block px-1 py-0.5 bg-pink-100 text-pink-700 rounded text-xs font-medium mb-0.5">Pregnant</span>');
                            
                            if (badges.length === 0) {
                                return '<span class="text-gray-400 text-xs">—</span>';
                            }
                            
                            return `<div class="flex flex-wrap gap-1 justify-center max-w-full">${badges.join('')}</div>`;
                        }
                    },
                    {
                        title: "Actions", 
                        field: "action", 
                        headerSort: false, 
                        hozAlign: "center", 
                        vertAlign: "middle",
                        widthGrow: 1,
                        minWidth: 130,
                        resizable: false,
                        responsive: 0,
                        download: false, // Exclude from exports
                        formatter: function(cell, formatterParams, onRendered) {
                            var row = cell.getRow().getData();
                            return `
                                <div class="flex items-center justify-center gap-1" style="width: 100%; padding: 0 8px;">
                                    <a href="#" title="View Details" class="action-btn view-btn view-resident-btn" style="width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; background: #3b82f6; color: white; border-radius: 4px; text-decoration: none;" 
                                        data-id='${row.id || row.ID || 0}'>
                                        <i class="fas fa-eye" style="font-size: 11px;"></i>
                                    </a>
                                    <a href="#" title="Edit Resident" class="action-btn edit-btn edit-resident-btn" style="width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; background: #10b981; color: white; border-radius: 4px; text-decoration: none;"
                                        data-id='${row.id || row.ID || 0}'>
                                        <i class="fas fa-edit" style="font-size: 11px;"></i>
                                    </a>
                                    <a href="#" title="Delete Resident" class="action-btn delete-btn delete-resident-btn" style="width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; background: #ef4444; color: white; border-radius: 4px; text-decoration: none;"
                                        data-id='${row.id || row.ID || 0}'>
                                        <i class="fas fa-trash" style="font-size: 11px;"></i>
                                    </a>
                                    <div class="relative">
                                        <button title="More Options" class="action-btn more-btn" style="width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #6b7280, #4b5563); color: white; border-radius: 4px; border: none; cursor: pointer;" onclick="showMoreOptionsModal('${row.id || row.ID || 0}')">
                                            <i class="fas fa-ellipsis-v" style="font-size: 11px;"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        }
                    }
                ],
                placeholder: function(){
                    return `
                        <div class="flex flex-col items-center justify-center h-96 w-full text-center text-gray-500 p-8">
                            <div class="bg-gray-100 rounded-full p-6 mb-4">
                                <i class='fas fa-users text-4xl text-gray-400'></i>
                            </div>
                            <div class="text-xl font-semibold mb-2 text-gray-700">No Residents Found</div>
                            <div class="text-gray-500 mb-4">Start building your community database</div>
                            <button id="add-resident-link-empty" class="btn-primary">
                                <i class="fas fa-plus"></i>
                                Add First Resident
                            </button>
                        </div>
                    `;
                },
                dataLoaded: function(data) {
                    // Don't update the counter here as we want to show total database count
                    // regardless of filtering
                    console.log("Data loaded: " + data.length + " records in the current view");
                },
                renderStarted: function() {
                    // Show loading indicator for large datasets
                    document.getElementById('table-loading').classList.remove('hidden');
                    console.log("Table rendering started...");
                },
                renderComplete: function() {
                    // Hide loading indicator
                    const loadingElement = document.getElementById('table-loading');
                    if (loadingElement) {
                        console.log("Table rendering completed - hiding loading indicator");
                        loadingElement.classList.add('hidden');
                        // Force hide with inline style as a backup
                        loadingElement.style.display = 'none';
                    } else {
                        console.error("Loading element not found in renderComplete!");
                    }
                    
                    console.log("Table rendering completed.");
                },
                ajaxLoading: function(url) {
                    // Show loading when fetching data
                    document.getElementById('table-loading').classList.remove('hidden');
                },
                ajaxResponse: function(url, params, response) {
                    // Hide loading when data arrives
                    const loadingElement = document.getElementById('table-loading');
                    if (loadingElement) {
                        console.log("Hiding loading indicator...");
                        loadingElement.classList.add('hidden');
                    } else {
                        console.error("Loading element not found!");
                    }
                    
                    return response;
                },
                ajaxError: function(xhr, textStatus, errorThrown) {
                    // Hide loading on error
                    document.getElementById('table-loading').classList.add('hidden');
                    console.error("Table loading error:", textStatus, errorThrown);
                    console.error("Response:", xhr.responseText);
                    
                    // Show error message to user
                    const tableElement = document.getElementById('residents-table');
                    if (tableElement) {
                        tableElement.innerHTML = `
                            <div class="flex flex-col items-center justify-center h-64 text-center text-red-500 p-8">
                                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                <div class="text-xl font-semibold mb-2">Error Loading Residents</div>
                                <div class="text-sm text-gray-600 mb-4">Please check your connection and try again</div>
                                <button onclick="location.reload()" class="btn-primary">
                                    <i class="fas fa-refresh"></i>
                                    Reload Page
                                </button>
                            </div>
                        `;
                    }
                },
                rowClick: function(e, row) {
                    // Don't do anything on row click to prevent interference with action buttons
                },
                cellClick: function(e, cell) {
                    // Handle clicks only on action column
                    if (cell.getField() === "action") {
                        const target = e.target;
                        const button = target.closest('a');
                        const rowData = cell.getRow().getData();
                        const residentId = rowData.id || rowData.ID;
                        
                        if (!button) return;
                        
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // View button
                        if (button.classList.contains('view-resident-btn')) {
                            viewResidentDetails(residentId);
                        }
                        
                        // Edit button
                        else if (button.classList.contains('edit-resident-btn')) {
                            editResident(residentId);
                        }
                        
                        // Delete button
                        else if (button.classList.contains('delete-resident-btn')) {
                            showDeleteConfirmation(residentId);
                        }
                    }
                }
            });

            // Set filter if provided
            if (tabFilter) {
                window.table.setFilter([tabFilter]);
            }

            // Enhanced search functionality for all fields
            var searchInput = document.getElementById('resident-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    var val = this.value.trim();
                    if (val === '') {
                        window.table.clearFilter();
                        if (tabFilter) window.table.setFilter([tabFilter]);
                    } else {
                        var filters = [
                            {field: "first_name", type: "like", value: val},
                            {field: "middle_name", type: "like", value: val},
                            {field: "last_name", type: "like", value: val},
                            {field: "suffix", type: "like", value: val},
                            {field: "gender", type: "like", value: val},
                            {field: "civil_status", type: "like", value: val},
                            {field: "purok", type: "like", value: val}
                        ];
                        
                        // Use OR filter for search across multiple fields
                        if (tabFilter) {
                            window.table.setFilter([[filters], tabFilter]);
                        } else {
                            window.table.setFilter([filters]);
                        }
                    }
                    
                    // No need to update counter with filtered results - keep showing total count
                });
            }
        });
        </script>
    </div>
    
    <script>
        // More Options Modal functionality - moved below to avoid duplication
        
        // More Options Modal event handlers
        document.addEventListener('DOMContentLoaded', function() {
            // No need for individual button handlers since we're using onclick attributes
        });
        
        // Global variable to track if certificate generation is in progress
        let isCertificateGenerating = false;
        let originalModalContent = null;
        let currentResidentId = null;

        // Store original modal content when modal is first opened
        function showMoreOptionsModal(residentId) {
            if (isCertificateGenerating) {
                return; // Prevent opening modal while generating
            }
            
            currentResidentId = residentId;
            const modal = document.getElementById('moreOptionsModal');
            
            // Store original content if not already stored
            if (!originalModalContent) {
                originalModalContent = modal.querySelector('.p-6').innerHTML;
            }
            
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.classList.add('opacity-100');
                modal.querySelector('.modal-container').classList.remove('scale-95');
                modal.querySelector('.modal-container').classList.add('scale-100');
            }, 10);
        }

        function closeMoreOptionsModal() {
            const modal = document.getElementById('moreOptionsModal');
            modal.classList.add('opacity-0');
            modal.classList.remove('opacity-100');
            modal.querySelector('.modal-container').classList.add('scale-95');
            modal.querySelector('.modal-container').classList.remove('scale-100');
            
            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.add('hidden');
                // Reset the modal content to original state when closing
                if (originalModalContent) {
                    modal.querySelector('.p-6').innerHTML = originalModalContent;
                }
                // Reset state variables
                currentResidentId = null;
                isCertificateGenerating = false;
            }, 300);
        }

        // Enhanced certificate generation function
        function generateCertificate(certificateType) {
            if (!currentResidentId) {
                showNotification('No resident selected', 'error');
                return;
            }
            
            if (isCertificateGenerating) {
                showNotification('Certificate generation in progress. Please wait...', 'info');
                return;
            }
            
            console.log(`Generating ${certificateType} certificate for resident:`, currentResidentId);
            
            // Set generation flag
            isCertificateGenerating = true;
            
            // Show loading state
            const modal = document.getElementById('moreOptionsModal');
            modal.querySelector('.p-6').innerHTML = `
                <div class="text-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Generating ${certificateType.replace(/_/g, ' ')} certificate...</p>
                    <p class="mt-2 text-sm text-gray-500">Please wait while we prepare your document</p>
                </div>
            `;
            
            // Create form data
            const formData = new FormData();
            formData.append('certificate_type', certificateType);
            formData.append('resident_id', currentResidentId);
            formData.append('purpose', 'Direct generation from residents list');
            
            // Generate certificate
            fetch('generate_certificate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success state first
                    modal.querySelector('.p-6').innerHTML = `
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-check text-green-600 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Certificate Generated Successfully!</h3>
                            <p class="text-gray-600 mb-4">Your ${certificateType.replace(/_/g, ' ')} certificate is ready for download.</p>
                            <button onclick="downloadAndClose('${data.download_url}', '${data.certificate_id}')" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                                <i class="fas fa-download mr-2"></i>Download Certificate
                            </button>
                        </div>
                    `;
                    
                    // Auto-download the certificate
                    const link = document.createElement('a');
                    link.href = data.download_url;
                    link.download = data.certificate_id + '.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Show success notification
                    showNotification('Certificate generated and downloaded successfully!', 'success');
                    
                    // Reset after a delay to allow user to see the success state
                    setTimeout(() => {
                        resetModalContent();
                        closeMoreOptionsModal();
                    }, 2000);
                    
                } else {
                    showNotification('Error generating certificate: ' + (data.error || 'Unknown error'), 'error');
                    resetModalContent();
                }
                
                // Reset generation flag
                isCertificateGenerating = false;
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while generating the certificate.', 'error');
                resetModalContent();
                isCertificateGenerating = false;
            });
        }

        // Function to download and close modal
        function downloadAndClose(downloadUrl, certificateId) {
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = certificateId + '.pdf';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            resetModalContent();
            closeMoreOptionsModal();
        }

        // Function to reset modal content to original state
        function resetModalContent() {
            if (originalModalContent) {
                const modal = document.getElementById('moreOptionsModal');
                modal.querySelector('.p-6').innerHTML = originalModalContent;
            }
        }

        function handleExportData(residentId) {
            console.log('Export data for resident:', residentId);
            // TODO: Implement individual resident data export
            showNotification('Export data functionality will be implemented here.', 'info');
        }

        function handleDuplicate(residentId) {
            console.log('Duplicate resident:', residentId);
            // TODO: Implement resident duplication functionality
            showNotification('Duplicate resident functionality will be implemented here.', 'info');
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 p-4 rounded-xl shadow-xl z-50 transform translate-x-full transition-all duration-300 ${
                type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600 text-white' : 
                type === 'error' ? 'bg-gradient-to-r from-red-500 to-red-600 text-white' : 
                'bg-gradient-to-r from-blue-500 to-blue-600 text-white'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-3">
                        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
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
            
            // Trigger animation
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
            
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

        // View Resident Details Modal (Preview Only)
        function viewResidentDetails(residentId) {
            // Fetch resident details via AJAX
            const modal = document.getElementById('viewResidentModal');
            const content = document.getElementById('viewResidentContent');
            // Show loading
            content.innerHTML = `<div class="text-center py-10"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div><p class="mt-3 text-gray-600">Loading resident details...</p></div>`;
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.querySelector('.modal-container').style.transform = 'scale(1)';
            }, 10);

            fetch('fetch_individual_detail.php?id=' + encodeURIComponent(residentId))
                .then(response => response.json())
                .then(data => {
                    if (!data || data.error) {
                        content.innerHTML = `<div class='text-center text-red-500'>Unable to load resident details.</div>`;
                        return;
                    }
                    // Build preview form
                    let fields = [
                        { label: 'First Name', name: 'first_name' },
                        { label: 'Middle Name', name: 'middle_name' },
                        { label: 'Last Name', name: 'last_name' },
                        { label: 'Suffix', name: 'suffix' },
                        { label: 'Gender', name: 'gender' },
                        { label: 'Birthdate', name: 'birthdate' },
                        { label: 'Civil Status', name: 'civil_status' },
                        { label: 'Purok', name: 'purok' },
                        { label: 'Religion', name: 'religion' },
                        { label: 'Contact', name: 'contact' },
                        { label: 'Address', name: 'address' },
                        { label: 'Voter', name: 'is_voter', type: 'checkbox' },
                        { label: 'PWD', name: 'is_pwd', type: 'checkbox' },
                        { label: '4Ps', name: 'is_4ps', type: 'checkbox' },
                        { label: 'Solo Parent', name: 'is_solo_parent', type: 'checkbox' },
                        { label: 'Pregnant', name: 'is_pregnant', type: 'checkbox' }
                    ];
                    let html = `<form class='grid grid-cols-1 md:grid-cols-2 gap-4 edit-modal-style'>`;
                    fields.forEach(f => {
                        let val = data[f.name] !== undefined && data[f.name] !== null ? data[f.name] : '';
                        if (f.type === 'checkbox') {
                            html += `<div><label class='resident-info-label mb-1'>${f.label}</label><input type='checkbox' disabled ${val == 1 ? 'checked' : ''} class='form-checkbox' /></div>`;
                        } else {
                            html += `<div><label class='resident-info-label mb-1'>${f.label}</label><input type='text' class='form-input w-full' value='${val}' readonly /></div>`;
                        }
                    });
                    html += `</form>`;
                    content.innerHTML = html;
                })
                .catch(() => {
                    content.innerHTML = `<div class='text-center text-red-500'>Unable to load resident details.</div>`;
                });
        }

        // Modal initialization and event handlers
        document.addEventListener('DOMContentLoaded', function() {
            // Load dropdown options
            fetchPuroks();
            fetchReligions();
            
            // Initialize event handlers after table is loaded
            setTimeout(function() {
                initializeModalHandlers();
            }, 1000); // Small delay to ensure table is fully loaded
            
            // Add Resident button
            document.getElementById('add-resident-btn').addEventListener('click', function() {
                // Clear any previous messages and reset form when opening modal
                clearAddResidentModal();
                
                // Show modal with proper display and transition
                const modal = document.getElementById('addResidentModal');
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.remove('hidden');
                    modal.classList.remove('opacity-0');
                    if (modal.querySelector('.modal-container')) {
                        modal.querySelector('.modal-container').classList.remove('scale-95');
                    }
                }, 10);
            });
            
            // Handle "Add First Resident" button using event delegation
            document.addEventListener('click', function(e) {
                if (e.target && e.target.id === 'add-resident-link-empty') {
                    e.preventDefault();
                    
                    // Clear any previous messages and reset form when opening modal
                    clearAddResidentModal();
                    
                    const modal = document.getElementById('addResidentModal');
                    modal.style.display = 'flex';
                    setTimeout(() => {
                        modal.classList.remove('hidden');
                        modal.classList.remove('opacity-0');
                        if (modal.querySelector('.modal-container')) {
                            modal.querySelector('.modal-container').classList.remove('scale-95');
                        }
                    }, 10);
                }
            });
            
            // Add Resident form submission
            document.getElementById('addResidentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'add_individual.php', true);
                xhr.onload = function() {
                    const msgElement = document.getElementById('addResidentMsg');
                    
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            
                            if (response.success) {
                                msgElement.innerHTML = `<div class="text-green-600">${response.message || 'Resident added successfully!'}</div>`;
                                
                                // Reset form
                                document.getElementById('addResidentForm').reset();
                                
                                // Refresh the table after a short delay
                                setTimeout(() => {
                                    window.table.setData("fetch_individuals.php");
                                    
                                    // Close modal after data refresh
                                    setTimeout(() => {
                                        closeAddResidentModal();
                                    }, 500);
                                }, 1000);
                            } else {
                                msgElement.innerHTML = `<div class="text-red-600">${response.error || response.message || 'An error occurred'}</div>`;
                            }
                        } catch (e) {
                            msgElement.innerHTML = `<div class="text-red-600">Error processing response</div>`;
                            console.error("Error parsing response:", this.responseText);
                        }
                    } else {
                        msgElement.innerHTML = `<div class="text-red-600">Server error (${this.status})</div>`;
                    }
                };
                xhr.send(formData);
            });
            
            // Clear error message when user starts interacting with form fields
            document.getElementById('addResidentForm').addEventListener('input', function() {
                const msgElement = document.getElementById('addResidentMsg');
                if (msgElement && msgElement.innerHTML.includes('text-red-600')) {
                    msgElement.innerHTML = '';
                }
            });
            
            // Close Add Resident modal
            document.getElementById('closeAddResidentModal').addEventListener('click', closeAddResidentModal);
            document.getElementById('cancelAddResident').addEventListener('click', closeAddResidentModal);
            
            function clearAddResidentModal() {
                // Clear any previous messages
                const msgElement = document.getElementById('addResidentMsg');
                if (msgElement) {
                    msgElement.innerHTML = '';
                }
                
                // Reset the form
                const form = document.getElementById('addResidentForm');
                if (form) {
                    form.reset();
                }
                
                // Reset dropdowns to loading state and reload them
                fetchPuroks();
                fetchReligions();
            }
            
            function closeAddResidentModal() {
                const modal = document.getElementById('addResidentModal');
                modal.classList.add('hidden');
                modal.classList.add('opacity-0');
                if (modal.querySelector('.modal-container')) {
                    modal.querySelector('.modal-container').classList.add('scale-95');
                }
                setTimeout(() => {
                    modal.style.display = 'none';
                    // Clear modal content when closing
                    clearAddResidentModal();
                }, 300);
            }
            
            // Close Edit Resident modal (only if elements exist)
            const closeEditBtn = document.getElementById('closeEditResidentModal');
            if (closeEditBtn) {
                closeEditBtn.addEventListener('click', closeEditResidentModal);
            }
            const cancelEditBtn = document.getElementById('cancelEditResident');
            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', closeEditResidentModal);
            }
            
            // Edit Resident form submission
            const editForm = document.getElementById('editResidentForm');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'edit_individual.php', true);
                    xhr.onload = function() {
                        const msgElement = document.getElementById('editResidentMsg');
                        
                        if (this.status === 200) {
                            try {
                                const response = JSON.parse(this.responseText);
                                
                                if (response.success) {
                                    msgElement.innerHTML = `<div class="text-green-600">${response.message}</div>`;
                                    
                                    // Refresh the table after a short delay
                                    setTimeout(() => {
                                        window.table.setData("fetch_individuals.php");
                                        
                                        // Close modal after data refresh
                                        setTimeout(() => {
                                            closeEditResidentModal();
                                        }, 500);
                                    }, 1000);
                                } else {
                                    msgElement.innerHTML = `<div class="text-red-600">${response.message}</div>`;
                                }
                            } catch (e) {
                                msgElement.innerHTML = `<div class="text-red-600">Error processing response</div>`;
                                console.error("Error parsing response:", this.responseText);
                            }
                        } else {
                            msgElement.innerHTML = `<div class="text-red-600">Server error (${this.status})</div>`;
                        }
                    };
                    xhr.send(formData);
                });
            }
            
            // Export modal
            document.getElementById('export-btn').addEventListener('click', function() {
                const modal = document.getElementById('exportModal');
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.remove('opacity-0');
                    modal.classList.add('opacity-100');
                    modal.querySelector('.modal-container').classList.remove('scale-95');
                    modal.querySelector('.modal-container').classList.add('scale-100');
                }, 10);
            });
            
            document.getElementById('closeExportModal').addEventListener('click', function() {
                closeExportModal();
            });
            
            document.getElementById('cancelExport').addEventListener('click', function() {
                closeExportModal();
            });
            
            // Export buttons
            document.getElementById('exportCsv').addEventListener('click', function() {
                exportData('csv');
            });
            
            document.getElementById('exportExcel').addEventListener('click', function() {
                exportData('xlsx');
            });
            
            document.getElementById('exportPdf').addEventListener('click', function() {
                exportData('pdf');
            });
            
            function exportData(format) {
                const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                const filename = `residents_export_${timestamp}`;
                
                // Configure export options based on format
                const options = {
                    downloadReady: function(fileContents, blob) {
                        closeExportModal();
                        return blob;
                    }
                };
                
                switch(format) {
                    case 'csv':
                        options.delimiter = ',';
                        // Explicitly define columns for CSV export
                        options.columns = ["first_name", "middle_name", "last_name", "suffix", "gender", "birthdate", "civil_status", "purok"];
                        options.columnHeaders = {
                            "first_name": "First Name",
                            "middle_name": "Middle Name",
                            "last_name": "Last Name",
                            "suffix": "Suffix", 
                            "gender": "Gender",
                            "birthdate": "Birthdate",
                            "civil_status": "Civil Status",
                            "purok": "Purok"
                        };
                        // Clean data for CSV export
                        options.mutateData = function(data) {
                            return data.map(row => {
                                if (row.purok) row.purok = row.purok.replace(/\s*\([^)]*\)/g, '').trim();
                                return row;
                            });
                        };
                        window.table.download("csv", `${filename}.csv`, options);
                        break;
                        
                    case 'xlsx':
                        options.sheetName = "Residents Data";
                        // Explicitly define columns for Excel export
                        options.columns = ["first_name", "middle_name", "last_name", "suffix", "gender", "birthdate", "civil_status", "purok"];
                        options.columnHeaders = {
                            "first_name": "First Name",
                            "middle_name": "Middle Name",
                            "last_name": "Last Name",
                            "suffix": "Suffix", 
                            "gender": "Gender",
                            "birthdate": "Birthdate",
                            "civil_status": "Civil Status",
                            "purok": "Purok"
                        };
                        // Clean data for Excel export
                        options.mutateData = function(data) {
                            return data.map(row => {
                                if (row.purok) row.purok = row.purok.replace(/\s*\([^)]*\)/g, '').trim();
                                return row;
                            });
                        };
                        options.documentProcessing = function(workbook) {
                            // Add metadata to Excel file
                            workbook.Props = {
                                Title: "Residents Data Export",
                                Subject: "Barangay Residents Information",
                                Author: "<?php echo htmlspecialchars($user_full_name); ?>",
                                CreatedDate: new Date()
                            };
                            return workbook;
                        };
                        window.table.download("xlsx", `${filename}.xlsx`, options);
                        break;
                        
                    case 'pdf':
                        options.orientation = "landscape";
                        options.title = "Residents Information Report";
                        // Explicitly define columns to include in PDF export
                        options.columns = ["first_name", "middle_name", "last_name", "suffix", "gender", "birthdate", "civil_status", "purok"];
                        options.columnHeaders = {
                            "first_name": "First Name",
                            "middle_name": "Middle Name",
                            "last_name": "Last Name",
                            "suffix": "Suffix", 
                            "gender": "Gender",
                            "birthdate": "Birthdate",
                            "civil_status": "Civil Status",
                            "purok": "Purok"
                        };
                        options.autoTable = {
                            styles: {
                                fontSize: 7,
                                cellPadding: 1.5,
                                overflow: 'linebreak',
                                halign: 'center'
                            },
                            headStyles: {
                                fillColor: [59, 130, 246],
                                textColor: 255,
                                fontSize: 8,
                                fontStyle: 'bold',
                                halign: 'center'
                            },
                            columnStyles: {
                                0: {cellWidth: 40},  // First Name
                                1: {cellWidth: 35},  // Middle Name
                                2: {cellWidth: 40},  // Last Name
                                3: {cellWidth: 20},  // Suffix
                                4: {cellWidth: 25},  // Gender
                                5: {cellWidth: 40},  // Birthdate
                                6: {cellWidth: 35},  // Civil Status
                                7: {cellWidth: 25},  // Purok
                            },
                            margin: {top: 35, left: 8, right: 8},
                            pageBreak: 'auto'
                        };
                        options.documentProcessing = function(doc) {
                            // Add header to PDF
                            doc.setFontSize(16);
                            doc.text("<?php echo htmlspecialchars($system_title); ?>", 14, 22);
                            doc.setFontSize(12);
                            doc.text("Residents Information Report", 14, 28);
                            doc.setFontSize(10);
                            doc.text(`Generated on: ${new Date().toLocaleDateString()} by <?php echo htmlspecialchars($user_full_name); ?>`, 14, 34);
                            
                            // Add footer with page numbers
                            const pageCount = doc.internal.getNumberOfPages();
                            for (let i = 1; i <= pageCount; i++) {
                                doc.setPage(i);
                                doc.setFontSize(8);
                                doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.width - 30, doc.internal.pageSize.height - 10);
                            }
                            
                            return doc;
                        };
                        // Format data for better PDF display
                        options.mutateData = function(data) {
                            return data.map(row => {
                                // Clean purok name for PDF
                                if (row.purok) {
                                    row.purok = row.purok.replace(/\s*\([^)]*\)/g, '').trim();
                                }
                                // Format birthdate for better display
                                if (row.birthdate) {
                                    try {
                                        const date = new Date(row.birthdate);
                                        row.birthdate = date.toLocaleDateString('en-US', { 
                                            year: 'numeric', 
                                            month: 'short', 
                                            day: 'numeric' 
                                        });
                                    } catch (e) {
                                        // Keep original if formatting fails
                                    }
                                }
                                return row;
                            });
                        };
                        window.table.download("pdf", `${filename}.pdf`, options);
                        break;
                }
            }
            
            function closeExportModal() {
                const modal = document.getElementById('exportModal');
                modal.classList.add('opacity-0');
                modal.classList.remove('opacity-100');
                modal.querySelector('.modal-container').classList.add('scale-95');
                modal.querySelector('.modal-container').classList.remove('scale-100');
                setTimeout(() => {
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                }, 300);
            }
            
            // Fetch Puroks and Religions for dropdowns
            fetchPuroks();
            fetchReligions();
        });
        
        function initializeModalHandlers() {
            // Use event delegation for dynamically generated buttons
            // This will work for all pages of the table
            document.addEventListener('click', function(e) {
                // Handle View Resident button clicks
                if (e.target.closest('.view-resident-btn')) {
                    e.preventDefault();
                    const btn = e.target.closest('.view-resident-btn');
                    const residentId = btn.getAttribute('data-id');
                    if (residentId) {
                        viewResidentDetails(residentId);
                    }
                }
                
                // Handle Edit Resident button clicks
                if (e.target.closest('.edit-resident-btn')) {
                    e.preventDefault();
                    const btn = e.target.closest('.edit-resident-btn');
                    const residentId = btn.getAttribute('data-id');
                    if (residentId) {
                        editResident(residentId);
                    }
                }
                
                // Handle Delete Resident button clicks
                if (e.target.closest('.delete-resident-btn')) {
                    e.preventDefault();
                    const btn = e.target.closest('.delete-resident-btn');
                    const residentId = btn.getAttribute('data-id');
                    if (residentId) {
                        showDeleteConfirmation(residentId);
                    }
                }
            });
            
            // Close View Resident Modal (only attach once)
            const closeViewBtn = document.getElementById('closeViewResidentModal');
            if (closeViewBtn && !closeViewBtn.hasAttribute('data-listener-attached')) {
                closeViewBtn.addEventListener('click', function() {
                    closeViewResidentModal();
                });
                closeViewBtn.setAttribute('data-listener-attached', 'true');
            }
            
            // Add ESC key support to close modals (only attach once)
            if (!document.body.hasAttribute('data-esc-listener-attached')) {
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        const viewModal = document.getElementById('viewResidentModal');
                        if (viewModal && !viewModal.classList.contains('hidden')) {
                            closeViewResidentModal();
                        }
                        const editModal = document.getElementById('editResidentModal');
                        if (editModal && !editModal.classList.contains('hidden')) {
                            closeEditResidentModal();
                        }
                        const deleteModal = document.getElementById('deleteResidentModal');
                        if (deleteModal && !deleteModal.classList.contains('hidden')) {
                            closeDeleteResidentModal();
                        }
                    }
                });
                document.body.setAttribute('data-esc-listener-attached', 'true');
            }
        }
        
        function viewResidentDetails(residentId) {
            // Show loading in the view modal
            const viewModal = document.getElementById('viewResidentModal');
            viewModal.style.display = 'flex';
            setTimeout(() => {
                viewModal.classList.remove('hidden');
                viewModal.classList.add('flex');
                viewModal.classList.remove('opacity-0');
                if (viewModal.querySelector('.modal-container')) {
                    viewModal.querySelector('.modal-container').classList.remove('scale-95');
                }
            }, 10);
            document.getElementById('viewResidentContent').innerHTML = `
                <div class="text-center py-10">
                    <div class="text-blue-500 text-3xl mb-3">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <p class="text-gray-600">Loading resident details...</p>
                </div>
            `;

            // Fetch resident details
            fetch(`fetch_individual_detail.php?id=${residentId}`)
                .then(res => res.json())
                .then(resident => {
                    if (resident.error) {
                        document.getElementById('viewResidentContent').innerHTML = `
                            <div class="text-center py-10">
                                <div class="text-red-500 text-5xl mb-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <p class="text-gray-700 font-medium">Error loading resident details</p>
                                <p class="text-gray-500 mt-2">${resident.error}</p>
                                <div class="mt-4">
                                    <button onclick="closeViewResidentModal()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                        Close
                                    </button>
                                </div>
                            </div>
                        `;
                        return;
                    }
            // Modal content with same structure as edit modal but readonly
            let html = `
                <form class="space-y-6">
                    <!-- Personal Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-user text-blue-600"></i>
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.first_name || ''}" readonly />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.last_name || ''}" readonly />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.middle_name || ''}" readonly />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Suffix</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.suffix || ''}" readonly />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.gender || ''}" readonly />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Birthdate</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.birthdate || ''}" readonly />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Basic Details Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-green-600"></i>
                            Basic Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Civil Status</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.civil_status || ''}" readonly />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Blood Type</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.blood_type || ''}" readonly />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.religion || ''}" readonly />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Residing Purok</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.purok_name || resident.purok || ''}" readonly />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-address-book text-purple-600"></i>
                            Contact Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" value="${resident.email || ''}" readonly />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Information Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-tags text-orange-600"></i>
                            Status Information
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50">
                                <input type="checkbox" disabled ${(resident.is_pwd == 1 || resident.is_pwd == '1' || resident.is_pwd === true) ? 'checked' : ''} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <span class="text-sm font-medium text-gray-700">PWD</span>
                            </div>
                            <div class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50">
                                <input type="checkbox" disabled ${(resident.is_voter == 1 || resident.is_voter == '1' || resident.is_voter === true) ? 'checked' : ''} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <span class="text-sm font-medium text-gray-700">Registered Voter</span>
                            </div>
                            <div class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50">
                                <input type="checkbox" disabled ${(resident.is_4ps == 1 || resident.is_4ps == '1' || resident.is_4ps === true) ? 'checked' : ''} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <span class="text-sm font-medium text-gray-700">4Ps Beneficiary</span>
                            </div>
                            <div class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50">
                                <input type="checkbox" disabled ${(resident.is_pregnant == 1 || resident.is_pregnant == '1' || resident.is_pregnant === true) ? 'checked' : ''} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <span class="text-sm font-medium text-gray-700">Pregnant</span>
                            </div>
                            <div class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50">
                                <input type="checkbox" disabled ${(resident.is_solo_parent == 1 || resident.is_solo_parent == '1' || resident.is_solo_parent === true) ? 'checked' : ''} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <span class="text-sm font-medium text-gray-700">Solo Parent</span>
                            </div>
                            <div class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg bg-gray-50">
                                <input type="checkbox" disabled ${(resident.is_senior_citizen == 1 || resident.is_senior_citizen == '1' || resident.is_senior_citizen === true) ? 'checked' : ''} class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                <span class="text-sm font-medium text-gray-700">Senior Citizen</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Close Button -->
                    <div class='flex justify-end gap-3 pt-6 border-t border-gray-200'>
                        <button type='button' onclick='closeViewResidentModal()' class='px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500'>
                            <i class="fas fa-times mr-2"></i>Close
                        </button>
                    </div>
                </form>
            `;
            document.getElementById('viewResidentContent').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('viewResidentContent').innerHTML = `
                        <div class="text-center py-10">
                            <div class="text-red-500 text-5xl mb-3">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <p class="text-gray-700 font-medium">Failed to load resident details</p>
                            <p class="text-gray-500 mt-2">Network error</p>
                            <div class="mt-4">
                                <button onclick="closeViewResidentModal()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                    Close
                                </button>
                            </div>
                        </div>
                    `;
                });
        }
        
        // Close the view resident modal
        function closeViewResidentModal() {
            const modal = document.getElementById('viewResidentModal');
            // Hide with transition if present, else fallback
            if (modal.querySelector('.modal-container')) {
                modal.querySelector('.modal-container').style.transform = 'scale(0.95)';
            }
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.opacity = '';
                if (modal.querySelector('.modal-container')) {
                    modal.querySelector('.modal-container').style.transform = '';
                }
                document.getElementById('viewResidentContent').innerHTML = '';
            }, 300);
        }
        
        // Close the edit resident modal
        function closeEditResidentModal() {
            const modal = document.getElementById('editResidentModal');
            if (modal) {
                modal.classList.add('opacity-0');
                if (modal.querySelector('.modal-container')) {
                    modal.querySelector('.modal-container').classList.remove('scale-100');
                    modal.querySelector('.modal-container').classList.add('scale-95');
                }
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.style.display = 'none';
                }, 300);
            }
        }
        
        function editResident(residentId) {
            // Get the edit modal
            const modal = document.getElementById('editResidentModal');
            
            // Show loading state
            modal.querySelector('.p-6').innerHTML = `
                <div class="text-center py-10">
                    <div class="text-blue-500 text-3xl mb-3">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <p class="text-gray-600">Loading resident data...</p>
                </div>
            `;
            
            // Show the modal
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.remove('hidden', 'opacity-0');
                modal.querySelector('.modal-container').classList.remove('scale-95');
                modal.querySelector('.modal-container').classList.add('scale-100');
            }, 10);
            
            // Fetch resident details for editing
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `fetch_individual_detail.php?id=${residentId}`, true);
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const resident = JSON.parse(this.responseText);
                        
                        if (resident.error) {
                            modal.querySelector('.p-6').innerHTML = `
                                <div class="text-center py-10">
                                    <div class="text-red-500 text-5xl mb-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <p class="text-gray-700 font-medium">Error loading resident data</p>
                                    <p class="text-gray-500 mt-2">${resident.error}</p>
                                    <div class="mt-4">
                                        <button onclick="closeEditResidentModal()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            `;
                        } else {
                            // Debug: Log the resident data to see what we're getting
                            console.log('Resident data from database:', resident);
                            
                            // Populate edit form with resident data
                            modal.querySelector('.p-6').innerHTML = `
                                <form id="editResidentForm" class="space-y-4">
                                    <input type="hidden" name="id" value="${resident.id}">
                                    
                                    <!-- Personal Information Section -->
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                            <i class="fas fa-user text-blue-600"></i>
                                            Personal Information
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="form-label">First Name <span class="text-red-500">*</span></label>
                                                <input type="text" name="first_name" value="${resident.first_name || ''}" required class="form-input">
                                            </div>
                                            <div>
                                                <label class="form-label">Middle Name</label>
                                                <input type="text" name="middle_name" value="${resident.middle_name || ''}" class="form-input">
                                            </div>
                                            <div>
                                                <label class="form-label">Last Name <span class="text-red-500">*</span></label>
                                                <input type="text" name="last_name" value="${resident.last_name || ''}" required class="form-input">
                                            </div>
                                            <div>
                                                <label class="form-label">Suffix</label>
                                                <input type="text" name="suffix" value="${resident.suffix || ''}" class="form-input" placeholder="e.g., III, Jr., Sr., II">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Basic Details Section -->
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                            <i class="fas fa-info-circle text-blue-600"></i>
                                            Basic Details
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="form-label">Gender <span class="text-red-500">*</span></label>
                                                <select name="gender" required class="form-select ${(!resident.gender || resident.gender === '') ? 'border-orange-300 bg-orange-50' : ''}">
                                                    <option value="">${(!resident.gender || resident.gender === '') ? 'Please Select Gender (Required)' : 'Select Gender'}</option>
                                                    <option value="Male" ${(resident.gender === 'M' || resident.gender === 'Male' || resident.gender === 'male') ? 'selected' : ''}>Male</option>
                                                    <option value="Female" ${(resident.gender === 'F' || resident.gender === 'Female' || resident.gender === 'female') ? 'selected' : ''}>Female</option>
                                                </select>
                                                ${(!resident.gender || resident.gender === '') ? '<p class="text-orange-600 text-xs mt-1"><i class="fas fa-exclamation-triangle"></i> Gender information is missing and needs to be selected</p>' : ''}
                                            </div>
                                            <div>
                                                <label class="form-label">Birthdate <span class="text-red-500">*</span></label>
                                                <input type="date" name="birthdate" value="${resident.birthdate || ''}" required class="form-input">
                                            </div>
                                            <div>
                                                <label class="form-label">Civil Status <span class="text-red-500">*</span></label>
                                                <select name="civil_status" required class="form-select">
                                                    <option value="">Select Civil Status</option>
                                                    <option value="Single" ${(resident.civil_status && resident.civil_status.toLowerCase() === 'single') ? 'selected' : ''}>Single</option>
                                                    <option value="Married" ${(resident.civil_status && resident.civil_status.toLowerCase() === 'married') ? 'selected' : ''}>Married</option>
                                                    <option value="Widowed" ${(resident.civil_status && resident.civil_status.toLowerCase() === 'widowed') ? 'selected' : ''}>Widowed</option>
                                                    <option value="Separated" ${(resident.civil_status && resident.civil_status.toLowerCase() === 'separated') ? 'selected' : ''}>Separated</option>
                                                    <option value="Divorced" ${(resident.civil_status && resident.civil_status.toLowerCase() === 'divorced') ? 'selected' : ''}>Divorced</option>
                                                    <option value="Annulled" ${(resident.civil_status && resident.civil_status.toLowerCase() === 'annulled') ? 'selected' : ''}>Annulled</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Blood Type</label>
                                                <select name="blood_type" class="form-select">
                                                    <option value="">Select Blood Type</option>
                                                    <option value="A+" ${(resident.blood_type === 'A+') ? 'selected' : ''}>A+</option>
                                                    <option value="A-" ${(resident.blood_type === 'A-') ? 'selected' : ''}>A-</option>
                                                    <option value="B+" ${(resident.blood_type === 'B+') ? 'selected' : ''}>B+</option>
                                                    <option value="B-" ${(resident.blood_type === 'B-') ? 'selected' : ''}>B-</option>
                                                    <option value="AB+" ${(resident.blood_type === 'AB+') ? 'selected' : ''}>AB+</option>
                                                    <option value="AB-" ${(resident.blood_type === 'AB-') ? 'selected' : ''}>AB-</option>
                                                    <option value="O+" ${(resident.blood_type === 'O+') ? 'selected' : ''}>O+</option>
                                                    <option value="O-" ${(resident.blood_type === 'O-') ? 'selected' : ''}>O-</option>
                                                    <option value="Unknown" ${(resident.blood_type === 'Unknown') ? 'selected' : ''}>Unknown</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Religion <span class="text-red-500">*</span></label>
                                                <select name="religion" id="edit_religion" required class="form-select">
                                                    <option value="">Loading...</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Residing Purok <span class="text-red-500">*</span></label>
                                                <select name="purok_id" id="edit_purok" required class="form-select">
                                                    <option value="">Loading...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Contact Information Section -->
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                            <i class="fas fa-address-book text-green-600"></i>
                                            Contact Information
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="form-label">Email Address</label>
                                                <input type="email" name="email" value="${resident.email || ''}" class="form-input" placeholder="e.g., juan.delacruz@email.com">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Information Section -->
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                            <i class="fas fa-tags text-blue-600"></i>
                                            Status Information
                                        </h3>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" name="is_pwd" value="1" ${(resident.is_pwd == 1 || resident.is_pwd == '1' || resident.is_pwd === true) ? 'checked' : ''} class="form-checkbox">
                                                <span class="text-sm font-medium">PWD</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" name="is_voter" value="1" ${(resident.is_voter == 1 || resident.is_voter == '1' || resident.is_voter === true) ? 'checked' : ''} class="form-checkbox">
                                                <span class="text-sm font-medium">Registered Voter</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" name="is_4ps" value="1" ${(resident.is_4ps == 1 || resident.is_4ps == '1' || resident.is_4ps === true) ? 'checked' : ''} class="form-checkbox">
                                                <span class="text-sm font-medium">4Ps Beneficiary</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" name="is_pregnant" value="1" ${(resident.is_pregnant == 1 || resident.is_pregnant == '1' || resident.is_pregnant === true) ? 'checked' : ''} class="form-checkbox">
                                                <span class="text-sm font-medium">Pregnant</span>
                                            </label>
                                            <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" name="is_solo_parent" value="1" ${(resident.is_solo_parent == 1 || resident.is_solo_parent == '1' || resident.is_solo_parent === true) ? 'checked' : ''} class="form-checkbox">
                                                <span class="text-sm font-medium">Solo Parent</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Form Messages -->
                                    <div id="editResidentMsg" class="mt-4"></div>
                                    
                                    <!-- Form Actions -->
                                    <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                                        <button type="button" id="cancelEditResident" class="btn-secondary">
                                            <i class="fas fa-times"></i>
                                            Cancel
                                        </button>
                                        <button type="submit" class="btn-primary">
                                            <i class="fas fa-save"></i>
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            `;
                            
                            // Load dropdown options and select current values
                            fetchPuroks(() => {
                                const purokSelect = document.getElementById('edit_purok');
                                if (purokSelect && resident.purok_id) {
                                    purokSelect.value = resident.purok_id;
                                }
                            });
                            
                            fetchReligions(() => {
                                // Add a small delay to ensure dropdown is fully rendered
                                setTimeout(() => {
                                    const religionSelect = document.getElementById('edit_religion');
                                    if (religionSelect && resident.religion) {
                                        // Try exact match first
                                        religionSelect.value = resident.religion;
                                        
                                        // If exact match didn't work, try to find by text content
                                        if (!religionSelect.value && resident.religion) {
                                            for (let option of religionSelect.options) {
                                                if (option.text.trim() === resident.religion.trim()) {
                                                    religionSelect.value = option.value;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }, 100);
                            });
                            
                            // Re-attach form event handlers
                            attachEditFormHandlers();
                        }
                    } catch (e) {
                        modal.querySelector('.p-6').innerHTML = `
                            <div class="text-center py-10">
                                <div class="text-red-500 text-5xl mb-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <p class="text-gray-700 font-medium">Error parsing resident data</p>
                                <p class="text-gray-500 mt-2">Please try again later</p>
                                <div class="mt-4">
                                    <button onclick="closeEditResidentModal()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                        Close
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    modal.querySelector('.p-6').innerHTML = `
                        <div class="text-center py-10">
                            <div class="text-red-500 text-5xl mb-3">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <p class="text-gray-700 font-medium">Failed to load resident data</p>
                            <p class="text-gray-500 mt-2">Server error (${this.status})</p>
                            <div class="mt-4">
                                <button onclick="closeEditResidentModal()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                    Close
                                </button>
                            </div>
                        </div>
                    `;
                }
            };
            xhr.send();
            
            // Re-attach close event handler
            setTimeout(() => {
                const closeBtn = document.getElementById('closeEditResidentModal');
                if (closeBtn) {
                    closeBtn.onclick = closeEditResidentModal;
                }
                const cancelBtn = document.getElementById('cancelEditResident');
                if (cancelBtn) {
                    cancelBtn.onclick = closeEditResidentModal;
                }
            }, 100);
        }
        
        function attachEditFormHandlers() {
            // Cancel button
            document.getElementById('cancelEditResident').addEventListener('click', closeEditResidentModal);
            
            // Form submission
            document.getElementById('editResidentForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const msgElement = document.getElementById('editResidentMsg');
                
                // Show loading
                msgElement.innerHTML = `<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Updating resident...</div>`;
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'edit_individual.php', true);
                xhr.onload = function() {
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            
                            if (response.success) {
                                msgElement.innerHTML = `<div class="text-green-600">${response.message}</div>`;
                                
                                // Refresh the table after a short delay
                                setTimeout(() => {
                                    window.table.setData("fetch_individuals.php");
                                    
                                    // Close modal after data refresh
                                    setTimeout(() => {
                                        closeEditResidentModal();
                                    }, 500);
                                }, 1000);
                            } else {
                                msgElement.innerHTML = `<div class="text-red-600">${response.message}</div>`;
                            }
                        } catch (e) {
                            msgElement.innerHTML = `<div class="text-red-600">Error processing response</div>`;
                            console.error("Error parsing response:", this.responseText);
                        }
                    } else {
                        msgElement.innerHTML = `<div class="text-red-600">Server error (${this.status})</div>`;
                    }
                };
                xhr.send(formData);
            });
        }
        
        function showDeleteConfirmation(residentId) {
            const modal = document.getElementById('deleteResidentModal');
            const confirmBtn = document.getElementById('confirmDeleteResident');
            const deleteButtonText = document.getElementById('deleteButtonText');
            
            // Store the resident ID for later use
            modal.setAttribute('data-resident-id', residentId);
            
            // Reset any previous messages
            document.getElementById('deleteResidentMsg').innerHTML = '';
            
            // Reset button state
            confirmBtn.disabled = true;
            confirmBtn.classList.add('disabled:bg-gray-400', 'disabled:cursor-not-allowed');
            confirmBtn.classList.remove('hover:bg-red-600');
            
            // Start countdown timer
            let countdown = 4;
            deleteButtonText.textContent = `Delete (${countdown})`;
            
            const countdownTimer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    deleteButtonText.textContent = `Delete (${countdown})`;
                } else {
                    // Enable the button after countdown
                    clearInterval(countdownTimer);
                    confirmBtn.disabled = false;
                    confirmBtn.classList.remove('disabled:bg-gray-400', 'disabled:cursor-not-allowed');
                    confirmBtn.classList.add('hover:bg-red-600');
                    deleteButtonText.textContent = 'Delete';
                }
            }, 1000);
            
            // Store timer reference to clear it if modal is closed
            modal.countdownTimer = countdownTimer;
            
            // Show the modal
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('flex');
                modal.classList.remove('opacity-0');
                modal.querySelector('.modal-container').classList.remove('scale-95');
            }, 10);
            
            // Attach event handlers
            document.getElementById('cancelDeleteResident').onclick = closeDeleteResidentModal;
            document.getElementById('closeDeleteResidentModal').onclick = closeDeleteResidentModal;
            confirmBtn.onclick = function() {
                if (!confirmBtn.disabled) {
                    deleteResident(residentId);
                }
            };
        }
        
        function closeDeleteResidentModal() {
            const modal = document.getElementById('deleteResidentModal');
            const confirmBtn = document.getElementById('confirmDeleteResident');
            const deleteButtonText = document.getElementById('deleteButtonText');
            
            // Clear countdown timer if it exists
            if (modal.countdownTimer) {
                clearInterval(modal.countdownTimer);
                modal.countdownTimer = null;
            }
            
            // Reset button state
            confirmBtn.disabled = true;
            confirmBtn.classList.add('disabled:bg-gray-400', 'disabled:cursor-not-allowed');
            confirmBtn.classList.remove('hover:bg-red-600');
            deleteButtonText.textContent = 'Delete (4)';
            
            modal.classList.add('opacity-0');
            modal.querySelector('.modal-container').classList.add('scale-95');
            
            // Hide the modal after the transition
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.getElementById('deleteResidentMsg').innerHTML = ''; // Clear any messages
            }, 300);
        }

        function deleteResident(residentId) {
            const msgElement = document.getElementById('deleteResidentMsg');
            const confirmBtn = document.getElementById('confirmDeleteResident');
            const cancelBtn = document.getElementById('cancelDeleteResident');
            
            // Show loading state
            msgElement.innerHTML = `<div class="text-blue-600"><i class="fas fa-spinner fa-spin mr-2"></i>Deleting resident...</div>`;
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            
            // Send delete request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'delete_individual.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    try {
                        const response = JSON.parse(this.responseText);
                        
                        if (response.success) {
                            msgElement.innerHTML = `<div class="text-green-600">${response.message}</div>`;
                            
                            // Refresh the table after a short delay
                            setTimeout(() => {
                                window.table.setData("fetch_individuals.php");
                                
                                // Close modal after data refresh
                                setTimeout(() => {
                                    closeDeleteResidentModal();
                                }, 500);
                            }, 1000);
                        } else {
                            msgElement.innerHTML = `<div class="text-red-600">${response.message}</div>`;
                            // Re-enable buttons on error
                            confirmBtn.disabled = false;
                            cancelBtn.disabled = false;
                        }
                    } catch (e) {
                        msgElement.innerHTML = `<div class="text-red-600">Error processing response</div>`;
                        console.error("Error parsing response:", this.responseText);
                        // Re-enable buttons on error
                        confirmBtn.disabled = false;
                        cancelBtn.disabled = false;
                    }
                } else {
                    msgElement.innerHTML = `<div class="text-red-600">Server error (${this.status})</div>`;
                    // Re-enable buttons on error
                    confirmBtn.disabled = false;
                    cancelBtn.disabled = false;
                }
            };
            xhr.send(`id=${encodeURIComponent(residentId)}`);
        }
        
        // Fetch functions for dropdowns
        function fetchPuroks(callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_puroks.php', true);
            xhr.onload = function() {
                if (this.status === 200) {
                    const puroks = JSON.parse(this.responseText);
                    const addSelect = document.getElementById('purokSelect');
                    const editSelect = document.getElementById('edit_purok');
                    
                    // Clear existing options except the first one
                    if (addSelect) {
                        addSelect.innerHTML = '<option value="">Select Purok</option>';
                    }
                    if (editSelect) {
                        editSelect.innerHTML = '<option value="">Select Purok</option>';
                    }
                    
                    puroks.forEach(purok => {
                        if (addSelect) {
                            addSelect.innerHTML += `<option value="${purok.id}">${purok.name}</option>`;
                        }
                        if (editSelect) {
                            editSelect.innerHTML += `<option value="${purok.id}">${purok.name}</option>`;
                        }
                    });
                    
                    // Execute callback if provided
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            };
            xhr.send();
        }
        
        function fetchReligions(callback) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_religions.php', true);
            xhr.onload = function() {
                if (this.status === 200) {
                    const religions = JSON.parse(this.responseText);
                    const addSelect = document.getElementById('religionSelect');
                    const editSelect = document.getElementById('edit_religion');
                    
                    // Clear existing options except the first one
                    if (addSelect) {
                        addSelect.innerHTML = '<option value="">Select Religion</option>';
                    }
                    if (editSelect) {
                        editSelect.innerHTML = '<option value="">Select Religion</option>';
                    }
                    
                    religions.forEach(religion => {
                        if (addSelect) {
                            addSelect.innerHTML += `<option value="${religion.id}">${religion.name}</option>`;
                        }
                        if (editSelect) {
                            editSelect.innerHTML += `<option value="${religion.id}">${religion.name}</option>`;
                        }
                    });
                    
                    // Execute callback if provided
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }
            };
            xhr.send();
        }
    </script>
</body>
</html>
