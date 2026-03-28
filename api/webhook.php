<?php
/**
 * WAPI SaaS - WhatsApp Webhook Handler
 * Receives delivery status updates and incoming messages from Meta
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Webhook verification (GET request from Meta)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $verifyToken = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    $mode = $_GET['hub_mode'] ?? '';

    $settings = new Settings();
    $expectedToken = $settings->get('webhook_verify_token', WEBHOOK_VERIFY_TOKEN);

    if ($mode === 'subscribe' && $verifyToken === $expectedToken) {
        http_response_code(200);
        echo $challenge;
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Verification failed']);
    }
    exit;
}

// Process webhook (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $payload = json_decode($input, true);

    // Debug: Log incoming payload
    file_put_contents(__DIR__ . '/../chatbot-engine/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] WEBHOOK RECEIVED: " . $input . "\n", FILE_APPEND);

    if ($payload) {
        try {
            $wa = new WhatsApp();
            $wa->processWebhook($payload);
            
            // --- CHATBOT ENGINE INTEGRATION ---
            require_once __DIR__ . '/../chatbot-engine/config.php';
            require_once __DIR__ . '/../chatbot-engine/functions.php';
            
            $entry = $payload['entry'][0]['changes'][0]['value'] ?? [];
            $messages = $entry['messages'] ?? [];
            $phoneNumberId = $entry['metadata']['phone_number_id'] ?? '';
            
            if (!empty($messages)) {
                $db = Database::getInstance();
                $account = $db->fetch("SELECT user_id, access_token FROM whatsapp_accounts WHERE phone_number_id = ? AND status = 'active'", [$phoneNumberId]);
                
                if ($account) {
                    $userId = $account['user_id'];
                    $accessToken = $account['access_token'];
                    
                    foreach ($messages as $msg) {
                        $from = $msg['from'] ?? '';
                        $type = $msg['type'] ?? 'text';
                        file_put_contents(__DIR__ . '/../chatbot-engine/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Processing msg from $from type $type\n", FILE_APPEND);
                        
                        // 1. Handle Button Replies (Interactive)
                        if ($type === 'interactive' && isset($msg['interactive']['button_reply'])) {
                            $replyId = $msg['interactive']['button_reply']['id'] ?? '';
                            if (strpos($replyId, 'flow_btn_') === 0) {
                                $parts = explode('_', $replyId);
                                $flowNodeId = $parts[2]; $portIndex = (int)$parts[3];
                                $flow = $db->fetch("SELECT id, flow_json FROM chatbot_flows WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$userId]);
                                if ($flow) {
                                    $flowData = json_decode($flow['flow_json'], true);
                                    $nodes = $flowData['drawflow']['Home']['data'] ?? [];
                                    $connections = $nodes[$flowNodeId]['outputs']['output_' . ($portIndex + 1)]['connections'] ?? [];
                                    if (!empty($connections)) {
                                        runFlow($from, $userId, $flow['id'], $connections[0]['node'], $phoneNumberId, $accessToken);
                                    }
                                }
                            }
                        } 
                        // 2. Handle Text Messages (Check Triggers or Session)
                        elseif ($type === 'text') {
                            $textBody = strtolower(trim($msg['text']['body'] ?? ''));
                            $flow = $db->fetch("SELECT id, flow_json FROM chatbot_flows WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$userId]);
                            
                            if ($flow) {
                                $flowData = json_decode($flow['flow_json'], true);
                                $nodes = $flowData['drawflow']['Home']['data'] ?? [];
                                $isTrigger = false; $startNodeId = null;
                                
                                foreach ($nodes as $nId => $nData) {
                                    if ($nData['name'] === 'start') {
                                        $keywords = strtolower($nData['data']['keywords'] ?? '');
                                        if (empty($keywords)) {
                                            // Default triggers if empty
                                            if (in_array($textBody, ['hi', 'hello', 'start', 'menu', 'hey'])) {
                                                $isTrigger = true; $startNodeId = $nId; break;
                                            }
                                        } else {
                                            $keywordArr = array_map('trim', explode(',', $keywords));
                                            if (in_array($textBody, $keywordArr)) {
                                                $isTrigger = true; $startNodeId = $nId; break;
                                            }
                                        }
                                    }
                                }
                                
                                if ($isTrigger) {
                                    file_put_contents(__DIR__ . '/../chatbot-engine/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Trigger matched node $startNodeId\n", FILE_APPEND);
                                    runFlow($from, $userId, $flow['id'], $startNodeId, $phoneNumberId, $accessToken);
                                } else {
                                    $session = getSession($from, $userId);
                                    if ($session && $session['state'] === 'active' && $session['flow_id'] == $flow['id']) {
                                        file_put_contents(__DIR__ . '/../chatbot-engine/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Continuing session at node " . $session['current_node_id'] . "\n", FILE_APPEND);
                                        runFlow($from, $userId, $flow['id'], $session['current_node_id'], $phoneNumberId, $accessToken);
                                    } else {
                                        // Auto-start if no keywords were defined (Welcome message)
                                        file_put_contents(__DIR__ . '/../chatbot-engine/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] No trigger/session - Auto-starting flow\n", FILE_APPEND);
                                        runFlow($from, $userId, $flow['id'], null, $phoneNumberId, $accessToken);
                                    }
                                }
                            }
                        }
                    }
                } else {
                     file_put_contents(__DIR__ . '/../chatbot-engine/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] NO ACTIVE ACCOUNT FOUND for PhoneID: $phoneNumberId\n", FILE_APPEND);
                }
            }
        } catch (Exception $e) {
            file_put_contents(__DIR__ . '/../chatbot-engine/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] WEBHOOK EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
