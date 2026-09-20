<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    $db = Database::getInstance();
    $res = [];

    // Check AI Bots
    try {
        $res['ai_bots'] = $db->fetchAll("SELECT * FROM ai_bots");
    } catch (Exception $e) { $res['ai_bots_err'] = $e->getMessage(); }

    // Check WhatsApp Accounts
    try {
        $res['wa_accounts'] = $db->fetchAll("SELECT id, user_id, phone_number_id, phone_number, status FROM whatsapp_accounts");
    } catch (Exception $e) { $res['wa_accounts_err'] = $e->getMessage(); }

    // Check Chatbot Flows
    try {
        $res['flows'] = $db->fetchAll("SELECT id, user_id, name, is_active, updated_at FROM chatbot_flows ORDER BY updated_at DESC");
    } catch (Exception $e) { $res['flows_err'] = $e->getMessage(); }

    // Check Log Files
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
            $res['logs'][$k] = array_slice($lines, -15);
        } else {
            $res['logs'][$k] = 'File not found';
        }
    }

    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Throwable $t) {
    echo json_encode(['fatal' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()], JSON_PRETTY_PRINT);
}
