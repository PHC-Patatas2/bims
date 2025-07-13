<?php
// test_name_parsing.php - Test the name parsing logic

// Include the parsing functions from fetch_online_officials.php
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

// Test cases
$testNames = [
    'MALLARI, NELSON C.',
    'GALANG, ISRAEL R.',
    'REGALADO, JEREMY ROLAND G.',
    'CRISTOBAL, MARISSA B.',
    'SMITH, JOHN MICHAEL A. JR.',
    'DELA CRUZ, MARIA ELENA S.',
    'SANTOS, JOSE ANTONIO III'
];

echo "<h2>Name Parsing Test Results</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Original</th><th>First Name</th><th>Middle Initial</th><th>Last Name</th><th>Suffix</th><th>Rebuilt Full Name</th></tr>\n";

foreach ($testNames as $testName) {
    $parsed = parseName($testName);
    
    if ($parsed) {
        $rebuilt = buildFullName($parsed);
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($testName) . "</td>\n";
        echo "<td>" . htmlspecialchars($parsed['first_name']) . "</td>\n";
        echo "<td>" . htmlspecialchars($parsed['middle_initial']) . "</td>\n";
        echo "<td>" . htmlspecialchars($parsed['last_name']) . "</td>\n";
        echo "<td>" . htmlspecialchars($parsed['suffix']) . "</td>\n";
        echo "<td>" . htmlspecialchars($rebuilt) . "</td>\n";
        echo "</tr>\n";
    } else {
        echo "<tr><td>" . htmlspecialchars($testName) . "</td><td colspan='5'>PARSING FAILED</td></tr>\n";
    }
}

echo "</table>\n";
?>
