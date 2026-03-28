-- SQL table for WhatsApp chatbot sessions
CREATE TABLE IF NOT EXISTS `chatbot_sessions` (
    `phone` VARCHAR(20) PRIMARY KEY,
    `state` VARCHAR(50) NOT NULL DEFAULT 'start',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
