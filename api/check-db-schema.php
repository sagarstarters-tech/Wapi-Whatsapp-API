<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();

$response = [];

try {
    $conversation = $db->fetch(
        "SELECT * FROM ai_conversations WHERE customer_phone = 'test_user_9' LIMIT 1"
    );
    $response['conversation'] = $conversation;

    if ($conversation) {
        $messages = $db->fetchAll(
            "SELECT * FROM ai_messages WHERE conversation_id = ? ORDER BY id DESC LIMIT 10",
            [$conversation['id']]
        );
        $response['messages'] = $messages;
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
