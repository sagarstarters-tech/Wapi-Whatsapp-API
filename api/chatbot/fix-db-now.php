<?php
/**
 * DB Fix Script - PUBLIC (Temporary)
 */
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance();

try {
    echo "Running DB Fix...<br>";
    $db->query("ALTER TABLE `chatbot_flows` ADD COLUMN IF NOT EXISTS `flow_json` LONGTEXT AFTER `name` ");
    $db->query("ALTER TABLE `chatbot_flows` MODIFY COLUMN `response_content` TEXT NULL");
    
    // Sessions
    $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN IF NOT EXISTS `state` VARCHAR(50) NOT NULL DEFAULT 'start' AFTER `phone`");
    $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN IF NOT EXISTS `flow_id` INT AFTER `state` ");
    $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN IF NOT EXISTS `current_node_id` VARCHAR(50) AFTER `flow_id` ");

    echo "<b>Success! Database fixed.</b>";
} catch (Exception $e) {
    echo "<b>Error:</b> " . $e->getMessage();
}
unlink(__FILE__); // Self-delete for security after running
