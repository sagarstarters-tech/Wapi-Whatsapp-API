<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();

$response = [
    'ai_conversations' => [],
    'ai_credits' => [],
    'ai_handovers' => [],
    'ai_leads' => []
];

try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_conversations`");
    foreach ($columns as $col) {
        $response['ai_conversations'][] = $col['Field'] ?? $col['field'];
    }
} catch (Exception $e) {
    $response['ai_conversations_error'] = $e->getMessage();
}

try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_credits`");
    foreach ($columns as $col) {
        $response['ai_credits'][] = $col['Field'] ?? $col['field'];
    }
} catch (Exception $e) {
    $response['ai_credits_error'] = $e->getMessage();
}

try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_handovers`");
    foreach ($columns as $col) {
        $response['ai_handovers'][] = $col['Field'] ?? $col['field'];
    }
} catch (Exception $e) {
    $response['ai_handovers_error'] = $e->getMessage();
}

try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM `ai_leads`");
    foreach ($columns as $col) {
        $response['ai_leads'][] = $col['Field'] ?? $col['field'];
    }
} catch (Exception $e) {
    $response['ai_leads_error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
