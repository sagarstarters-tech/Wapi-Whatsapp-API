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
    if ($conv) {
        $msgs = $db->fetchAll(
            "SELECT id, direction, sender_type, content, created_at 
             FROM ai_messages 
             WHERE conversation_id = ? 
             ORDER BY id ASC",
            [$conv['id']]
        );
        echo "<pre>";
        foreach ($msgs as $m) {
            echo "[#{$m['id']} {$m['created_at']} {$m['direction']}/{$m['sender_type']}]:\n{$m['content']}\n\n===============================\n\n";
        }
        echo "</pre>";
        exit;
    }
    echo "No conv";
    exit;
} catch (Throwable $t) {
    echo json_encode(['fatal' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()]);
}
