-- SQL Updates for Chatbot Integration
CREATE TABLE IF NOT EXISTS `chatbot_flows` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `flow_json` LONGTEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update Chatbot Sessions to track flows and nodes
ALTER TABLE `chatbot_sessions` 
ADD COLUMN IF NOT EXISTS `state` VARCHAR(50) NOT NULL DEFAULT 'start' AFTER `phone`,
ADD COLUMN IF NOT EXISTS `flow_id` INT AFTER `state`,
ADD COLUMN IF NOT EXISTS `current_node_id` VARCHAR(50) AFTER `flow_id`;
