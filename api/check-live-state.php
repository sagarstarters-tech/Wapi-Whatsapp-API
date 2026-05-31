<?php
/**
 * WAPI SaaS - Live Webhook & Database Session Diagnostics
 * Usage: Access via browser: /api/check-live-state.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

header('Content-Type: text/plain; charset=utf-8');

$db = Database::getInstance();

// Manual cleanup option
if (isset($_GET['clear_expired']) && $_GET['clear_expired'] == '1') {
    try {
        $stmt = $db->query(
            "UPDATE chatbot_sessions SET state = 'finished', updated_at = NOW() WHERE state = 'active' AND updated_at < DATE_SUB(NOW(), INTERVAL 4 HOUR)"
        );
        $count = $stmt->rowCount();
        echo "=== EXPIRED SESSIONS CLEARED: $count SESSIONS UPDATED TO FINISHED ===\n\n";
    } catch (Exception $e) {
        echo "=== CLEAR ERROR: " . $e->getMessage() . " ===\n\n";
    }
}

echo "=== ACTIVE CHATBOT SESSIONS ===\n";
try {
    $sessions = $db->fetchAll("SELECT * FROM chatbot_sessions ORDER BY updated_at DESC LIMIT 10");
    echo json_encode($sessions, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Sessions Error: " . $e->getMessage() . "\n";
}

echo "\n=== LATEST WEBHOOK ROOT LOG LINES FOR 918573934013 ===\n";
$rootPath = __DIR__ . '/../logs/webhook_root.log';
if (file_exists($rootPath)) {
    $file = fopen($rootPath, 'r');
    $matchedLines = [];
    while (($line = fgets($file)) !== false) {
        if (strpos($line, '918573934013') !== false || strpos($line, 'Message from') !== false || strpos($line, 'Continuing session') !== false || strpos($line, 'Auto-starting') !== false) {
            $matchedLines[] = $line;
            if (count($matchedLines) > 50) {
                array_shift($matchedLines);
            }
        }
    }
    fclose($file);
    echo implode("", $matchedLines);
} else {
    echo "No root webhook log found.\n";
}

echo "\n=== LATEST MESSAGES FOR 918573934013 ===\n";
try {
    $messages = $db->fetchAll("SELECT id, to_number, type, content, status, direction, created_at FROM messages WHERE to_number = '918573934013' OR to_number = '+918573934013' ORDER BY id DESC LIMIT 20");
    echo json_encode($messages, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "Messages Error: " . $e->getMessage() . "\n";
}

exit;
