<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    $db = Database::getInstance();
    $res = [];

    // 1. Conversation with 918573934013
    try {
        $conv = $db->fetch("SELECT * FROM ai_conversations WHERE customer_phone LIKE '%8573934013%' LIMIT 1");
        $res['conversation'] = $conv;

        if ($conv) {
            $res['messages'] = $db->fetchAll(
                "SELECT id, direction, sender_type, content, tokens_used, created_at 
                 FROM ai_messages 
                 WHERE conversation_id = ? 
                 ORDER BY created_at ASC",
                [$conv['id']]
            );
        }
    } catch (Exception $e) { $res['conv_err'] = $e->getMessage(); }

    // 2. Knowledge Base for Bot 1
    try {
        $kb = $db->fetch("SELECT * FROM ai_knowledge_bases WHERE bot_id = 1 LIMIT 1");
        $res['kb'] = $kb;
        $res['kb_urls'] = $db->fetchAll("SELECT * FROM ai_kb_urls");
        $res['kb_documents'] = $db->fetchAll("SELECT * FROM ai_kb_documents");
        $res['kb_qa_pairs'] = $db->fetchAll("SELECT * FROM ai_kb_qa_pairs");
        $res['kb_chunks_count'] = $db->fetchColumn("SELECT COUNT(*) FROM ai_kb_chunks");
        $res['sample_chunks'] = $db->fetchAll("SELECT id, source_type, word_count, SUBSTRING(content, 1, 300) as snippet FROM ai_kb_chunks LIMIT 10");
    } catch (Exception $e) { $res['kb_err'] = $e->getMessage(); }

    echo json_encode($res, JSON_PRETTY_PRINT);
} catch (Throwable $t) {
    echo json_encode(['fatal' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()]);
}
