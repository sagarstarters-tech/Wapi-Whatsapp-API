-- ============================================
-- WhatsApp API SaaS Portal - Database Schema
-- Version: 1.0.0
-- Multi-tenant SaaS Architecture
-- ============================================

CREATE DATABASE IF NOT EXISTS `wapi_saas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wapi_saas`;

-- ============================================
-- 1. Users Table
-- ============================================
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(36) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `company_name` VARCHAR(150) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `email_verified` TINYINT(1) DEFAULT 0,
    `email_verify_token` VARCHAR(100) DEFAULT NULL,
    `reset_token` VARCHAR(100) DEFAULT NULL,
    `reset_token_expiry` DATETIME DEFAULT NULL,
    `last_login` DATETIME DEFAULT NULL,
    `login_attempts` INT DEFAULT 0,
    `locked_until` DATETIME DEFAULT NULL,
    `timezone` VARCHAR(50) DEFAULT 'Asia/Kolkata',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- ============================================
-- 2. Settings Table (Key-Value Store)
-- ============================================
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_group` VARCHAR(50) DEFAULT 'general',
    `setting_type` ENUM('text', 'textarea', 'image', 'json', 'boolean', 'number', 'color') DEFAULT 'text',
    `is_public` TINYINT(1) DEFAULT 1,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_group` (`setting_group`),
    INDEX `idx_key` (`setting_key`)
) ENGINE=InnoDB;

-- ============================================
-- 3. Pages Content (CMS)
-- ============================================
CREATE TABLE `pages_content` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page` VARCHAR(50) NOT NULL,
    `section` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `subtitle` TEXT DEFAULT NULL,
    `content` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `button_text` VARCHAR(100) DEFAULT NULL,
    `button_link` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_page_section` (`page`, `section`)
) ENGINE=InnoDB;

-- ============================================
-- 4. Features Table
-- ============================================
CREATE TABLE `features` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `icon` VARCHAR(50) DEFAULT 'bi-star',
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 5. Plans Table
-- ============================================
CREATE TABLE `plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `monthly_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `yearly_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `message_limit` INT DEFAULT 1000,
    `contacts_limit` INT DEFAULT 500,
    `api_calls_limit` INT DEFAULT 5000,
    `templates_limit` INT DEFAULT 10,
    `templates_limit` INT DEFAULT 10,
    `bulk_messaging` TINYINT(1) DEFAULT 0,
    `webhook_enabled` TINYINT(1) DEFAULT 0,
    `analytics_enabled` TINYINT(1) DEFAULT 0,
    `priority_support` TINYINT(1) DEFAULT 0,
    `badge_color` VARCHAR(20) DEFAULT '#6c63ff',
    `is_popular` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 6. Plan Features (Many-to-Many)
-- ============================================
CREATE TABLE `plan_features` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plan_id` INT NOT NULL,
    `feature_text` VARCHAR(255) NOT NULL,
    `is_included` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 7. Subscriptions Table
-- ============================================
CREATE TABLE `subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `plan_id` INT NOT NULL,
    `billing_cycle` ENUM('monthly', 'yearly') DEFAULT 'monthly',
    `amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'pending',
    `starts_at` DATETIME NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `razorpay_subscription_id` VARCHAR(100) DEFAULT NULL,
    `auto_renew` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`plan_id`) REFERENCES `plans`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- ============================================
-- 8. Payments Table
-- ============================================
CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `subscription_id` INT DEFAULT NULL,
    `razorpay_order_id` VARCHAR(100) DEFAULT NULL,
    `razorpay_payment_id` VARCHAR(100) DEFAULT NULL,
    `razorpay_signature` VARCHAR(255) DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `status` ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `invoice_number` VARCHAR(50) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- ============================================
-- 9. API Keys Table
-- ============================================
CREATE TABLE `api_keys` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `api_key` VARCHAR(64) NOT NULL UNIQUE,
    `api_secret` VARCHAR(128) NOT NULL,
    `name` VARCHAR(100) DEFAULT 'Default',
    `permissions` JSON DEFAULT NULL,
    `last_used_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_api_key` (`api_key`)
) ENGINE=InnoDB;

