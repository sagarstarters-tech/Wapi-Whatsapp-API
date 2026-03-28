<?php
require_once __DIR__ . '/config/config.php';
$db = Database::getInstance();

try {
    echo "Testing webhook_logs insert with null user_id...\n";
    $db->insert('webhook_logs', [
        'user_id' => null,
        'event_type' => 'incoming',
        'payload' => '{}',
        'status' => 'received'
    ]);
    echo "Success.\n";
} catch (Exception $e) {
    echo "CRASHED: " . $e->getMessage() . "\n";
}
