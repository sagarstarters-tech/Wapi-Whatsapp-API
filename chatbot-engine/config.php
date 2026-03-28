<?php
/**
 * WhatsApp Chatbot Configuration
 * Replace the placeholders with your actual Meta Cloud API credentials.
 */

// 1. Meta App Settings
define('WHATSAPP_API_TOKEN', 'YOUR_PERMANENT_ACCESS_TOKEN'); // System User Access Token
define('PHONE_NUMBER_ID', 'YOUR_PHONE_NUMBER_ID');          // From Meta Developer Portal
define('WHATSAPP_API_VERSION', 'v18.0');                   // Graph API Version

// 2. Webhook Settings
define('WEBHOOK_VERIFY_TOKEN', 'my_secret_token_123');     // For verification with Meta

// 3. Database Settings (Using your existing Database class)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../classes/Database.php';

// Log errors for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/webhook_errors.log');
?>
