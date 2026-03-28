<?php
require_once __DIR__ . '/config/config.php';
$db = Database::getInstance();

try {
    echo "Updating schema for chatbot_sessions...\n";
    $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN `state` VARCHAR(50) NOT NULL DEFAULT 'start' AFTER `phone`");
    $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN `current_node_id` VARCHAR(50) AFTER `state`");
    echo "Done.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}