-- ============================================
-- 10. WhatsApp Accounts (Multi-tenant)
-- ============================================
CREATE TABLE `whatsapp_accounts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `phone_number_id` VARCHAR(50) NOT NULL,
    `waba_id` VARCHAR(50) DEFAULT NULL,
    `access_token` TEXT NOT NULL,
    `business_name` VARCHAR(150) DEFAULT NULL,
    `phone_number` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    `webhook_secret` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB;

-- ============================================
-- 11. Contacts Table
-- ============================================
CREATE TABLE `contacts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `company` VARCHAR(150) DEFAULT NULL,
    `tags` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_phone` (`user_id`, `phone`),
    UNIQUE KEY `uk_user_phone` (`user_id`, `phone`)
) ENGINE=InnoDB;

-- ============================================
-- 12. Contact Groups
-- ============================================
CREATE TABLE `contact_groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT '#6c63ff',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `contact_group_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT NOT NULL,
    `contact_id` INT NOT NULL,
    FOREIGN KEY (`group_id`) REFERENCES `contact_groups`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`contact_id`) REFERENCES `contacts`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_group_contact` (`group_id`, `contact_id`)
) ENGINE=InnoDB;

-- ============================================
-- 13. Messages Table
-- ============================================
CREATE TABLE `messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `whatsapp_account_id` INT DEFAULT NULL,
    `contact_id` INT DEFAULT NULL,
    `message_id` VARCHAR(100) DEFAULT NULL,
    `to_number` VARCHAR(20) NOT NULL,
    `type` ENUM('text', 'image', 'video', 'document', 'audio', 'voice', 'sticker', 'location', 'contacts', 'interactive', 'button', 'template', 'reaction', 'system', 'identity', 'unsupported') DEFAULT 'text',
    `content` TEXT DEFAULT NULL,
    `media_url` VARCHAR(500) DEFAULT NULL,
    `template_name` VARCHAR(100) DEFAULT NULL,
    `template_params` JSON DEFAULT NULL,
    `status` ENUM('queued', 'sent', 'delivered', 'read', 'failed') DEFAULT 'queued',
    `error_message` TEXT DEFAULT NULL,
    `direction` ENUM('outbound', 'inbound') DEFAULT 'outbound',
    `credits_used` INT DEFAULT 1,
    `sent_at` DATETIME DEFAULT NULL,
    `delivered_at` DATETIME DEFAULT NULL,
    `read_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_status` (`user_id`, `status`),
    INDEX `idx_message_id` (`message_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- ============================================
-- 14. Templates Table
-- ============================================
CREATE TABLE `templates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('marketing', 'utility', 'authentication') DEFAULT 'utility',
    `language` VARCHAR(10) DEFAULT 'en',
    `header_type` ENUM('none', 'text', 'image', 'video', 'document') DEFAULT 'none',
    `header_content` TEXT DEFAULT NULL,
    `body` TEXT NOT NULL,
    `footer` VARCHAR(255) DEFAULT NULL,
    `buttons` JSON DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `meta_template_id` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB;



-- ============================================
-- 16. Credits Table
-- ============================================
CREATE TABLE `credits` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_credits` INT DEFAULT 0,
    `used_credits` INT DEFAULT 0,
    `last_reset_at` DATETIME DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_user` (`user_id`)
) ENGINE=InnoDB;

CREATE TABLE `credit_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('credit', 'debit') NOT NULL,
    `amount` INT NOT NULL,
    `balance_after` INT NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB;

-- ============================================
-- 17. Notifications Table
-- ============================================
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `link` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB;

-- ============================================
-- 18. Testimonials Table
-- ============================================
CREATE TABLE `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `company` VARCHAR(150) DEFAULT NULL,
    `designation` VARCHAR(100) DEFAULT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `content` TEXT NOT NULL,
    `rating` INT DEFAULT 5,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 19. FAQs Table
-- ============================================
CREATE TABLE `faqs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `question` TEXT NOT NULL,
    `answer` TEXT NOT NULL,
    `category` VARCHAR(50) DEFAULT 'general',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 20. Webhook Logs
