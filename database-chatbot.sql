-- ============================================
-- WhatsApp API SaaS Portal - Chatbot Builder
-- Database Migration Script
-- ============================================

-- 1. Chatbot Flows Table
CREATE TABLE IF NOT EXISTS `chatbot_flows` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `trigger_keywords` JSON DEFAULT NULL, -- JSON array of keywords
    `match_type` ENUM('exact', 'contains', 'starts_with', 'regex') DEFAULT 'contains',
    `is_active` TINYINT(1) DEFAULT 0,
    `is_published` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_active` (`user_id`, `is_active`)
) ENGINE=InnoDB;

-- 2. Chatbot Nodes Table
CREATE TABLE IF NOT EXISTS `chatbot_nodes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `flow_id` INT NOT NULL,
    `node_uuid` VARCHAR(64) NOT NULL,
    `type` VARCHAR(50) NOT NULL, -- text, image, button, list, condition, delay, etc.
    `data` JSON DEFAULT NULL, -- Node-specific data (message, media_url, timeout, logic)
    `x` INT DEFAULT 0,
    `y` INT DEFAULT 0,
    FOREIGN KEY (`flow_id`) REFERENCES `chatbot_flows`(`id`) ON DELETE CASCADE,
    INDEX `idx_flow_node` (`flow_id`, `node_uuid`)
) ENGINE=InnoDB;

-- 3. Chatbot Connections Table
CREATE TABLE IF NOT EXISTS `chatbot_connections` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `flow_id` INT NOT NULL,
    `from_node_uuid` VARCHAR(64) NOT NULL,
    `from_port` VARCHAR(50) DEFAULT 'out',
    `to_node_uuid` VARCHAR(64) NOT NULL,
    FOREIGN KEY (`flow_id`) REFERENCES `chatbot_flows`(`id`) ON DELETE CASCADE,
    INDEX `idx_flow_conn` (`flow_id`)
) ENGINE=InnoDB;

-- 4. Chatbot Sessions (Track user state)
CREATE TABLE IF NOT EXISTS `chatbot_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `flow_id` INT DEFAULT NULL,
    `current_node_uuid` VARCHAR(64) DEFAULT NULL,
    `session_data` JSON DEFAULT NULL,
    `last_interaction` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`flow_id`) REFERENCES `chatbot_flows`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `uk_user_phone` (`user_id`, `phone`)
) ENGINE=InnoDB;

-- 5. Re-enable Chatbot Support in Plans
ALTER TABLE `plans` ADD COLUMN IF NOT EXISTS `chatbot_enabled` TINYINT(1) DEFAULT 0 AFTER `templates_limit`;

-- 6. Insert Default Feature
INSERT IGNORE INTO `features` (`icon`, `title`, `description`, `sort_order`) VALUES
('bi-robot', 'SaaS Chatbot Builder', 'Create advanced conversational flows with our drag-and-drop node-based editor.', 2);

-- 7. Update Default Plans to include Chatbot (example for Business Plan)
UPDATE `plans` SET `chatbot_enabled` = 1 WHERE `slug` = 'business' OR `slug` = 'professional';
