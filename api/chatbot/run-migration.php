<?php
/**
 * DB Migration Script - Visual Chatbot V2
 * Run this on the server to update the database schema.
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';

// PUBLIC ACCESS FOR EMERGENCY FIX
/*
if (!Auth::isLoggedIn()) {
    die("Unauthorized access. Please login to your dashboard first.");
}
*/

$db = Database::getInstance();

try {
    echo "Starting Migration...<br>";

    // 1. Add flow_json to chatbot_flows
    echo "Updating chatbot_flows table...<br>";
    $db->query("ALTER TABLE `chatbot_flows` ADD COLUMN IF NOT EXISTS `flow_json` LONGTEXT AFTER `name` ");
    $db->query("ALTER TABLE `chatbot_flows` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1 AFTER `flow_json` ");
    $db->query("ALTER TABLE `chatbot_flows` MODIFY COLUMN `response_content` TEXT NULL");

    // 2. Update chatbot_sessions
    echo "Updating chatbot_sessions table...<br>";
    // Since IF NOT EXISTS for columns is MySQL 8.0.12+, we'll do it safely with a check
    $columns = $db->fetchAll("DESCRIBE `chatbot_sessions` ");
    $colNames = array_column($columns, 'Field');

    if (!in_array('flow_id', $colNames)) {
        $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN `flow_id` INT AFTER `phone` ");
    }
    if (!in_array('current_node_id', $colNames)) {
        $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN `current_node_id` VARCHAR(50) AFTER `user_id` ");
    }

    echo "<br><b>Migration completed successfully!</b><br>";
    echo "You can now go back and save your flows.";

} catch (Exception $e) {
    echo "<br><b style='color:red'>Migration failed:</b> " . $e->getMessage();
}
