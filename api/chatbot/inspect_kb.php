<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    $db = Database::getInstance();
    $res = [];

    $bot = $db->fetch("SELECT id, name, system_prompt, greeting_message, welcome_message, fallback_message, business_hours_enabled, ai_model FROM ai_bots WHERE id = 1");
    echo json_encode(['bot' => $bot], JSON_PRETTY_PRINT);
    exit;
} catch (Throwable $t) {
    echo json_encode(['fatal' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()]);
}
