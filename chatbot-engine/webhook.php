<?php
/**
 * WAPI SaaS - WhatsApp Webhook (V2 Full Integration)
 * Processes incoming messages and routes them through the visual flow engine.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/functions.php';

// 1. Webhook Verification (GET method)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? null;
    $token = $_GET['hub_verify_token'] ?? null;
    $challenge = $_GET['hub_challenge'] ?? null;

    if ($mode === 'subscribe' && $token === WEBHOOK_VERIFY_TOKEN) {
        http_response_code(200);
        echo $challenge;
        die();
    } else {
        http_response_code(403);
        die('Forbidden');
    }
}

// 2. Incoming Messages Handler (POST method)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        die();
    }

    $entry = $data['entry'][0]['changes'][0]['value'] ?? [];
    $messages = $entry['messages'] ?? [];
    $phoneNumberId = $entry['metadata']['phone_number_id'] ?? '';

    // Find User / Account associated with this phone number
    $db = Database::getInstance();
    $account = $db->fetch("SELECT user_id FROM whatsapp_accounts WHERE phone_number_id = ? AND status = 'active'", [$phoneNumberId]);
    if (!$account) {
        error_log("No active WhatsApp account found for ID: " . $phoneNumberId);
        die();
    }
    $userId = $account['user_id'];

    if (!empty($messages)) {
        foreach ($messages as $msg) {
            $from = $msg['from'] ?? '';
            $type = $msg['type'] ?? 'text';

            // Handle Interactive Replied (Flow Buttons)
            if ($type === 'interactive' && isset($msg['interactive']['button_reply'])) {
                $replyId = $msg['interactive']['button_reply']['id'] ?? '';
                
                // Expected Format: flow_btn_{nodeId}_{portIndex}
                if (strpos($replyId, 'flow_btn_') === 0) {
                    $parts = explode('_', $replyId);
                    $flowNodeId = $parts[2];
                    $portIndex = (int)$parts[3];

                    // Find the user's master flow (currently selecting first one)
                    $flow = $db->fetch("SELECT id, flow_json FROM chatbot_flows WHERE user_id = ? ORDER BY id ASC LIMIT 1", [$userId]);
                    if (!$flow) continue;

                    $flowData = json_decode($flow['flow_json'], true);
                    $nodes = $flowData['drawflow']['Home']['data'] ?? [];
                    
                    // Look for connections on the specific output port
                    $outputName = 'output_' . ($portIndex + 1);
                    $connections = $nodes[$flowNodeId]['outputs'][$outputName]['connections'] ?? [];

                    if (!empty($connections)) {
                        $nextNodeId = $connections[0]['node'];
                        runFlow($from, $userId, $flow['id'], $nextNodeId);
                    }
                }
            } 
            // Handle Incoming Text (Keywords / Restart)
            elseif ($type === 'text') {
                $textBody = strtolower(trim($msg['text']['body'] ?? ''));

                    // Find User's active flow
                    $flow = $db->fetch("SELECT id FROM chatbot_flows WHERE user_id = ? ORDER BY id ASC LIMIT 1", [$userId]);
                    if (!$flow) continue;

                    if ($textBody === 'hi' || $textBody === 'start' || $textBody === 'menu') {
                        // Reset session and start from root
                        runFlow($from, $userId, $flow['id'], null);
                    } else {
                        // Resume from current session if exists
                        $session = getSession($from);
                        if ($session && $session['state'] === 'active' && $session['flow_id'] == $flow['id']) {
                            // Find connections of the current node
                            $flowData = $db->fetch("SELECT flow_json FROM chatbot_flows WHERE id = ?", [$session['flow_id']]);
                            $data = json_decode($flowData['flow_json'], true);
                            $nodes = $data['drawflow']['Home']['data'] ?? [];
                            
                            $currNode = $nodes[$session['current_node_id']] ?? null;
                            if ($currNode && $currNode['name'] === 'interactive') {
                                // Ignore text if waiting for button click, OR handle keyword matching
                                sendText($from, "Please click one of the buttons above to proceed. Or type 'start' to reset.");
                            } else {
                                runFlow($from, $userId, $flow['id'], $session['current_node_id']);
                            }
                        } else {
                            // Logic for unknown keywords
                            runFlow($from, $userId, $flow['id'], null);
                        }
                    }
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);
}
?>
