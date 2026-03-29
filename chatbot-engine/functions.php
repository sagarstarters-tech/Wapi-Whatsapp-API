<?php
/**
 * WAPI SaaS - WhatsApp Chatbot Core Functions (V2 Refactored)
 * Dynamic Flow Engine and Messaging Helpers.
 */

require_once __DIR__ . '/config.php';

/**
 * 1. Meta API Request Helper (using cURL)
 */
function sendRequest($payload, $phoneId = null, $token = null) {
    if (!$payload) return false;

    // Use parameters or fall back to constants
    $targetPhoneId = $phoneId ?? (defined('PHONE_NUMBER_ID') ? PHONE_NUMBER_ID : '');
    $targetToken = $token ?? (defined('WHATSAPP_API_TOKEN') ? WHATSAPP_API_TOKEN : '');

    // Debug logging
    file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Sending Payload: " . json_encode($payload) . "\n", FILE_APPEND);

    $url = "https://graph.facebook.com/" . WHATSAPP_API_VERSION . "/" . $targetPhoneId . "/messages";
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $targetToken
        ],
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] cURL Error: " . $error . "\n", FILE_APPEND);
        return false;
    }

    $result = json_decode($response, true);
    if ($httpCode >= 400) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Meta Error (HTTP $httpCode): " . json_encode($result) . "\n", FILE_APPEND);
        return false;
    }

    return $result;
}

/**
 * 2. Specialized Messaging Helpers
 */
