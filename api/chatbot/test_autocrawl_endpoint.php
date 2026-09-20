<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../classes/Database.php';
    require_once __DIR__ . '/../../classes/AIKnowledgeBase.php';

    $db = Database::getInstance();
    $kb = $db->fetch("SELECT * FROM ai_knowledge_bases WHERE bot_id = 1 LIMIT 1");
    if (!$kb) {
        throw new Exception("KB not found");
    }

    $res = AIKnowledgeBase::addUrl((int) $kb['id'], (int) $kb['user_id'], 'https://www.sagarstarters.com');
    echo json_encode(['status' => 'success', 'result' => $res], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $t) {
    echo json_encode(['status' => 'error', 'message' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()]);
}
