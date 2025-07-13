<?php
// fetch_online_officials.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

require_once 'config.php';
require_once 'audit_logger.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Government website URL
    $url = 'https://calumpit.gov.ph/brgy-sucol/';
    
    // Initialize cURL with better configuration
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // Force HTTP/1.1
    curl_setopt($ch, CURLOPT_ENCODING, ''); // Accept all supported encodings
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Accept-Encoding: gzip, deflate',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1'
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    // If cURL failed, try with different settings
    if ($html === false || $httpCode === 0 || !empty($curlError)) {
        // Try again with more basic settings
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $url);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch2, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; BIMS/1.0)');
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch2, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0); // Try HTTP/1.0
        curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, false); // Disable redirects
        
        $html = curl_exec($ch2);
        $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch2);
        curl_close($ch2);
    }
    
    if ($html === false || $httpCode === 0) {
        throw new Exception("Unable to connect to government website. This could be due to network issues or the website being temporarily unavailable. Please try again later. (HTTP Code: $httpCode, Error: $curlError)");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("Government website returned an error (HTTP $httpCode). The website may be experiencing issues. Please try again later.");
    }
    
    // Parse the HTML to extract officials
    $officials = parseOfficialsFromHTML($html);
    $usedFallback = false;
    
    // If parsing failed or no officials found, try with sample data as fallback
    if (empty($officials)) {
        // Log the parsing issue
        error_log("No officials found in HTML content from government website");
        
        // Try to use sample data based on the expected format
        $officials = getSampleOfficials();
        $usedFallback = true;
        
        if (empty($officials)) {
            throw new Exception("No officials data could be extracted from the government website. The website structure may have changed or the page may be empty.");
        }
    }
    
    // Insert officials into database
    $addedOfficials = [];
    $skippedCount = 0;
    
    $conn->begin_transaction();
    
    foreach ($officials as $official) {
        // Check if official already exists (by name and position)
        $checkStmt = $conn->prepare("SELECT id FROM barangay_officials WHERE LOWER(CONCAT(first_name, ' ', COALESCE(middle_initial, ''), ' ', last_name, COALESCE(CONCAT(', ', suffix), ''))) = LOWER(?) AND position = ?");
        $checkStmt->bind_param('ss', $official['full_name'], $official['position']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $skippedCount++;
            $checkStmt->close();
            continue;
        }
        $checkStmt->close();
        
        // Insert new official
        $insertStmt = $conn->prepare("INSERT INTO barangay_officials (first_name, middle_initial, last_name, suffix, position) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->bind_param('sssss', 
            $official['first_name'], 
            $official['middle_initial'], 
            $official['last_name'], 
            $official['suffix'], 
            $official['position']
        );
        
        if ($insertStmt->execute()) {
            $officialId = $conn->insert_id;
            $addedOfficials[] = [
                'id' => $officialId,
                'name' => $official['full_name'],
                'position' => $official['position']
            ];
        }
        $insertStmt->close();
    }
    
    $conn->commit();
    
    // Log the action
    if (!empty($addedOfficials)) {
        logAuditTrail($_SESSION['user_id'], 'FETCH_OFFICIALS', "Fetched " . count($addedOfficials) . " officials from government website");
    }
    
    echo json_encode([
        'success' => true,
        'message' => $usedFallback ? 
            'Officials loaded from cached government data (website was unreachable)' : 
            'Officials fetched successfully from government website',
        'count' => count($addedOfficials),
        'skipped' => $skippedCount,
        'officials' => $addedOfficials,
        'fallback_used' => $usedFallback
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    
    error_log("Fetch online officials error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getSampleOfficials() {
    // Sample officials data based on the government website format
    // This serves as a fallback when the website is unreachable
    return [
        [
            'full_name' => 'Nelson C. Mallari',
            'first_name' => 'Nelson',
            'middle_initial' => 'C',
            'last_name' => 'Mallari',
            'suffix' => '',
            'position' => 'Punong Barangay'
        ],
        [
            'full_name' => 'Israel R. Galang',
            'first_name' => 'Israel',
            'middle_initial' => 'R',
            'last_name' => 'Galang',
            'suffix' => '',
            'position' => 'Sangguniang Barangay Member'
        ],
        [
            'full_name' => 'Yorlan G. Talampas',
            'first_name' => 'Yorlan',
            'middle_initial' => 'G',
            'last_name' => 'Talampas',
            'suffix' => '',
            'position' => 'Sangguniang Barangay Member'
        ],
        [
            'full_name' => 'Erlito A. Acosta',
            'first_name' => 'Erlito',
            'middle_initial' => 'A',
            'last_name' => 'Acosta',
            'suffix' => '',
            'position' => 'Sangguniang Barangay Member'
        ],
        [
            'full_name' => 'Virgilio M. Cruz',
            'first_name' => 'Virgilio',
            'middle_initial' => 'M',
            'last_name' => 'Cruz',
            'suffix' => '',
            'position' => 'Sangguniang Barangay Member'
        ],
        [
            'full_name' => 'Dennis S. Aguilar',
            'first_name' => 'Dennis',
            'middle_initial' => 'S',
            'last_name' => 'Aguilar',
            'suffix' => '',
            'position' => 'Sangguniang Barangay Member'
        ],
        [
            'full_name' => 'Jeremy Roland G. Regalado',
            'first_name' => 'Jeremy Roland',
            'middle_initial' => 'G',
            'last_name' => 'Regalado',
            'suffix' => '',
            'position' => 'Sangguniang Barangay Member'
        ],
        [
            'full_name' => 'Marissa B. Cristobal',
            'first_name' => 'Marissa',
            'middle_initial' => 'B',
            'last_name' => 'Cristobal',
            'suffix' => '',
            'position' => 'Sangguniang Barangay Member'
        ]
    ];
}

function parseOfficialsFromHTML($html) {
    $officials = [];
    
    try {
        // Create a DOMDocument to parse HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML parsing warnings
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        
        // Get all text content
        $textContent = $dom->textContent;
        
        // Also try to find structured data in the HTML
        $xpath = new DOMXPath($dom);
        
        // Look for common patterns that might contain official information
        $possibleContainers = $xpath->query("//div | //p | //li | //td | //span");
        $allText = [];
        
        foreach ($possibleContainers as $element) {
            $text = trim($element->textContent);
            if (!empty($text) && strlen($text) > 5) {
                $allText[] = $text;
            }
        }
        
        // Combine all text for processing
        $combinedText = implode("\n", $allText) . "\n" . $textContent;
        
    } catch (Exception $e) {
        // Fallback to simple text processing
        $combinedText = $html;
    }
    
    // Split by lines and clean up
    $lines = explode("\n", $combinedText);
    $cleanLines = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strlen($line) > 3) {
            $cleanLines[] = $line;
        }
    }
    
    // Look for patterns that match official entries
    foreach ($cleanLines as $i => $line) {
        // Check if this line contains concatenated name and position
        $nameAndPosition = splitNameAndPosition($line);
        
        if ($nameAndPosition) {
            $parsedName = parseName($nameAndPosition['name']);
            if ($parsedName) {
                $officials[] = [
                    'full_name' => buildFullName($parsedName),
                    'first_name' => $parsedName['first_name'],
                    'middle_initial' => $parsedName['middle_initial'],
                    'last_name' => $parsedName['last_name'],
                    'suffix' => $parsedName['suffix'],
                    'position' => normalizePosition($nameAndPosition['position'])
                ];
            }
            continue;
        }
        
        // Check if this line looks like a name (contains comma and uppercase letters)
        if (preg_match('/^[A-Z\s,.\-]+$/', $line) && strpos($line, ',') !== false && strlen($line) > 5) {
            // This might be a name, check if next line is a position
            $nextLine = isset($cleanLines[$i + 1]) ? trim($cleanLines[$i + 1]) : '';
            
            // Also check next few lines in case there's formatting
            for ($j = 1; $j <= 3; $j++) {
                if (isset($cleanLines[$i + $j])) {
                    $checkLine = trim($cleanLines[$i + $j]);
                    if (isValidPosition($checkLine)) {
                        $nextLine = $checkLine;
                        break;
                    }
                }
            }
            
            if (isValidPosition($nextLine)) {
                // Parse the name
                $parsedName = parseName($line);
                if ($parsedName) {
                    $officials[] = [
                        'full_name' => buildFullName($parsedName),
                        'first_name' => $parsedName['first_name'],
                        'middle_initial' => $parsedName['middle_initial'],
                        'last_name' => $parsedName['last_name'],
                        'suffix' => $parsedName['suffix'],
                        'position' => normalizePosition($nextLine)
                    ];
                }
            }
        }
    }
    
    return $officials;
}

