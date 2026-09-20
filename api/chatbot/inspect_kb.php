<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    $db = Database::getInstance();
    $res = [];

    require_once __DIR__ . '/../../classes/AIOrchestrator.php';
    require_once __DIR__ . '/../../classes/AIBot.php';
    require_once __DIR__ . '/../../classes/AIModelAdapter.php';

    $testRes = AIOrchestrator::processMessage(
        1,
        'test_user_sim_link',
        'Sim User',
        'mujhe 1 hp submersible pump ke starter ka buy link chahiye',
        'test',
        'test_token'
    );

    echo json_encode([
        'status' => $testRes['status'] ?? 'unknown',
        'reply' => $testRes['message'] ?? $testRes['reply'] ?? $testRes,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
} catch (Throwable $t) {
    echo json_encode(['fatal' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()]);
}
