-- ============================================
-- AI ChatBot Builder Module - Database Schema
-- Migration for WAPI SaaS Platform
-- Version: 1.0.0
-- ============================================

USE `wapi_saas`;

-- ============================================
-- 1. AI Bots Table (Core)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_bots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'inactive',
    `whatsapp_account_id` INT DEFAULT NULL,

    -- AI Model Configuration
    `ai_model` ENUM('gpt-4o', 'gpt-4.1', 'gemini', 'claude', 'custom') DEFAULT 'gpt-4o',
    `custom_api_endpoint` VARCHAR(500) DEFAULT NULL,
    `custom_api_key_encrypted` TEXT DEFAULT NULL,

    -- Personality Settings
    `bot_role` VARCHAR(100) DEFAULT 'Customer Support Agent',
    `business_type` VARCHAR(100) DEFAULT 'General',
    `response_tone` ENUM('professional', 'friendly', 'sales', 'support', 'healthcare', 'real_estate', 'custom') DEFAULT 'professional',
    `response_length` ENUM('concise', 'moderate', 'detailed') DEFAULT 'moderate',
    `language` VARCHAR(50) DEFAULT 'English',
    `system_prompt` TEXT DEFAULT NULL,

    -- Human Handover Settings
    `handover_enabled` TINYINT(1) DEFAULT 0,
    `handover_keywords` TEXT DEFAULT 'talk to human,human support,agent,representative',
    `handover_confidence_threshold` DECIMAL(3,2) DEFAULT 0.30,

    -- CRM Integration
    `crm_capture_enabled` TINYINT(1) DEFAULT 1,

    -- Rate Limiting
    `rate_limit_per_minute` INT DEFAULT 100,

    -- Counters (denormalized for performance)
    `total_conversations` INT DEFAULT 0,
    `total_messages_processed` INT DEFAULT 0,
    `total_leads_captured` INT DEFAULT 0,

    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`whatsapp_account_id`) REFERENCES `whatsapp_accounts`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_status` (`user_id`, `status`),
    INDEX `idx_uuid` (`uuid`),
    INDEX `idx_wa_account` (`whatsapp_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 2. AI Knowledge Bases Table
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_knowledge_bases` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bot_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL DEFAULT 'Default Knowledge Base',
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'processing') DEFAULT 'active',
    `total_chunks` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`bot_id`) REFERENCES `ai_bots`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_bot` (`bot_id`),
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 3. AI KB Documents (Uploaded Files)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_kb_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kb_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_type` ENUM('pdf', 'docx', 'txt', 'csv') NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT DEFAULT 0,
    `chunks_count` INT DEFAULT 0,
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `error_message` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`kb_id`) REFERENCES `ai_knowledge_bases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_kb` (`kb_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 4. AI KB URLs (Crawled Websites)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_kb_urls` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kb_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `url` VARCHAR(2000) NOT NULL,
    `title` VARCHAR(500) DEFAULT NULL,
    `chunks_count` INT DEFAULT 0,
    `status` ENUM('pending', 'crawling', 'completed', 'failed') DEFAULT 'pending',
    `error_message` TEXT DEFAULT NULL,
    `last_crawled_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`kb_id`) REFERENCES `ai_knowledge_bases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_kb` (`kb_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 5. AI KB Q&A Pairs (Manual Training)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_kb_qa_pairs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kb_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `question` TEXT NOT NULL,
    `answer` TEXT NOT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`kb_id`) REFERENCES `ai_knowledge_bases`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_kb` (`kb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 6. AI KB Chunks (Processed Text Blocks)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_kb_chunks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kb_id` INT NOT NULL,
    `source_type` ENUM('document', 'url', 'qa', 'manual') NOT NULL,
    `source_id` INT DEFAULT NULL,
    `content` TEXT NOT NULL,
    `content_hash` VARCHAR(64) DEFAULT NULL,
    `word_count` INT DEFAULT 0,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`kb_id`) REFERENCES `ai_knowledge_bases`(`id`) ON DELETE CASCADE,
    INDEX `idx_kb_source` (`kb_id`, `source_type`),
    INDEX `idx_hash` (`content_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 7. AI Conversations (Sessions)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_conversations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bot_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_name` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active', 'resolved', 'handed_over', 'expired') DEFAULT 'active',
    `messages_count` INT DEFAULT 0,
    `ai_messages_count` INT DEFAULT 0,
    `human_messages_count` INT DEFAULT 0,
    `tokens_used` INT DEFAULT 0,
    `resolved_by` ENUM('ai', 'human', 'expired') DEFAULT NULL,
    `handed_over_at` DATETIME DEFAULT NULL,
    `last_message_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`bot_id`) REFERENCES `ai_bots`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_bot_status` (`bot_id`, `status`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_phone` (`customer_phone`),
    INDEX `idx_last_msg` (`last_message_at`),
    UNIQUE KEY `uk_bot_phone` (`bot_id`, `customer_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 8. AI Messages (Conversation Messages)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` INT NOT NULL,
    `bot_id` INT NOT NULL,
    `direction` ENUM('inbound', 'outbound') NOT NULL,
    `sender_type` ENUM('customer', 'ai', 'human') NOT NULL,
    `content` TEXT NOT NULL,
    `tokens_used` INT DEFAULT 0,
    `confidence_score` DECIMAL(3,2) DEFAULT NULL,
    `ai_model_used` VARCHAR(50) DEFAULT NULL,
    `response_time_ms` INT DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bot_id`) REFERENCES `ai_bots`(`id`) ON DELETE CASCADE,
    INDEX `idx_conversation` (`conversation_id`),
    INDEX `idx_bot` (`bot_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 9. AI Leads (CRM Captures)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_leads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bot_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `conversation_id` INT DEFAULT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_name` VARCHAR(100) DEFAULT NULL,
    `customer_email` VARCHAR(150) DEFAULT NULL,
    `customer_company` VARCHAR(150) DEFAULT NULL,
    `requirement` TEXT DEFAULT NULL,
    `tags` VARCHAR(255) DEFAULT 'AI Generated Lead',
    `status` ENUM('new', 'contacted', 'qualified', 'converted', 'lost') DEFAULT 'new',
    `synced_to_contacts` TINYINT(1) DEFAULT 0,
    `contact_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`bot_id`) REFERENCES `ai_bots`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations`(`id`) ON DELETE SET NULL,
    INDEX `idx_bot` (`bot_id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_phone` (`customer_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 10. AI Handovers (Human Transfer Requests)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_handovers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bot_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `conversation_id` INT NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `trigger_type` ENUM('keyword', 'low_confidence', 'manual', 'error') NOT NULL,
    `trigger_message` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'accepted', 'resolved', 'expired') DEFAULT 'pending',
    `assigned_to` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` DATETIME DEFAULT NULL,

    FOREIGN KEY (`bot_id`) REFERENCES `ai_bots`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`conversation_id`) REFERENCES `ai_conversations`(`id`) ON DELETE CASCADE,
    INDEX `idx_bot_status` (`bot_id`, `status`),
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 11. AI Analytics Daily (Pre-aggregated)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_analytics_daily` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bot_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `total_conversations` INT DEFAULT 0,
    `new_conversations` INT DEFAULT 0,
    `resolved_by_ai` INT DEFAULT 0,
    `transferred_to_human` INT DEFAULT 0,
    `leads_generated` INT DEFAULT 0,
    `total_messages` INT DEFAULT 0,
    `ai_messages` INT DEFAULT 0,
    `human_messages` INT DEFAULT 0,
    `total_tokens_used` INT DEFAULT 0,
    `avg_response_time_ms` INT DEFAULT 0,
    `avg_confidence_score` DECIMAL(3,2) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`bot_id`) REFERENCES `ai_bots`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_bot_date` (`bot_id`, `date`),
    INDEX `idx_user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- 12. AI Credits (Token Usage Tracking)
-- ============================================
CREATE TABLE IF NOT EXISTS `ai_credits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_tokens` BIGINT DEFAULT 0,
    `used_tokens` BIGINT DEFAULT 0,
    `total_messages` INT DEFAULT 0,
    `used_messages` INT DEFAULT 0,
    `last_reset_at` DATETIME DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- ALTER existing plans table for AI limits
-- ============================================
ALTER TABLE `plans`
    ADD COLUMN `ai_enabled` TINYINT(1) DEFAULT 0 AFTER `priority_support`,
    ADD COLUMN `ai_bots_limit` INT DEFAULT 0 AFTER `ai_enabled`,
    ADD COLUMN `ai_messages_limit` INT DEFAULT 0 AFTER `ai_bots_limit`,
    ADD COLUMN `ai_kb_limit` INT DEFAULT 0 AFTER `ai_messages_limit`;


-- ============================================
-- Update default plans with AI limits
-- ============================================
UPDATE `plans` SET `ai_enabled` = 1, `ai_bots_limit` = 1, `ai_messages_limit` = 5000, `ai_kb_limit` = 1 WHERE `slug` = 'starter';
UPDATE `plans` SET `ai_enabled` = 1, `ai_bots_limit` = 5, `ai_messages_limit` = 50000, `ai_kb_limit` = 5 WHERE `slug` = 'professional';
UPDATE `plans` SET `ai_enabled` = 1, `ai_bots_limit` = 999, `ai_messages_limit` = 999999, `ai_kb_limit` = 999 WHERE `slug` = 'business';


-- ============================================
-- Add AI settings to settings table
-- ============================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`) VALUES
    ('ai_openai_api_key', '', 'ai', 'text'),
    ('ai_openai_enabled', '1', 'ai', 'boolean'),
    ('ai_gemini_api_key', '', 'ai', 'text'),
    ('ai_gemini_enabled', '1', 'ai', 'boolean'),
    ('ai_claude_api_key', '', 'ai', 'text'),
    ('ai_claude_enabled', '0', 'ai', 'boolean'),
    ('ai_custom_enabled', '1', 'ai', 'boolean'),
    ('ai_default_model', 'gpt-4o', 'ai', 'text'),
    ('ai_max_tokens_per_response', '1024', 'ai', 'number'),
    ('ai_max_context_messages', '10', 'ai', 'number'),
    ('ai_rate_limit_default', '100', 'ai', 'number'),
    ('ai_default_system_prompt', 'You are a helpful customer support assistant. Answer questions based on the provided knowledge base. If you cannot find the answer, politely let the customer know and offer to connect them with a human agent.', 'ai', 'textarea')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
