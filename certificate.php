<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once 'config.php';
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    // Log the detailed error for yourself
    error_log('Database connection failed: ' . $conn->connect_error);
    // Show a friendly message to the user
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
    <title>Document Generation - <?php echo htmlspecialchars($system_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Certificate card styles */
        .certificate-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: border-color 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }
        .form-radio {
            accent-color: #667eea;
            width: 1rem;
            height: 1rem;
        }
        .form-checkbox {
            accent-color: #667eea;
            transform: scale(1.1);
        }

        /* Resident Search Styles */
        #residentSearchSection {
            position: relative;
        }
        
        #searchResults {
            max-height: 250px;
            z-index: 1000;
        }
        
        .search-result-item {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        
        .search-result-item:hover {
            background-color: #f3f4f6;
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .search-result-details {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .no-results {
            padding: 16px;
            text-align: center;
            color: #6b7280;
            font-style: italic;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Include Navigation -->
    <?php include 'navigation.php'; ?>
    <!-- Main Content -->
    <div id="mainContent" class="flex-1 transition-all duration-300 ease-in-out p-2 md:px-0 md:pt-4 mt-16 flex flex-col items-center">
        <div class="w-full max-w-7xl px-4 md:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <i class="fas fa-certificate text-blue-600 text-xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-800">Document Generation</h1>
                </div>
                <p class="text-gray-600">Generate barangay certificates, clearances, and identification documents for residents</p>
            </div>

            <!-- Certificate Types Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Barangay Clearance -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('clearance')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-file-alt text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">01</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Barangay Clearance</h3>
                    <p class="text-white/80">General clearance certificate for residents</p>
                </div>

                <!-- Certificate of First Time Job Seeker -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('first_time_job_seeker')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-user-graduate text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">02</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">First Time Job Seeker</h3>
                    <p class="text-white/80">Certificate for first-time job seekers</p>
                </div>

                <!-- Certificate of Residency -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('residency')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-home text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">03</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Certificate of Residency</h3>
                    <p class="text-white/80">Proof of residence in the barangay</p>
                </div>

                <!-- Certificate of Indigency -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('indigency')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-hand-holding-heart text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">04</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Certificate of Indigency</h3>
                    <p class="text-white/80">Financial status certification</p>
                </div>

                <!-- Barangay ID -->
                <div class="certificate-card p-6 text-white cursor-pointer" onclick="openCertificateModal('barangay_id')">
                    <div class="flex items-center justify-between mb-4">
                        <i class="fas fa-id-card text-3xl"></i>
                        <div class="text-right">
                            <div class="text-2xl font-bold">05</div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Barangay ID</h3>
                    <p class="text-white/80">Official barangay identification card</p>
                </div>
            </div>

            <!-- Recent Certificates -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-history text-blue-600"></i>
                        Recent Certificates
                    </h2>
                    <button class="btn-secondary" onclick="location.href='issued_documents.php'">
                        <i class="fas fa-list mr-2"></i>View All
                    </button>
                </div>
                
                <!-- Recent certificates table placeholder -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Certificate No.</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Resident Name</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Certificate Type</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Date Issued</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Purpose</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recentCertificatesTable">
                            <!-- Dynamic content will be loaded here -->
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                    No recent certificates found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificate Generation Modal -->
    <div id="certificateModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Generate Document</h3>
                <button id="closeCertificateModal" class="text-gray-500 hover:text-red-500 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="certificateForm" class="p-6">
                <input type="hidden" id="certificateType" name="certificate_type">
                
                <!-- Resident Search Section -->
                <div id="residentSearchSection" class="mb-6">
                    <div class="form-group">
                        <label class="form-label" for="residentSearchInput">
                            <i class="fas fa-search mr-2"></i>Search Resident
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   class="form-input" 
                                   id="residentSearchInput" 
                                   name="resident_search" 
                                   placeholder="Type resident name to search..." 
                                   autocomplete="off">
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Search Results Dropdown -->
                        <div id="searchResults" class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                            <!-- Dynamic search results will appear here -->
                        </div>
                        
                        <!-- Hidden input to store selected resident ID -->
                        <input type="hidden" id="selectedResidentId" name="resident_id">
                        
                        <!-- Selected resident display -->
                        <div id="selectedResidentDisplay" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg hidden">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-green-800">Selected Resident:</p>
                                    <p id="selectedResidentName" class="text-green-700"></p>
                                </div>
                                <button type="button" onclick="clearSelectedResident()" class="text-green-600 hover:text-green-800">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center my-4">
                        <button type="button" id="registerNewResidentBtn" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                            <i class="fas fa-plus mr-1"></i>Resident not on the list? Register them now
                        </button>
                    </div>
                </div>

                <!-- New Resident Form -->
                <!-- Purpose -->
                <div class="form-group">
                    <label class="form-label" for="purpose">
                        <i class="fas fa-info-circle mr-2"></i>Purpose
                    </label>
                    <input type="text" class="form-input" id="purpose" name="purpose" placeholder="Enter the purpose of the certificate">
                </div>

                <!-- Additional fields will be shown based on certificate type -->
                <div id="additionalFields"></div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4">
                    <button type="button" class="btn-secondary flex-1" onclick="closeCertificateModal()">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn-primary flex-1">
                        <i class="fas fa-file-pdf mr-2"></i>Generate Document
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Global variables
        let preSelectedResidentId = null;

        // Check URL parameters for pre-selected resident
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            preSelectedResidentId = urlParams.get('resident_id');
            
            if (preSelectedResidentId) {
                // If coming from individuals page, show certificate selection
                showCertificateSelectionForResident(preSelectedResidentId);
            }
        });

        // Certificate Modal Functions
        const certificateModal = document.getElementById('certificateModal');
        const closeCertificateModalBtn = document.getElementById('closeCertificateModal');

        function showCertificateSelectionForResident(residentId) {
            // Show a certificate type selection modal first
            if (confirm('Select a certificate type for this resident. Click OK to continue to certificate selection.')) {
                // For now, just open the clearance modal with the resident pre-selected
                openCertificateModal('clearance', residentId);
            }
        }

        function openCertificateModal(type, residentId = null) {
            const titles = {
                'clearance': 'Generate Barangay Clearance',
                'first_time_job_seeker': 'Generate Certificate of First Time Job Seeker',
                'residency': 'Generate Certificate of Residency',
                'indigency': 'Generate Certificate of Indigency',
                'barangay_id': 'Generate Barangay ID'
            };
            
            document.getElementById('modalTitle').textContent = titles[type] || 'Generate Document';
            document.getElementById('certificateType').value = type;
            
            // Load additional fields based on certificate type
            loadAdditionalFields(type);
            
            // Load all residents for search functionality
            loadAllResidents();
            
            // Auto-select resident if provided
            if (residentId || preSelectedResidentId) {
                const selectResidentId = residentId || preSelectedResidentId;
                
                // Find and select the resident
                setTimeout(() => {
                    const resident = allResidents.find(r => r.id == selectResidentId);
                    if (resident) {
                        const fullName = `${resident.first_name} ${resident.middle_name || ''} ${resident.last_name} ${resident.suffix || ''}`;
                        selectResident(resident.id, fullName);
                    }
                }, 500); // Small delay to ensure residents are loaded
                
                // Clear the URL parameter after using it
                if (preSelectedResidentId) {
                    const url = new URL(window.location);
                    url.searchParams.delete('resident_id');
                    window.history.replaceState({}, document.title, url.toString());
                    preSelectedResidentId = null;
                }
            }
            
            certificateModal.classList.remove('hidden');
            certificateModal.style.display = 'flex';
        }

        function closeCertificateModal() {
            certificateModal.classList.add('hidden');
            certificateModal.style.display = 'none';
            document.getElementById('certificateForm').reset();
            
            // Clear search results and selections
            clearSelectedResident();
            document.getElementById('residentSearchInput').value = '';
            document.getElementById('searchResults').classList.add('hidden');
        }

        closeCertificateModalBtn.addEventListener('click', closeCertificateModal);

        // Close modal when clicking outside
        certificateModal.addEventListener('click', (e) => {
            if (e.target === certificateModal) {
                closeCertificateModal();
            }
        });

        // Resident search functionality
        let searchTimeout;
        let allResidents = [];

        // Load all residents for searching
        function loadAllResidents() {
            fetch('fetch_individuals.php')
                .then(response => response.json())
                .then(data => {
                    allResidents = data;
                })
                .catch(error => {
                    console.error('Error loading residents:', error);
                });
        }

        // Search residents as user types
        document.addEventListener('input', function(e) {
            if (e.target.id === 'residentSearchInput') {
                clearTimeout(searchTimeout);
                const searchTerm = e.target.value.trim();
                
                if (searchTerm.length < 2) {
                    document.getElementById('searchResults').classList.add('hidden');
                    return;
                }
                
                searchTimeout = setTimeout(() => {
                    searchResidents(searchTerm);
                }, 300); // Debounce search
            }
        });

        function searchResidents(searchTerm) {
            const searchResults = document.getElementById('searchResults');
            const results = allResidents.filter(resident => {
                const fullName = `${resident.first_name} ${resident.middle_name || ''} ${resident.last_name} ${resident.suffix || ''}`.toLowerCase();
                return fullName.includes(searchTerm.toLowerCase());
            });

            if (results.length === 0) {
                searchResults.innerHTML = '<div class="no-results">No residents found matching your search</div>';
            } else {
                searchResults.innerHTML = results.map(resident => `
                    <div class="search-result-item" onclick="selectResident(${resident.id}, '${resident.first_name} ${resident.middle_name || ''} ${resident.last_name} ${resident.suffix || ''}')">
                        <div class="search-result-name">${resident.first_name} ${resident.middle_name || ''} ${resident.last_name} ${resident.suffix || ''}</div>
                        <div class="search-result-details">Age: ${calculateAge(resident.birthdate)} • Gender: ${resident.gender} • Purok: ${resident.purok_name || 'N/A'}</div>
                    </div>
                `).join('');
            }
            
            searchResults.classList.remove('hidden');
        }

        function selectResident(residentId, residentName) {
            document.getElementById('selectedResidentId').value = residentId;
            document.getElementById('selectedResidentName').textContent = residentName.trim();
            document.getElementById('selectedResidentDisplay').classList.remove('hidden');
            document.getElementById('residentSearchInput').value = residentName.trim();
            document.getElementById('searchResults').classList.add('hidden');
        }

        function clearSelectedResident() {
            document.getElementById('selectedResidentId').value = '';
            document.getElementById('selectedResidentName').textContent = '';
            document.getElementById('selectedResidentDisplay').classList.add('hidden');
            document.getElementById('residentSearchInput').value = '';
            document.getElementById('searchResults').classList.add('hidden');
        }

        function calculateAge(birthdate) {
            const today = new Date();
            const birth = new Date(birthdate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        }

        // Hide search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#residentSearchSection')) {
                document.getElementById('searchResults').classList.add('hidden');
            }
        });

        // Handle resident option toggle - removed since we no longer have radio buttons
        // Register new resident button - redirect to individuals page
        document.addEventListener('click', function(e) {
            if (e.target.id === 'registerNewResidentBtn') {
                e.preventDefault();
                // Redirect to individuals page for registration
                window.location.href = 'individuals.php';
            }
        });

        function loadAdditionalFields(type) {
            const additionalFields = document.getElementById('additionalFields');
            additionalFields.innerHTML = '';

            switch(type) {
                case 'first_time_job_seeker':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="age">
                                <i class="fas fa-calendar mr-2"></i>Age
                            </label>
                            <input type="number" class="form-input" id="age" name="age" placeholder="Enter age" min="15" max="30">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="educationalAttainment">
                                <i class="fas fa-graduation-cap mr-2"></i>Educational Attainment
                            </label>
                            <select class="form-input form-select" id="educationalAttainment" name="educational_attainment">
                                <option value="">Select educational attainment</option>
                                <option value="Elementary Graduate">Elementary Graduate</option>
                                <option value="High School Graduate">High School Graduate</option>
                                <option value="Senior High School Graduate">Senior High School Graduate</option>
                                <option value="College Graduate">College Graduate</option>
                                <option value="Vocational/Technical Graduate">Vocational/Technical Graduate</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                    `;
                    break;
                case 'indigency':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="familyIncome">
                                <i class="fas fa-money-bill-wave mr-2"></i>Monthly Family Income
                            </label>
                            <select class="form-input form-select" id="familyIncome" name="family_income">
                                <option value="">Select income range</option>
                                <option value="Below ₱5,000">Below ₱5,000</option>
                                <option value="₱5,000 - ₱10,000">₱5,000 - ₱10,000</option>
                                <option value="₱10,001 - ₱15,000">₱10,001 - ₱15,000</option>
                                <option value="₱15,001 - ₱20,000">₱15,001 - ₱20,000</option>
                                <option value="Above ₱20,000">Above ₱20,000</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="familyMembers">
                                <i class="fas fa-users mr-2"></i>Number of Family Members
                            </label>
                            <input type="number" class="form-input" id="familyMembers" name="family_members" placeholder="Enter number of family members" min="1">
                        </div>
                    `;
                    break;
                case 'residency':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="yearsOfResidency">
                                <i class="fas fa-clock mr-2"></i>Years of Residency
                            </label>
                            <input type="number" class="form-input" id="yearsOfResidency" name="years_of_residency" placeholder="Enter number of years as resident" min="0">
                        </div>
                    `;
                    break;
                case 'barangay_id':
                    additionalFields.innerHTML = `
                        <div class="form-group">
                            <label class="form-label" for="emergencyContact">
                                <i class="fas fa-phone mr-2"></i>Emergency Contact Name
                            </label>
                            <input type="text" class="form-input" id="emergencyContact" name="emergency_contact" placeholder="Enter emergency contact name">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emergencyContactNumber">
                                <i class="fas fa-phone-alt mr-2"></i>Emergency Contact Number
                            </label>
                            <input type="tel" class="form-input" id="emergencyContactNumber" name="emergency_contact_number" placeholder="Enter emergency contact number">
                        </div>
                    `;
                    break;
                // For clearance, no additional fields needed
                case 'clearance':
                    additionalFields.innerHTML = ``;
                    break;
                default:
                    // No additional fields for other certificates
                    break;
            }
        }

        // Form submission
        document.getElementById('certificateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate purpose field (required for all certificates)
            const purpose = document.getElementById('purpose').value.trim();
            if (!purpose) {
                showNotification('Please enter the purpose of the certificate.', 'error');
                document.getElementById('purpose').focus();
                return;
            }
            
            // Validate that a resident is selected
            const residentId = document.getElementById('selectedResidentId').value;
            if (!residentId) {
                showNotification('Please search and select a resident.', 'error');
                document.getElementById('residentSearchInput').focus();
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
            submitBtn.disabled = true;
            
            // Generate certificate for selected resident
            const formData = new FormData();
            
            // Include the relevant fields
            formData.append('certificate_type', document.getElementById('certificateType').value);
            formData.append('resident_id', residentId);
            formData.append('purpose', purpose);
            
            // Add any additional fields that might be present
            const additionalFields = document.getElementById('additionalFields');
            const additionalInputs = additionalFields.querySelectorAll('input, select, textarea');
            additionalInputs.forEach(input => {
                if (input.name && input.value) {
                    if (input.type === 'checkbox') {
                        if (input.checked) {
                            formData.append(input.name, input.value);
                        }
                    } else {
                        formData.append(input.name, input.value);
                    }
                }
            });
            
            fetch('generate_certificate.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Certificate generated successfully!', 'success');
                    closeCertificateModal();
                    
                    // Download the certificate
                    if (data.download_url) {
                        window.open(data.download_url, '_blank');
                    }
                } else {
                    showNotification('Error generating certificate: ' + (data.error || data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while generating the certificate.', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

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
    </script>
</body>
</html>