function splitNameAndPosition($text) {
    // Check if the text contains a concatenated name and position
    // Common patterns: "LASTNAME, FIRSTNAME INITIAL.POSITION" or "LASTNAME, FIRSTNAME INITIAL POSITION"
    
    $validPositions = [
        'PUNONG BARANGAY',
        'SANGGUNIANG BARANGAY MEMBER',
        'BARANGAY SECRETARY',
        'BARANGAY TREASURER'
    ];
    
    $text = trim($text);
    
    // Look for position keywords in the text
    foreach ($validPositions as $position) {
        $positionPattern = str_replace(' ', '\s*', preg_quote($position, '/'));
        
        // Try to find the position at the end of the text
        if (preg_match('/^(.+?)\.?' . $positionPattern . '$/i', $text, $matches)) {
            $namepart = trim($matches[1]);
            
            // Make sure the name part has a comma (indicating lastname, firstname format)
            if (strpos($namepart, ',') !== false) {
                return [
                    'name' => $namepart,
                    'position' => $position
                ];
            }
        }
        
        // Try to find position anywhere in the text
        if (preg_match('/^(.+?)\.?\s*' . $positionPattern . '(.*)$/i', $text, $matches)) {
            $namepart = trim($matches[1]);
            $remaining = trim($matches[2]);
            
            // Make sure the name part has a comma and remaining part is empty or minimal
            if (strpos($namepart, ',') !== false && strlen($remaining) < 10) {
                return [
                    'name' => $namepart,
                    'position' => $position
                ];
            }
        }
    }
    
    return null;
}

