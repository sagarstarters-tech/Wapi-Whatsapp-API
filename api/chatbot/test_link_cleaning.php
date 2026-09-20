<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../classes/Database.php';
    require_once __DIR__ . '/../../classes/AIOrchestrator.php';
    require_once __DIR__ . '/../../classes/AIBot.php';
    require_once __DIR__ . '/../../classes/AIModelAdapter.php';
    require_once __DIR__ . '/../../classes/AIKnowledgeBase.php';

    $testRes = AIOrchestrator::processMessage(
        1,
        'test_user_sim_website_links',
        'Sim User',
        'kya aapke paas koi website hai jaha sabhi products dekh sake? website link do',
        'test',
        'test_token'
    );

    echo json_encode([
        'status' => $testRes['status'] ?? 'unknown',
        'reply' => $testRes['message'] ?? $testRes['reply'] ?? $testRes,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $t) {
    echo json_encode(['status' => 'error', 'message' => $t->getMessage(), 'line' => $t->getLine()]);
}
