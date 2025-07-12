<?php
require_once 'config.php';

echo "PHP timezone: " . date_default_timezone_get() . PHP_EOL;
echo "PHP current time: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "PHP timestamp: " . time() . PHP_EOL;

$result = $pdo->query('SELECT NOW() as db_time, @@system_time_zone as db_timezone, UNIX_TIMESTAMP(NOW()) as db_timestamp');
$row = $result->fetch();
echo "Database timezone: " . $row['db_timezone'] . PHP_EOL;
echo "Database current time: " . $row['db_time'] . PHP_EOL;
echo "Database timestamp: " . $row['db_timestamp'] . PHP_EOL;

echo "Time difference: " . (time() - $row['db_timestamp']) . " seconds" . PHP_EOL;

// Check recent audit entries with their timestamps
echo PHP_EOL . "Recent audit entries:" . PHP_EOL;
$logs = $pdo->query('SELECT action, timestamp, UNIX_TIMESTAMP(timestamp) as unix_ts FROM audit_trail ORDER BY timestamp DESC LIMIT 3');
foreach ($logs as $log) {
    echo "- " . $log['action'] . " at " . $log['timestamp'] . " (unix: " . $log['unix_ts'] . ")" . PHP_EOL;
}
?>
