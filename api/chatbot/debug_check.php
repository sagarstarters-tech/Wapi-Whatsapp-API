<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    $db = Database::getInstance();
    $res = [];

    // 1. Database Cleanup for Handover
    $res['db_cleanup_conversations'] = $db->query("UPDATE ai_conversations SET status = 'active' WHERE status = 'handed_over'")->rowCount();
    $res['db_cleanup_bots'] = $db->query("UPDATE ai_bots SET handover_keywords = 'agent, human, support, help' WHERE handover_keywords LIKE '%Hi%' OR handover_keywords LIKE '%hi%'")->rowCount();

    // 2. Fetch AI Bots
    $res['ai_bots'] = $db->fetchAll("SELECT id, name, status, whatsapp_account_id, ai_model, handover_enabled, handover_keywords FROM ai_bots");

    // 3. Fetch AI Conversations
    $res['ai_conversations'] = $db->fetchAll("SELECT id, bot_id, customer_phone, customer_name, status, last_message_at FROM ai_conversations ORDER BY id DESC LIMIT 5");

    // 4. Test Gemini connection / call directly
    try {
        require_once __DIR__ . '/../../classes/Settings.php';
        require_once __DIR__ . '/../../classes/AIModelAdapter.php';
        $testResult = AIModelAdapter::testProvider('gemini');
        $res['gemini_test'] = $testResult;
    } catch (Exception $ge) {
        $res['gemini_test_error'] = $ge->getMessage();
    }

    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Throwable $t) {
    echo json_encode(['fatal' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()], JSON_PRETTY_PRINT);
}
