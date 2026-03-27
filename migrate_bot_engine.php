<?php
/**
 * BotBee Style Chatbot Schema Migration
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';

$db = Database::getInstance();

$queries = [
    // 1. User Sessions (Track current node and variables for each customer)
    "CREATE TABLE IF NOT EXISTS `chatbot_sessions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `phone` varchar(20) NOT NULL,
        `flow_id` int(11) DEFAULT NULL,
        `current_node` varchar(100) DEFAULT NULL,
        `variables` longtext DEFAULT NULL, 
        `last_activity` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `created_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `phone_user` (`phone`, `user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // 2. User Inputs (Save captured data like lead generations)
    "CREATE TABLE IF NOT EXISTS `chatbot_leads` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `phone` varchar(20) NOT NULL,
        `variable_name` varchar(50) NOT NULL,
        `variable_value` text NOT NULL,
        `created_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // 3. Flow Logs (For Analytics: Drop-offs, performance)
    "CREATE TABLE IF NOT EXISTS `chatbot_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `flow_id` int(11) NOT NULL,
        `node_id` varchar(100) NOT NULL,
        `phone` varchar(20) NOT NULL,
        `action` enum('visit', 'click', 'input') DEFAULT 'visit',
        `created_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `analytics_idx` (`flow_id`, `node_id`, `action`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    // 4. Node Options (Extended for List Messages & CTA)
    "CREATE TABLE IF NOT EXISTS `chatbot_node_options` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `flow_id` int(11) NOT NULL,
        `node_id` varchar(100) NOT NULL,
        `option_type` varchar(20) NOT NULL, -- reply, url, call, list
        `label` varchar(255) NOT NULL,
        `value` text DEFAULT NULL,
        `next_node` varchar(100) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
];

echo "🚀 Starting Enterprise Bot Engine Migration...\n";

foreach ($queries as $sql) {
    try {
        $db->query($sql);
        echo "✅ Table created/updated.\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "🎯 Migration Completed Successfully.\n";