-- ============================================
CREATE TABLE `webhook_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `payload` JSON DEFAULT NULL,
    `status` ENUM('received', 'processed', 'failed') DEFAULT 'received',
    `error_message` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_event` (`event_type`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- ============================================
-- 21. Activity Logs
-- ============================================
CREATE TABLE `activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- ============================================
-- DEFAULT DATA INSERTS
-- ============================================

-- Admin User (password: Admin@123)
INSERT INTO `users` (`uuid`, `name`, `email`, `password`, `role`, `status`, `email_verified`) VALUES
(UUID(), 'Super Admin', 'admin@wapi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', 1);

-- Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `setting_type`) VALUES
('site_name', 'WAPI - WhatsApp API Platform', 'general', 'text'),
('site_tagline', 'Powerful WhatsApp Business API for your business', 'general', 'text'),
('site_description', 'Complete WhatsApp Business API solution for sending messages, managing contacts, and tracking communication.', 'general', 'textarea'),
('site_logo', '/assets/images/logo.png', 'general', 'image'),
('site_favicon', '/assets/images/favicon.png', 'general', 'image'),
('primary_color', '#6c63ff', 'theme', 'color'),
('secondary_color', '#3f3d56', 'theme', 'color'),
('accent_color', '#00d2ff', 'theme', 'color'),
('footer_text', '© 2026 WAPI. All rights reserved.', 'general', 'text'),
('contact_email', 'support@wapi.com', 'general', 'text'),
('contact_phone', '+91 9876543210', 'general', 'text'),
('whatsapp_api_version', 'v17.0', 'whatsapp', 'text'),
('whatsapp_api_url', 'https://graph.facebook.com', 'whatsapp', 'text'),
('razorpay_key_id', '', 'payment', 'text'),
('razorpay_key_secret', '', 'payment', 'text'),
('razorpay_test_mode', '1', 'payment', 'boolean'),
('smtp_host', '', 'email', 'text'),
('smtp_port', '587', 'email', 'number'),
('smtp_username', '', 'email', 'text'),
('smtp_password', '', 'email', 'text'),
('smtp_encryption', 'tls', 'email', 'text'),
('smtp_from_name', 'WAPI', 'email', 'text'),
('smtp_from_email', 'noreply@wapi.com', 'email', 'text'),
('recaptcha_site_key', '', 'security', 'text'),
('recaptcha_secret_key', '', 'security', 'text'),
('google_analytics_id', '', 'analytics', 'text'),
('meta_keywords', 'whatsapp api, whatsapp business api, bulk whatsapp, whatsapp saas', 'seo', 'text'),
('hero_title', 'Supercharge Your Business with WhatsApp API', 'landing', 'text'),
('hero_subtitle', 'Send bulk messages, manage contacts and grow your business with our powerful WhatsApp Business API platform.', 'landing', 'textarea'),
('hero_button_text', 'Get Started Free', 'landing', 'text'),
('hero_button_link', '/wapi/auth/register.php', 'landing', 'text'),
('hero_image', '/assets/images/hero-illustration.svg', 'landing', 'image'),
('features_title', 'Everything You Need to Scale', 'landing', 'text'),
('features_subtitle', 'Our platform provides all the tools you need to manage your WhatsApp Business communication at scale.', 'landing', 'textarea'),
('pricing_title', 'Simple, Transparent Pricing', 'landing', 'text'),
('pricing_subtitle', 'Choose the plan that fits your business needs. Upgrade or downgrade anytime.', 'landing', 'textarea'),
('testimonials_title', 'Trusted by 10,000+ Businesses', 'landing', 'text'),
('faq_title', 'Frequently Asked Questions', 'landing', 'text'),
('chat_widget_enabled', '1', 'widget', 'boolean'),
('chat_widget_number', '+919876543210', 'widget', 'text'),
('chat_widget_message', 'Hi! I need help with WhatsApp API.', 'widget', 'text');

-- Default Features
INSERT INTO `features` (`icon`, `title`, `description`, `sort_order`) VALUES
('bi-chat-dots-fill', 'Bulk Messaging', 'Send thousands of WhatsApp messages in one click with our high-speed bulk messaging engine.', 1),

('bi-graph-up-arrow', 'Real-time Analytics', 'Track message delivery, open rates, and engagement with beautiful dashboards.', 3),
('bi-shield-check', 'Official API', 'Built on Meta''s official WhatsApp Cloud API for reliability and compliance.', 4),
('bi-people-fill', 'Contact Management', 'Organize contacts into groups, add tags, and manage your audience effortlessly.', 5),
('bi-credit-card-2-front', 'Easy Payments', 'Integrated payment system with multiple gateways and automatic subscription management.', 6),
('bi-code-slash', 'REST API', 'Full REST API access for developers to integrate WhatsApp into any application.', 7),
('bi-phone', 'Template Messages', 'Create and manage pre-approved WhatsApp message templates with dynamic variables.', 8);

-- Default Plans
INSERT INTO `plans` (`name`, `slug`, `description`, `monthly_price`, `yearly_price`, `message_limit`, `contacts_limit`, `api_calls_limit`, `templates_limit`, `bulk_messaging`, `webhook_enabled`, `analytics_enabled`, `priority_support`, `badge_color`, `is_popular`, `sort_order`) VALUES
('Starter', 'starter', 'Perfect for small businesses getting started with WhatsApp API', 999.00, 9990.00, 1000, 500, 5000, 5, 0, 0, 0, '#28a745', 0, 1),
('Professional', 'professional', 'For growing businesses that need more power and features', 2499.00, 24990.00, 5000, 2500, 25000, 25, 1, 1, 0, '#6c63ff', 1, 2),
('Business', 'business', 'For enterprises that need unlimited power and priority support', 4999.00, 49990.00, 25000, 10000, 100000, 100, 1, 1, 1, '#ff6b35', 0, 3);

-- Default Plan Features
INSERT INTO `plan_features` (`plan_id`, `feature_text`, `is_included`, `sort_order`) VALUES
(1, '1,000 Messages/month', 1, 1),
(1, '500 Contacts', 1, 2),
(1, '5 Templates', 1, 3),
(1, 'Basic Analytics', 1, 4),
(1, 'Email Support', 1, 5),
(1, 'Bulk Messaging', 0, 7),
(1, 'Webhook Support', 0, 8),
(2, '5,000 Messages/month', 1, 1),
(2, '2,500 Contacts', 1, 2),
(2, '25 Templates', 1, 3),
(2, 'Advanced Analytics', 1, 4),
(2, 'Priority Email Support', 1, 5),
(2, 'Bulk Messaging', 1, 7),
(2, 'Webhook Support', 1, 8),
(3, '25,000 Messages/month', 1, 1),
(3, '10,000 Contacts', 1, 2),
(3, '100 Templates', 1, 3),
(3, 'Full Analytics Suite', 1, 4),
(3, '24/7 Priority Support', 1, 5),
(3, 'Unlimited Bulk Messaging', 1, 7),
(3, 'Webhook + API Access', 1, 8);

-- Default Testimonials
INSERT INTO `testimonials` (`name`, `company`, `designation`, `content`, `rating`, `sort_order`) VALUES
('Rahul Sharma', 'TechVista Solutions', 'CEO', 'WAPI has transformed how we communicate with our customers. The bulk messaging feature alone saved us 20 hours per week!', 5, 1),

('Amit Kumar', 'FastShip Logistics', 'CTO', 'The API integration was seamless. We connected our CRM in just 2 hours. Best WhatsApp API platform out there.', 5, 3),
('Sneha Reddy', 'EduPro Academy', 'Operations Manager', 'Managing 50,000+ student contacts is now effortless. The analytics dashboard gives us real insights.', 4, 4);

-- Default FAQs
INSERT INTO `faqs` (`question`, `answer`, `category`, `sort_order`) VALUES
('What is WhatsApp Business API?', 'WhatsApp Business API is a solution by Meta that allows businesses to communicate with customers at scale. It supports automated messages, bulk messaging, and integrations with CRM and other business tools.', 'general', 1),
('How do I get started?', 'Simply sign up for an account, choose a pricing plan, and connect your WhatsApp Business number. Our step-by-step setup wizard will guide you through the entire process in under 10 minutes.', 'general', 2),
('Do I need a Meta Business account?', 'Yes, you need a Meta Business account and a verified WhatsApp Business phone number to use the WhatsApp Cloud API. We provide detailed documentation to help you set this up.', 'general', 3),
('Is there a free trial?', 'Yes! Our Starter plan comes with 1,000 free messages so you can test the platform before committing to a paid plan.', 'pricing', 4),
('Can I upgrade or downgrade my plan?', 'Absolutely! You can upgrade or downgrade your plan at any time from your dashboard. Changes take effect immediately, and we prorate billing accordingly.', 'pricing', 5),
('How secure is the platform?', 'We take security seriously. All data is encrypted, we use secure authentication, and comply with Meta''s data protection requirements. Your API keys are stored encrypted at rest.', 'security', 6),
('Do you provide API documentation?', 'Yes, we provide comprehensive REST API documentation with code examples in PHP, Python, Node.js, and cURL. You can access it from your dashboard.', 'technical', 7),
('What payment methods do you accept?', 'We accept all major payment methods through Razorpay including credit/debit cards, UPI, net banking, and digital wallets.', 'pricing', 8);
