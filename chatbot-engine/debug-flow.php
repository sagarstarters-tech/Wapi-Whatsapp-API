<?php
/**
 * Debug Tool: Inspect Flow JSON structure for card nodes
 * Access: http://localhost/wapi/chatbot-engine/debug-flow.php
 * DELETE THIS FILE after debugging!
 */
require_once __DIR__ . '/config.php';
echo '<pre style="font-family:monospace;font-size:13px;background:#1e1e1e;color:#d4d4d4;padding:20px;">';

$db = Database::getInstance();

// Get latest flow
$flow = $db->fetch("SELECT id, title, flow_json FROM chatbot_flows ORDER BY id DESC LIMIT 1");
if (!$flow) { echo "No flows found!"; exit; }

echo "=== Flow ID: {$flow['id']} | Title: {$flow['title']} ===\n\n";

$data = json_decode($flow['flow_json'], true);
$nodes = $data['drawflow']['Home']['data'] ?? $data['drawflow']['home']['data'] ?? [];

echo "Total nodes: " . count($nodes) . "\n\n";

foreach ($nodes as $nodeId => $node) {
    $type = $node['name'];
    echo "--- Node $nodeId (type: $type) ---\n";
    
    // Show data keys
    $dataKeys = array_keys($node['data'] ?? []);
    echo "  Data keys: " . implode(', ', $dataKeys) . "\n";
    
    // Show btn data
    foreach ($node['data'] as $k => $v) {
        if (strpos($k, 'btn-') === 0) {
            echo "  $k = '$v'\n";
        }
    }
    
    // Show outputs
    $outputs = $node['outputs'] ?? [];
    echo "  Outputs (" . count($outputs) . "):\n";
    foreach ($outputs as $opName => $opData) {
        $conns = $opData['connections'] ?? [];
        $connStr = empty($conns) ? '(no connection)' : implode(', ', array_column($conns, 'node'));
        echo "    $opName -> $connStr\n";
    }
    
    // For card nodes, show what button IDs will be generated
    if ($type === 'card' || $type === 'interactive' || $type === 'text-cta') {
        echo "  [Button IDs that will be sent to WhatsApp]:\n";
        $btnIdx = 0;
        foreach ($node['data'] as $k => $v) {
            if (strpos($k, 'btn-') === 0 && !empty(trim($v))) {
                $portIndex = str_replace('btn-', '', $k);
                $expectedOutputPort = 'output_' . ($portIndex + 2);
                echo "    flow_btn_{$nodeId}_{$portIndex} ('{$v}') -> should trigger $expectedOutputPort\n";
                $btnIdx++;
            }
        }
    }
    echo "\n";
}

// Also show webhook_debug.log last 20 lines
echo "=== webhook_debug.log (last 30 lines) ===\n";
$logFile = __DIR__ . '/webhook_debug.log';
if (file_exists($logFile) && filesize($logFile) > 0) {
    $lines = file($logFile);
    $last = array_slice($lines, -30);
    foreach ($last as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "(empty - no button clicks received yet)\n";
}

echo '</pre>';
?>
