<?php
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance();
$flows = $db->fetchAll("SELECT id, user_id, name, is_active, LENGTH(flow_json) as json_len, updated_at FROM chatbot_flows ORDER BY id DESC");
$result = ['flows' => $flows];

$helloFlow = $db->fetch("SELECT * FROM chatbot_flows WHERE name LIKE '%hello%' OR name LIKE '%bot%' ORDER BY id DESC LIMIT 1");
if (!$helloFlow && !empty($flows)) {
    $helloFlow = $db->fetch("SELECT * FROM chatbot_flows WHERE id = ?", [$flows[0]['id']]);
}

if ($helloFlow) {
    $data = json_decode($helloFlow['flow_json'], true);
    $nodes = $data['drawflow']['Home']['data'] ?? $data['drawflow']['home']['data'] ?? [];
    $imageNodes = [];
    foreach ($nodes as $nId => $node) {
        $nData = $node['data'] ?? [];
        $img = $nData['image'] ?? $nData['url'] ?? null;
        if (!empty($img) || in_array($node['name'] ?? '', ['cta', 'interactive', 'image'])) {
            $imageNodes[] = [
                'id' => $nId,
                'name' => $node['name'] ?? '',
                'image' => $img,
                'full_data' => $nData
            ];
        }
    }
    $result['inspect_flow'] = [
        'id' => $helloFlow['id'],
        'name' => $helloFlow['name'],
        'is_active' => $helloFlow['is_active'],
        'node_count' => count($nodes),
        'image_nodes' => $imageNodes
    ];
}

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);

