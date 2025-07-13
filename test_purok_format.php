<?php
// Test purok formatting logic
$test_puroks = [
    'Purok 1 (Pulongtingga)',
    'Purok 2 (Santol)',
    'Purok 3',
    'purok 4 (some area)',
    'PUROK 5 (ANOTHER AREA)',
    '1 (Pulongtingga)',
    '2',
    'Sitio Pulongtingga',
    'Zone 1'
];

echo "Testing Purok Formatting:\n";
echo "========================\n";

foreach ($test_puroks as $purokRaw) {
    // Format purok name (same logic as in export script)
    $purokName = '';
    if ($purokRaw) {
        // Extract only the purok number/name and format as "Purok X"
        if (preg_match('/(\d+)/', $purokRaw, $matches)) {
            $purokName = 'Purok ' . $matches[1];
        } else if (stripos($purokRaw, 'purok') !== false) {
            // If it already contains "purok" but no number found
            $purokName = ucwords(strtolower($purokRaw));
        } else {
            // Fallback - assume it's a purok number or name
            $purokName = 'Purok ' . $purokRaw;
        }
    }
    
    echo sprintf("%-30s -> %s\n", "'" . $purokRaw . "'", $purokName);
}
?>
