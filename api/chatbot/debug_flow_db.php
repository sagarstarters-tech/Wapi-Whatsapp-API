<?php
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance();

$aiBots = $db->fetchAll("SELECT id, user_id, whatsapp_account_id, name, status FROM ai_bots");
$waAccounts = $db->fetchAll("SELECT id, user_id, phone_number_id, phone_number, status FROM whatsapp_accounts");

$helloFlow = $db->fetch("SELECT id, user_id, name, is_active, flow_json FROM chatbot_flows WHERE name LIKE '%hello%' OR name LIKE '%bot%' ORDER BY id DESC LIMIT 1");
$startNode = null;
$nodeSummaries = [];

if ($helloFlow) {
    $data = json_decode($helloFlow['flow_json'], true);
    $nodes = $data['drawflow']['Home']['data'] ?? $data['drawflow']['home']['data'] ?? [];
    foreach ($nodes as $nId => $n) {
        $name = $n['name'] ?? 'unknown';
        if ($name === 'start') {
            $startNode = [
                'id' => $nId,
                'data' => $n['data'] ?? [],
                'outputs' => $n['outputs'] ?? []
            ];
        }
        $nodeSummaries[$nId] = [
            'name' => $name,
            'image' => $n['data']['image'] ?? null,
            'text' => substr($n['data']['text'] ?? $n['data']['body_text'] ?? '', 0, 40),
            'conns' => count($n['outputs']['output_1']['connections'] ?? [])
        ];
    }
}

header('Content-Type: application/json');
echo json_encode([
    'ai_bots' => $aiBots,
    'wa_accounts' => $waAccounts,
    'flow_meta' => [
        'id' => $helloFlow['id'] ?? null,
        'user_id' => $helloFlow['user_id'] ?? null,
        'name' => $helloFlow['name'] ?? null,
        'is_active' => $helloFlow['is_active'] ?? null,
    ],
    'start_node' => $startNode,
    'nodes_summary' => $nodeSummaries
], JSON_PRETTY_PRINT);
