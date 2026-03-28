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
    $account = $db->fetch("SELECT user_id, access_token FROM whatsapp_accounts WHERE phone_number_id = ? AND status = 'active'", [$phoneNumberId]);
    
    if (!$account) {
        error_log("No active WhatsApp account found for ID: " . $phoneNumberId);
        die();
    }
    
    $userId = $account['user_id'];
    $accessToken = $account['access_token'];

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
                        runFlow($from, $userId, $flow['id'], $nextNodeId, $phoneNumberId, $accessToken);
                    }
                }
            } 
            // Handle Incoming Text (Keywords / Restart / Continue)
            elseif ($type === 'text') {
                $textBody = strtolower(trim($msg['text']['body'] ?? ''));

                // Find User's active flow
                $flow = $db->fetch("SELECT id, flow_json FROM chatbot_flows WHERE user_id = ? ORDER BY id ASC LIMIT 1", [$userId]);
                if (!$flow) continue;

                $flowData = json_decode($flow['flow_json'], true);
                $nodes = $flowData['drawflow']['Home']['data'] ?? [];
                
                // 1. Check if input matches any "Start" node keywords
                $startNodeId = null;
                $isTrigger = false;
                
                foreach ($nodes as $nId => $nData) {
                    if ($nData['name'] === 'start') {
                        $keywords = strtolower($nData['data']['keywords'] ?? '');
                        $keywordArr = array_map('trim', explode(',', $keywords));
                        
                        // If keywords are empty, 'hi'/'start' are defaults, otherwise match the list
                        if (empty($keywords) && in_array($textBody, ['hi', 'hello', 'start', 'menu'])) {
                            $isTrigger = true;
                            $startNodeId = $nId;
                        } elseif (in_array($textBody, $keywordArr)) {
                            $isTrigger = true;
                            $startNodeId = $nId;
                        }
                        if ($isTrigger) break;
                    }
                }

                if ($isTrigger) {
                    // Start or Reset session
                    runFlow($from, $userId, $flow['id'], $startNodeId, $phoneNumberId, $accessToken);
                } else {
                    // 2. Resume from current session if exists
                    $session = getSession($from);
                    if ($session && $session['state'] === 'active' && $session['flow_id'] == $flow['id']) {
                         runFlow($from, $userId, $flow['id'], $session['current_node_id'], $phoneNumberId, $accessToken);
                    } else {
                         // Default fallback: Trigger flow from start if no active session
                         runFlow($from, $userId, $flow['id'], null, $phoneNumberId, $accessToken);
                    }
                }
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);
}
?>