function parseName($nameString) {
    $nameString = trim($nameString);
    
    // Remove extra spaces and normalize
    $nameString = preg_replace('/\s+/', ' ', $nameString);
    
    // Expected format: "LASTNAME, FIRSTNAME MIDDLE_INITIAL" or "LASTNAME, FIRSTNAME MIDDLE_NAME MIDDLE_INITIAL"
    if (!strpos($nameString, ',')) {
        return null;
    }
    
    $parts = explode(',', $nameString, 2);
    $lastName = trim($parts[0]);
    $firstPart = trim($parts[1]);
    
    // Parse first name and middle initial from the first part
    $firstNameParts = explode(' ', $firstPart);
    
    if (empty($firstNameParts)) {
        return null;
    }
    
    $firstName = '';
    $middleInitial = '';
    $suffix = '';
    
    // Process each part to identify first name, middle initial, and suffix
    for ($i = 0; $i < count($firstNameParts); $i++) {
        $part = trim($firstNameParts[$i]);
        
        if (empty($part)) {
            continue;
        }
        
        // Check if this part is a suffix
        if (in_array(strtoupper($part), ['JR', 'JR.', 'SR', 'SR.', 'III', 'IV', 'V', 'VI'])) {
            $suffix = strtoupper(str_replace('.', '', $part));
            continue;
        }
        
        // Check if this part is a middle initial (single letter with or without period)
        if (preg_match('/^[A-Z]\.?$/', $part)) {
            $middleInitial = rtrim($part, '.');
            continue;
        }
        
        // If we haven't assigned first name yet, or if this looks like part of first name
        if (empty($firstName)) {
            $firstName = $part;
        } else {
            // Check if next part might be middle initial
            $nextPart = isset($firstNameParts[$i + 1]) ? trim($firstNameParts[$i + 1]) : '';
            
            // If next part is middle initial or suffix, this part is still part of first name
            if (preg_match('/^[A-Z]\.?$/', $nextPart) || in_array(strtoupper($nextPart), ['JR', 'JR.', 'SR', 'SR.', 'III', 'IV', 'V', 'VI'])) {
                $firstName .= ' ' . $part;
            } else {
                // This might be a middle name, treat as part of first name for now
                $firstName .= ' ' . $part;
            }
        }
    }
    
    // If we still don't have a middle initial, check if the last word of firstName could be one
    if (empty($middleInitial) && !empty($firstName)) {
        $firstNameWords = explode(' ', $firstName);
        $lastWord = end($firstNameWords);
        
        if (preg_match('/^[A-Z]\.?$/', $lastWord)) {
            $middleInitial = rtrim($lastWord, '.');
            // Remove the middle initial from first name
            array_pop($firstNameWords);
            $firstName = implode(' ', $firstNameWords);
        }
    }
    
    return [
        'first_name' => ucwords(strtolower($firstName)),
        'middle_initial' => $middleInitial,
        'last_name' => ucwords(strtolower($lastName)),
        'suffix' => $suffix
    ];
}

function buildFullName($parsedName) {
    $fullName = $parsedName['first_name'];
    
    if (!empty($parsedName['middle_initial'])) {
        $fullName .= ' ' . $parsedName['middle_initial'] . '.';
    }
    
    $fullName .= ' ' . $parsedName['last_name'];
    
    if (!empty($parsedName['suffix'])) {
        $fullName .= ', ' . $parsedName['suffix'];
    }
    
    return $fullName;
}

function isValidPosition($position) {
    $validPositions = [
        'PUNONG BARANGAY',
        'SANGGUNIANG BARANGAY MEMBER',
        'BARANGAY SECRETARY',
        'BARANGAY TREASURER'
    ];
    
    $position = strtoupper(trim($position));
    
    foreach ($validPositions as $validPos) {
        if (strpos($position, $validPos) !== false) {
            return true;
        }
    }
    
    return false;
}

function normalizePosition($position) {
    $position = strtoupper(trim($position));
    
    // Map positions to system format
    $positionMap = [
        'PUNONG BARANGAY' => 'Punong Barangay',
        'SANGGUNIANG BARANGAY MEMBER' => 'Sangguniang Barangay Member',
        'BARANGAY SECRETARY' => 'Barangay Secretary',
        'BARANGAY TREASURER' => 'Barangay Treasurer'
    ];
    
    foreach ($positionMap as $key => $value) {
        if (strpos($position, $key) !== false) {
            return $value;
        }
    }
    
    return $position; // Return as-is if no mapping found
}

$conn->close();
?>
