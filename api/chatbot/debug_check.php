<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance();

$res = [];

// 1. Check AI Bots
$res['ai_bots'] = $db->fetchAll("SELECT id, user_id, whatsapp_account_id, name, status, ai_provider, ai_model, welcome_message FROM ai_bots");

// 2. Check WhatsApp Accounts
$res['wa_accounts'] = $db->fetchAll("SELECT id, user_id, phone_number_id, phone_number, status FROM whatsapp_accounts");

// 3. Check Chatbot Flows
$res['flows'] = $db->fetchAll("SELECT id, user_id, name, is_active, updated_at FROM chatbot_flows ORDER BY updated_at DESC");

// 4. Check AI Conversations
$res['ai_conversations'] = $db->fetchAll("SELECT id, bot_id, user_id, customer_phone, customer_name, status, last_message_at FROM ai_conversations ORDER BY id DESC LIMIT 5");

// 5. Check AI Messages
$res['ai_messages'] = $db->fetchAll("SELECT id, conversation_id, bot_id, direction, sender_type, content, created_at FROM ai_messages ORDER BY id DESC LIMIT 5");

// 6. Check Log Files
$logFiles = [
    'webhook_root' => __DIR__ . '/../../logs/webhook_root.log',
    'ai_webhook' => __DIR__ . '/../../logs/ai_webhook.log',
    'webhook_debug' => __DIR__ . '/../webhook_debug.txt',
    'chatbot_debug' => __DIR__ . '/../../chatbot-engine/webhook_debug.log',
    'error_log' => __DIR__ . '/../../logs/error.log'
];

foreach ($logFiles as $k => $p) {
    if (file_exists($p)) {
        $lines = file($p);
        $res['logs'][$k] = array_slice($lines, -20);
    } else {
        $res['logs'][$k] = 'File not found';
    }
}

echo json_encode($res, JSON_PRETTY_PRINT);