function sendText($phone, $message, $phoneId = null, $token = null) {
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $phone,
        'type' => 'text',
        'text' => ['preview_url' => false, 'body' => $message]
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendImage($phone, $imageUrl, $caption = '', $phoneId = null, $token = null) {
    if (empty($imageUrl)) return false;
    $media = ['link' => $imageUrl];
    if (trim($caption) !== '') {
        $media['caption'] = $caption;
    }
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'image',
        'image' => $media
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendAudio($phone, $audioUrl, $phoneId = null, $token = null) {
    if (empty($audioUrl)) return false;
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'audio',
        'audio' => ['link' => $audioUrl]
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendVideo($phone, $videoUrl, $caption = '', $phoneId = null, $token = null) {
    if (empty($videoUrl)) return false;
    $media = ['link' => $videoUrl];
    if (trim($caption) !== '') {
        $media['caption'] = $caption;
    }
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'video',
        'video' => $media
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendDocument($phone, $docUrl, $filename = '', $phoneId = null, $token = null) {
    if (empty($docUrl)) return false;
    $media = ['link' => $docUrl];
    if (trim($filename) !== '') {
        $media['filename'] = $filename;
    }
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'document',
        'document' => $media
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendButtons($phone, $text, $buttonsData, $nodeId, $phoneId = null, $token = null) {
    if (empty(trim($text)) || empty($buttonsData)) return false;
    $buttons = [];
    foreach ($buttonsData as $key => $label) {
        if (trim($label) === '') continue;
        if (count($buttons) >= 3) break;
        $portIndex = str_replace('btn-', '', $key); 
        $buttons[] = [
            'type' => 'reply',
            'reply' => ['id' => "flow_btn_{$nodeId}_{$portIndex}", 'title' => mb_substr(trim($label), 0, 20)]
        ];
    }

    if (empty($buttons)) return false;

    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'interactive',
        'interactive' => [
            'type' => 'button', 'body' => ['text' => $text], 'action' => ['buttons' => $buttons]
        ]
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendCtaUrl($phone, $text, $btnText, $url, $phoneId = null, $token = null) {
    if (empty(trim($text)) || empty(trim($btnText)) || empty(trim($url))) return false;
    
    $payload = [
        'messaging_product' => 'whatsapp', 
        'recipient_type'    => 'individual', 
        'to'                => $phone, 
        'type'              => 'interactive',
        'interactive'       => [
            'type'   => 'cta_url',
            'body'   => ['text' => $text],
            'action' => [
                'name' => 'cta_url',
                'parameters' => [
                    'display_text' => mb_substr(trim($btnText), 0, 20),
                    'url'          => trim($url)
                ]
            ]
        ]
    ];
    return sendRequest($payload, $phoneId, $token);
}

/**
 * 3. Dynamic Flow Engine (JSON Parser)
 */
function runFlow($phone, $userId, $flowId, $nodeId = null, $phoneId = null, $token = null) {
    $db = Database::getInstance();

    file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] runFlow: phone=$phone, userId=$userId, flowId=$flowId, nodeId=$nodeId\n", FILE_APPEND);

    // 1. Fetch the Flow JSON
    $flow = $db->fetch("SELECT flow_json FROM chatbot_flows WHERE id = ?", [$flowId]);
    if (!$flow) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Flow not found: $flowId\n", FILE_APPEND);
        return;
    }

    $data = json_decode($flow['flow_json'], true);
    $nodes = $data['drawflow']['Home']['data'] ?? $data['drawflow']['home']['data'] ?? [];

    if (empty($nodes)) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Flow $flowId has no nodes!\n", FILE_APPEND);
        return;
    }

    // 2. Identify Current Node (if null, find a start node)
    if ($nodeId === null) {
        foreach ($nodes as $nId => $nData) {
            if ($nData['name'] === 'start') {
                $nodeId = $nId;
                break;
            }
        }
        // Fallback to first node if no explicit start found
        if ($nodeId === null && !empty($nodes)) {
            $nodeIds = array_keys($nodes);
            $nodeId = $nodeIds[0];
        }
    }

    if ($nodeId === null || !isset($nodes[$nodeId])) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Target node $nodeId not found in flow $flowId\n", FILE_APPEND);
        return;
    }

    $currentNode = $nodes[$nodeId];
    $nodeType = $currentNode['name'];
    $nodeData = $currentNode['data'];

    file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Executing node $nodeId (type: $nodeType), data: " . json_encode($nodeData) . "\n", FILE_APPEND);

    // Update Session State
    setSession($phone, $userId, $flowId, $nodeId, 'active');

    // 3. Execute Node Action
    $isInteractive = false;
    
    switch ($nodeType) {
        case 'start':
            // Start node sends no message — immediately follow to the next connected node
            file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Start node triggered, following connection...\n", FILE_APPEND);
            
            // Apply configured start node delay
            $delaySecs = (int)($nodeData['delay'] ?? 0);
            if ($delaySecs > 0 && $delaySecs <= 60) {
                sleep($delaySecs);
            }

            $startConns = $currentNode['outputs']['output_1']['connections'] ?? [];
            if (!empty($startConns)) {
                $nextNodeId = $startConns[0]['node'];
                runFlow($phone, $userId, $flowId, $nextNodeId, $phoneId, $token);
            }
            return; // exit this call — the recursive call handles everything

        case 'text':
            $textMsg = $nodeData['text'] ?? '';
            if (empty($textMsg)) {
                file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] WARNING: text node $nodeId has empty message!\n", FILE_APPEND);
            }
            $delaySecs = (int)($nodeData['delay'] ?? 0);
            if ($delaySecs > 0 && $delaySecs <= 60) {
                sleep($delaySecs);
            }
            $res = sendText($phone, $textMsg, $phoneId, $token);
            logChatbotMessage($userId, $phone, 'text', $textMsg, $res);
            break;
            
        case 'image':
            $delaySecs = (int)($nodeData['delay'] ?? 0);
            if ($delaySecs > 0 && $delaySecs <= 60) {
                sleep($delaySecs);
            }
            $res = sendImage($phone, $nodeData['image-url'] ?? '', $nodeData['caption'] ?? '', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'image', 'Image', $res, $nodeData['image-url'] ?? '');
            break;
            
        case 'interactive':
            $buttonsData = [];
            foreach ($nodeData as $key => $val) {
                if (strpos($key, 'btn-') === 0) $buttonsData[$key] = $val;
            }
            $res = sendButtons($phone, $nodeData['prompt'] ?? 'Select an option:', $buttonsData, $nodeId, $phoneId, $token);
            logChatbotMessage($userId, $phone, 'interactive', $nodeData['prompt'] ?? 'Interactive Buttons', $res);
            $isInteractive = true;
            break;
            
        case 'audio':
            $delaySecs = (int)($nodeData['delay'] ?? 0);
            if ($delaySecs > 0 && $delaySecs <= 60) {
                sleep($delaySecs);
            }
            $res = sendAudio($phone, $nodeData['audio-url'] ?? '', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'audio', 'Audio', $res, $nodeData['audio-url'] ?? '');
            break;
            
        case 'video':
            $delaySecs = (int)($nodeData['delay'] ?? 0);
            if ($delaySecs > 0 && $delaySecs <= 60) {
                sleep($delaySecs);
            }
            $res = sendVideo($phone, $nodeData['video-url'] ?? '', $nodeData['caption'] ?? '', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'video', 'Video', $res, $nodeData['video-url'] ?? '');
            break;
            
        case 'file':
            $res = sendDocument($phone, $nodeData['file-url'] ?? '', $nodeData['filename'] ?? 'document', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'document', 'Document', $res, $nodeData['file-url'] ?? '');
            break;
            
        case 'cta':
            $res = sendCtaUrl($phone, $nodeData['text'] ?? '', $nodeData['btnText'] ?? '', $nodeData['url'] ?? '', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'interactive', $nodeData['text'] ?? 'CTA Link', $res);
            break;

        case 'delay':
            $secs = max(1, min(10, (int)($nodeData['delay-seconds'] ?? 2)));
            sleep($secs);
            break;

        default:
            file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Unknown node type: $nodeType\n", FILE_APPEND);
    }

    // 4. Move to Next Node (if not interactive)
    if (!$isInteractive) {
        $outputs = $currentNode['outputs'] ?? [];
        $foundNext = false;
        
        foreach ($outputs as $outputKey => $outputData) {
            $connections = $outputData['connections'] ?? [];
            foreach ($connections as $conn) {
                $nextNodeId = $conn['node'];
                $foundNext = true;
                
                // Add a forced 1-second delay between sequential nodes to guarantee WhatsApp API delivery order (Media takes longer than Text)
                sleep(1);
                
                file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Moving to next node via $outputKey: $nextNodeId\n", FILE_APPEND);
                runFlow($phone, $userId, $flowId, $nextNodeId, $phoneId, $token); 
            }
        }
        
        if (!$foundNext) {
            setSession($phone, $userId, $flowId, $nodeId, 'finished');
            file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Flow finished at node $nodeId\n", FILE_APPEND);
        }
    }
}

/**
 * 4. Improved Session Helpers
 */
function setSession($phone, $userId, $flowId, $nodeId, $state) {
    $db = Database::getInstance();
    // Use INSERT ... ON DUPLICATE KEY UPDATE with composite unique key (phone, user_id)
    $sql = "INSERT INTO chatbot_sessions (phone, user_id, flow_id, current_node_id, state) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                flow_id = VALUES(flow_id), 
                current_node_id = VALUES(current_node_id), 
                state = VALUES(state),
                updated_at = CURRENT_TIMESTAMP";
    try {
        return $db->query($sql, [$phone, $userId, $flowId, $nodeId, $state]);
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] setSession Error: " . $e->getMessage() . "\n", FILE_APPEND);
        // Auto-fix: try creating the table with correct schema if missing
        try {
            $db->query(
                "UPDATE chatbot_sessions SET flow_id=?, current_node_id=?, state=?, updated_at=NOW() WHERE phone=? AND user_id=?",
                [$flowId, $nodeId, $state, $phone, $userId]
            );
        } catch (Exception $e2) {
            file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] setSession Fallback Error: " . $e2->getMessage() . "\n", FILE_APPEND);
        }
    }
}

function getSession($phone, $userId) {
    $db = Database::getInstance();
    try {
        return $db->fetch(
            "SELECT * FROM chatbot_sessions WHERE phone = ? AND user_id = ? LIMIT 1",
            [$phone, $userId]
        );
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] getSession Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return null;
    }
}

/**
 * Log automated chatbot message to the dashboard messages table
 */
function logChatbotMessage($userId, $to, $type, $content, $apiResponse, $mediaUrl = null) {
    if (!$apiResponse || !isset($apiResponse['messages'][0]['id'])) return;
    
    $db = Database::getInstance();
    try {
        $db->insert('messages', [
            'user_id' => $userId,
            'message_id' => $apiResponse['messages'][0]['id'],
            'to_number' => $to,
            'type' => $type,
            'content' => $content,
            'media_url' => $mediaUrl,
            'status' => 'sent',
            'direction' => 'outbound'
        ]);
        
        // Deduct from credits table
        $db->query("UPDATE credits SET used_credits = used_credits + 1 WHERE user_id = ?", [$userId]);
        
        // Activity log
        $db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => 'chatbot_reply',
            'description' => "Chatbot replied to $to (" . ucfirst($type) . ")",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'system'
        ]);
        
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Log Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>
