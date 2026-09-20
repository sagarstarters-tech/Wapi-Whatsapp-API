<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    $db = Database::getInstance();
    $res = [];

    $chunks_cols = $db->fetchAll("DESCRIBE ai_kb_chunks");
    $qa_cols = $db->fetchAll("DESCRIBE ai_kb_qa_pairs");
    $urls_cols = $db->fetchAll("DESCRIBE ai_kb_urls");
    echo json_encode([
        'chunks_cols' => $chunks_cols,
        'qa_cols' => $qa_cols,
        'urls_cols' => $urls_cols
    ], JSON_PRETTY_PRINT);
    exit;
} catch (Throwable $t) {
    echo json_encode(['fatal' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()]);
}
