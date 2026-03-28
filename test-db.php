<?php
require_once __DIR__ . '/config/config.php';
$db = Database::getInstance();

$flows = $db->fetchAll("SELECT id, user_id, flow_json FROM chatbot_flows ORDER BY id DESC LIMIT 1");
if (empty($flows)) {
    die("No flows found.\n");
}
$flow = $flows[0];
echo "USER ID: " . $flow['user_id'] . "\n";
echo "FLOW JSON PREVIEW: " . substr($flow['flow_json'], 0, 500) . "...\n";

$flowData = json_decode($flow['flow_json'], true);
$nodes = $flowData['drawflow']['Home']['data'] ?? [];
echo "NODES: \n";
foreach ($nodes as $id => $node) {
    echo "- Node ID $id: type=" . $node['name'] . "\n";
    if ($node['name'] === 'start') {
        echo "  Keywords: '" . ($node['data']['keywords'] ?? '') . "'\n";
    }
}
