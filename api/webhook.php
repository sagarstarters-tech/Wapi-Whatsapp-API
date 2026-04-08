<?php
/**
 * WAPI SaaS - WhatsApp Webhook Handler
 * Processes incoming messages, logs them, and triggers chatbot engine.
 */
require_once __DIR__ . '/../config/config.php';

// AT THE VERY TOP: DEBUG LOG
file_put_contents(__DIR__ . '/webhook_test.log', "[" . date('H:i:s') . "] METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

header('Content-Type: application/json');

// -------------------------------------------------------
// Webhook verification (GET request from Meta)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $verifyToken = $_GET['hub_verify_token'] ?? '';
    $challenge   = $_GET['hub_challenge'] ?? '';
    $mode        = $_GET['hub_mode'] ?? '';

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

// -------------------------------------------------------
// Process incoming webhook (POST request)
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input   = file_get_contents('php://input');
    $payload = json_decode($input, true);

    file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] PAYLOAD RECEIVED: " . substr($input, 0, 500) . "\n", FILE_APPEND);

    if (!$payload) {
        http_response_code(200); // Always 200 to Meta
        echo json_encode(['status' => 'ok']);
        exit;
    }

    try {
        // 1. Log via WhatsApp class (status updates + message logging)
        $wa = new WhatsApp();
        $wa->processWebhook($payload);

        // 2. Load chatbot engine
        require_once __DIR__ . '/../chatbot-engine/config.php';
        require_once __DIR__ . '/../chatbot-engine/functions.php';

        $entry         = $payload['entry'][0]['changes'][0]['value'] ?? [];
        $messages      = $entry['messages'] ?? [];
        $phoneNumberId = $entry['metadata']['phone_number_id'] ?? '';

        if (empty($messages) || empty($phoneNumberId)) {
            // Status update only — nothing to do for chatbot
            http_response_code(200);
            echo json_encode(['status' => 'ok']);
            exit;
        }

        // 3. Find the user account for this phone number ID
        $db      = Database::getInstance();
        $account = $db->fetch(
            "SELECT user_id, access_token FROM whatsapp_accounts WHERE phone_number_id = ? AND status = 'active' LIMIT 1",
            [$phoneNumberId]
        );

        if (!$account) {
            file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] No active account for phone_number_id: $phoneNumberId\n", FILE_APPEND);
            http_response_code(200);
            echo json_encode(['status' => 'ok']);
            exit;
        }

        $userId      = $account['user_id'];
        $accessToken = $account['access_token'];

        // Helper: parse drawflow nodes from JSON
        $getNodes = function($json) {
            $data = json_decode($json, true);
            return $data['drawflow']['Home']['data'] 
                ?? $data['drawflow']['home']['data'] 
                ?? [];
        };

        // 4. Process each incoming message
        foreach ($messages as $msg) {
            $from = $msg['from'] ?? '';
            $type = $msg['type'] ?? 'text';

            // -----------------------------------------------
            // A. Interactive button reply (chatbot flow nav)
            // -----------------------------------------------
            if ($type === 'interactive' && isset($msg['interactive']['button_reply'])) {
                $replyId = $msg['interactive']['button_reply']['id'] ?? '';
                file_put_contents(__DIR__ . '/webhook_debug.txt', "[" . date('Y-m-d H:i:s') . "] RAW BUTTON CLICK: '$replyId' (type: $type)\n", FILE_APPEND);

                if (strpos($replyId, 'flow_btn_') === 0) {
                    $lastUnderscore = strrpos($replyId, '_');
                    $portIndex = (int)substr($replyId, $lastUnderscore + 1);
                    $flowNodeId = substr($replyId, strlen('flow_btn_'), $lastUnderscore - strlen('flow_btn_'));
                    $outputName  = 'output_' . ($portIndex + 1);

                    file_put_contents(__DIR__ . '/webhook_debug.txt', "[" . date('Y-m-d H:i:s') . "] PARSED: nodeId='$flowNodeId', portIndex=$portIndex -> $outputName\n", FILE_APPEND);

                    $flow = $db->fetch(
                        "SELECT id, flow_json FROM chatbot_flows WHERE user_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1",
                        [$userId]
                    );

                    if ($flow && $flowNodeId) {
                        $nodes       = $getNodes($flow['flow_json']);
                        $nodeData    = $nodes[$flowNodeId] ?? null;
                        
                        if (!$nodeData) {
                             file_put_contents(__DIR__ . '/webhook_debug.txt', "[" . date('Y-m-d H:i:s') . "] ERROR: Node $flowNodeId not found in flow " . $flow['id'] . ". NodeIDs available: " . implode(', ', array_keys($nodes)) . "\n", FILE_APPEND);
                             continue;
                        }
                        
                        $connections = $nodeData['outputs'][$outputName]['connections'] ?? [];
                        
                        if (empty($connections)) {
                             $available = implode(', ', array_keys($nodeData['outputs'] ?? []));
                             file_put_contents(__DIR__ . '/webhook_debug.txt', "[" . date('Y-m-d H:i:s') . "] ERROR: No connections on $outputName for node $flowNodeId. Available ports: $available\n", FILE_APPEND);
                        } else {
                            $nextNodeId = $connections[0]['node'];
                            file_put_contents(__DIR__ . '/webhook_debug.txt', "[" . date('Y-m-d H:i:s') . "] SUCCESS: Routing to next node: $nextNodeId via $outputName\n", FILE_APPEND);
                            runFlow($from, $userId, $flow['id'], $nextNodeId, $phoneNumberId, $accessToken);
                        }
                    }
                }
                continue;
            }

            // -----------------------------------------------
            // B. Extract text body for keyword matching
            //    Works for text messages; non-text types get empty string
            // -----------------------------------------------
            $textBody = '';
            if ($type === 'text') {
                $textBody = strtolower(trim($msg['text']['body'] ?? ''));
            } elseif ($type === 'image') {
                $textBody = strtolower(trim($msg['image']['caption'] ?? ''));
            } elseif ($type === 'video') {
                $textBody = strtolower(trim($msg['video']['caption'] ?? ''));
            } elseif ($type === 'document') {
                $textBody = strtolower(trim($msg['document']['caption'] ?? ''));
            }
            // For sticker, audio, location, contacts etc. — textBody stays empty

            file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] Message from $from: type='$type', text='$textBody'\n", FILE_APPEND);

            // Load user's active flow (latest by default)
            $flow = $db->fetch(
                "SELECT id, flow_json FROM chatbot_flows WHERE user_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1",
                [$userId]
            );

            if (!$flow) {
                file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] No chatbot flow for user $userId\n", FILE_APPEND);
                continue;
            }

            $nodes      = $getNodes($flow['flow_json']);
            $isTrigger  = false;
            $startNodeId = null;

            // Find start node and check keyword match (only if we have text to match)
            if (!empty($textBody)) {
                foreach ($nodes as $nId => $nData) {
                    if ($nData['name'] !== 'start') continue;

                    $keywords = strtolower(trim($nData['data']['keywords'] ?? ''));

                    if (empty($keywords)) {
                        // No keywords set: match common greeting words
                        $defaults = ['hi', 'hello', 'start', 'menu', 'hey', 'demo', 'helo', 'hai'];
                        if (in_array($textBody, $defaults)) {
                            $isTrigger   = true;
                            $startNodeId = $nId;
                            break;
                        }
                    } else {
                        $matchType  = $nData['data']['match'] ?? 'exact';
                        $keywordArr = array_map('trim', explode(',', $keywords));
                        $keywordArr = array_map('strtolower', $keywordArr);

                        if ($matchType === 'contains') {
                            foreach ($keywordArr as $kw) {
                                if ($kw && strpos($textBody, $kw) !== false) {
                                    $isTrigger   = true;
                                    $startNodeId = $nId;
                                    break 2;
                                }
                            }
                        } else {
                            // Exact match
                            if (in_array($textBody, $keywordArr)) {
                                $isTrigger   = true;
                                $startNodeId = $nId;
                                break;
                            }
                        }
                    }
                }
            }

            if ($isTrigger) {
                // ---- CRITICAL FIX: Start node does not send a message.
                // We must find the node connected to the start node's output_1
                // and run from THERE, not from the start node itself.
                $nodes        = $getNodes($flow['flow_json']); // already fetched above
                $startNode    = $nodes[$startNodeId] ?? null;
                $firstConns   = $startNode['outputs']['output_1']['connections'] ?? [];

                if (!empty($firstConns)) {
                    $firstNodeId = $firstConns[0]['node'];
                    file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] Trigger matched! Starting at node: $firstNodeId\n", FILE_APPEND);
                    runFlow($from, $userId, $flow['id'], $firstNodeId, $phoneNumberId, $accessToken);
                } else {
                    // Start node has no connections — pass null so runFlow finds first node
                    file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] Trigger matched but start node has no connections.\n", FILE_APPEND);
                    runFlow($from, $userId, $flow['id'], null, $phoneNumberId, $accessToken);
                }
            } else {
                // Check active session — works for ALL message types (text, image, sticker, etc.)
                $session = getSession($from, $userId);
                if (
                    $session &&
                    ($session['state'] ?? '') === 'active' &&
                    ($session['flow_id'] ?? 0) == $flow['id'] &&
                    !empty($session['current_node_id'])
                ) {
                    file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] Continuing session (type=$type) at node: " . $session['current_node_id'] . "\n", FILE_APPEND);
                    runFlow($from, $userId, $flow['id'], $session['current_node_id'], $phoneNumberId, $accessToken);
                } else {
                    // No trigger, no active session
                    // For non-text messages without active session, auto-start the flow
                    if ($type !== 'text') {
                        // Find the first start node and auto-start flow
                        foreach ($nodes as $nId => $nData) {
                            if ($nData['name'] === 'start') {
                                $startNode  = $nData;
                                $firstConns = $startNode['outputs']['output_1']['connections'] ?? [];
                                if (!empty($firstConns)) {
                                    $firstNodeId = $firstConns[0]['node'];
                                    file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] Auto-starting flow for non-text message (type=$type) at node: $firstNodeId\n", FILE_APPEND);
                                    runFlow($from, $userId, $flow['id'], $firstNodeId, $phoneNumberId, $accessToken);
                                } else {
                                    runFlow($from, $userId, $flow['id'], null, $phoneNumberId, $accessToken);
                                }
                                break;
                            }
                        }
                    } else {
                        file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i:s') . "] No trigger match and no active session for '$textBody'\n", FILE_APPEND);
                    }
                }
            }
        }

    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../logs/webhook_root.log', "[" . date('H:i') . "] EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
    }

    // Always return 200 to Meta to prevent retries
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

// Non-GET/POST
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
