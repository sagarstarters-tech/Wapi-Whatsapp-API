<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    $db = Database::getInstance();
    $res = [];

    // Test knowledge search
    $q1 = AIKnowledgeBase::searchChunks(1, '1 hp submersible motor starter buy link', 5);
    $q2 = AIKnowledgeBase::searchChunks(1, 'mujhe is product ka link do 1 hp aur 220v ki hai', 5);
    $q3 = AIKnowledgeBase::searchChunks(1, 'product kharidne ka link', 5);

    echo json_encode([
        'q1_count' => count($q1),
        'q1_top_snippet' => !empty($q1) ? substr($q1[0]['content'], 0, 300) : null,
        'q2_count' => count($q2),
        'q2_top_snippet' => !empty($q2) ? substr($q2[0]['content'], 0, 300) : null,
        'q3_count' => count($q3),
        'q3_top_snippet' => !empty($q3) ? substr($q3[0]['content'], 0, 300) : null,
    ], JSON_PRETTY_PRINT);
    exit;
} catch (Throwable $t) {
    echo json_encode(['fatal' => $t->getMessage(), 'line' => $t->getLine(), 'file' => $t->getFile()]);
}
