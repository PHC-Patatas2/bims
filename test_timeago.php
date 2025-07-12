<?php
require_once 'config.php';

// Helper: human readable time ago (same as in dashboard.php)
function timeAgo($datetime) {
    if (empty($datetime)) {
        return 'Unknown time';
    }
    
    // Try to create a DateTime object for better handling
    try {
        $date = new DateTime($datetime);
        $now = new DateTime();
        $diff = $now->getTimestamp() - $date->getTimestamp();
        
        // If the difference is negative (future date), it might be a timezone issue
        // Let's use absolute value for reasonable differences (up to 24 hours)
        if ($diff < 0 && abs($diff) <= 86400) { // 24 hours
            $diff = abs($diff);
        } else if ($diff < 0) {
            // For larger negative differences, show the formatted date
            return $date->format('M d, Y h:i A');
        }
        
        if ($diff < 60) {
            return $diff . ' sec ago';
        }
        
        $mins = floor($diff / 60);
        if ($mins < 60) {
            return $mins . ' min ago';
        }
        
        $hours = floor($mins / 60);
        if ($hours < 24) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }
        
        $days = floor($hours / 24);
        if ($days < 7) {
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
        
        // For older dates, show formatted date
        return $date->format('M d, Y h:i A');
        
    } catch (Exception $e) {
        // Fallback to original method with error handling
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return 'Unknown time';
        }
        
        $diff = time() - $timestamp;
        
        // Handle timezone issues in fallback too
        if ($diff < 0 && abs($diff) <= 86400) {
            $diff = abs($diff);
        } else if ($diff < 0) {
            return date('M d, Y h:i A', $timestamp);
        }
        
        if ($diff < 60) return $diff . ' sec ago';
        $mins = floor($diff / 60);
        if ($mins < 60) return $mins . ' min ago';
        $hours = floor($mins / 60);
        if ($hours < 24) return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        $days = floor($hours / 24);
        if ($days < 7) return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        return date('M d, Y h:i A', $timestamp);
    }
}

echo "<h2>Testing timeAgo() function</h2>";
echo "<p>Current server time: " . date('Y-m-d H:i:s') . "</p>";

// Get recent activities from the database
$stmt = $pdo->prepare("SELECT action, target, created_at FROM activity_logs ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$activities = $stmt->fetchAll();

if ($activities) {
    echo "<h3>Recent Activities:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Action</th><th>Target</th><th>Database Time</th><th>timeAgo() Result</th><th>Raw Difference (sec)</th></tr>";
    
    foreach ($activities as $activity) {
        $timestamp = strtotime($activity['created_at']);
        $diff = time() - $timestamp;
        $timeAgoResult = timeAgo($activity['created_at']);
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($activity['action']) . "</td>";
        echo "<td>" . htmlspecialchars($activity['target']) . "</td>";
        echo "<td>" . htmlspecialchars($activity['created_at']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($timeAgoResult) . "</strong></td>";
        echo "<td>" . $diff . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No activities found.</p>";
}

echo "<h3>Test Cases:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Test Case</th><th>Input</th><th>Result</th></tr>";

// Test current time
$now = date('Y-m-d H:i:s');
echo "<tr><td>Current time</td><td>$now</td><td>" . timeAgo($now) . "</td></tr>";

// Test 5 minutes ago
$fiveMinAgo = date('Y-m-d H:i:s', time() - 300);
echo "<tr><td>5 minutes ago</td><td>$fiveMinAgo</td><td>" . timeAgo($fiveMinAgo) . "</td></tr>";

// Test 2 hours ago
$twoHoursAgo = date('Y-m-d H:i:s', time() - 7200);
echo "<tr><td>2 hours ago</td><td>$twoHoursAgo</td><td>" . timeAgo($twoHoursAgo) . "</td></tr>";

// Test simulated future time (timezone mismatch)
$futureTime = date('Y-m-d H:i:s', time() + 300); // 5 minutes in future
echo "<tr><td>5 minutes in future (simulated timezone issue)</td><td>$futureTime</td><td>" . timeAgo($futureTime) . "</td></tr>";

// Test far future time
$farFuture = date('Y-m-d H:i:s', time() + 86400 + 3600); // More than 24 hours in future
echo "<tr><td>25 hours in future</td><td>$farFuture</td><td>" . timeAgo($farFuture) . "</td></tr>";

echo "</table>";
?>
