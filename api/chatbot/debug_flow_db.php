<?php
require_once __DIR__ . '/../../config/config.php';
$db = Database::getInstance();
$flow = $db->fetch("SELECT flow_json FROM chatbot_flows WHERE id = 13");
$data = json_decode($flow['flow_json'], true);
$nodes = $data['drawflow']['Home']['data'] ?? [];

$nodeDetails = [];
foreach ([61, 62, 64, 65, 67, 68, 69, 70, 71] as $id) {
    if (isset($nodes[$id])) {
        $nodeDetails[$id] = [
            'name' => $nodes[$id]['name'],
            'data' => $nodes[$id]['data']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($nodeDetails, JSON_PRETTY_PRINT);
