<?php
// Mock Incoming Message to Test Chatbot Logic
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/chatbot-engine/functions.php';

header('Content-Type: text/plain');
echo "--- Chatbot Mock Test ---\n";

$db = Database::getInstance();

// SET THESE FOR YOUR TEST
$testPhone = '919876543210'; // A dummy or real phone
$testUser = 2; // User ID
$testPhoneID = '1234567890'; // Found from Meta or DB
$token = 'YOUR_MOCK_TOKEN'; // Optional

echo "Identifying Latest Flow for User $testUser...\n";
$flow = $db->fetch("SELECT id, flow_json FROM chatbot_flows WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$testUser]);

if (!$flow) {
    die("ERROR: No flows found in DB. Please save a flow in the builder first!\n");
}

echo "Flow Found: ID {$flow['id']}\n";
$data = json_decode($flow['flow_json'], true);
$nodes = $data['drawflow']['Home']['data'] ?? [];
echo "Nodes Found: " . count($nodes) . "\n";

echo "\nExecuting runFlow for 'hi'...\n";
// Manually run the trigger logic
$msgText = 'hi';
$startNodeId = null;

foreach ($nodes as $nId => $nData) {
    if ($nData['name'] === 'start') {
        $startNodeId = $nId;
        echo "Starting from node $nId\n";
        break;
    }
}

if ($startNodeId) {
    // Note: This will actually call Meta if runFlow calls sendRequest!
    // But since $webhook_debug.log will be updated, we can see path.
    // I will mock sendRequest in memory for 1 second by defining a constant? No.
    
    // Just run it and see logs
    runFlow($testPhone, $testUser, $flow['id'], $startNodeId, 'mock_phone_id', 'mock_token');
    echo "Done! Check chatbot-engine/webhook_debug.log\n";
} else {
    echo "No start node found.\n";
}
?>
