<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance();

$res = [];

// 1. Conversation with 918573934013
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

// 2. Knowledge Base for Bot 1
$kb = $db->fetch("SELECT * FROM ai_knowledge_bases WHERE bot_id = 1 LIMIT 1");
$res['kb'] = $kb;

$res['kb_urls'] = $db->fetchAll("SELECT id, kb_id, url, status, word_count, error_message, last_crawled_at FROM ai_kb_urls");
$res['kb_documents'] = $db->fetchAll("SELECT id, kb_id, filename, status, word_count FROM ai_kb_documents");
$res['kb_qa_pairs'] = $db->fetchAll("SELECT id, kb_id, question, answer, is_active FROM ai_kb_qa_pairs");
$res['kb_chunks_count'] = $db->fetchColumn("SELECT COUNT(*) FROM ai_kb_chunks");
$res['sample_chunks'] = $db->fetchAll("SELECT id, source_type, word_count, SUBSTRING(content, 1, 300) as snippet FROM ai_kb_chunks LIMIT 10");

// 3. Search test
require_once __DIR__ . '/../../classes/AIKnowledgeBase.php';
$testTerms = ['starter', 'product', '1 hp', 'price', 'submersible'];
foreach ($testTerms as $t) {
    $chunks = AIKnowledgeBase::searchChunks(1, $t, 3);
    $res['search_test'][$t] = array_map(function($c) {
        return ['id' => $c['id'] ?? null, 'snippet' => substr($c['content'] ?? '', 0, 200)];
    }, $chunks);
}

echo json_encode($res, JSON_PRETTY_PRINT);
