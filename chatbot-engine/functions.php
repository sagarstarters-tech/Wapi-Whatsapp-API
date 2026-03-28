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
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'image',
        'image' => ['link' => $imageUrl, 'caption' => $caption]
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendAudio($phone, $audioUrl, $phoneId = null, $token = null) {
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'audio',
        'audio' => ['link' => $audioUrl]
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendVideo($phone, $videoUrl, $caption = '', $phoneId = null, $token = null) {
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'video',
        'video' => ['link' => $videoUrl, 'caption' => $caption]
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendDocument($phone, $docUrl, $filename = '', $phoneId = null, $token = null) {
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'document',
        'document' => ['link' => $docUrl, 'filename' => $filename]
    ];
    return sendRequest($payload, $phoneId, $token);
}

function sendButtons($phone, $text, $buttonsData, $nodeId, $phoneId = null, $token = null) {
    $buttons = [];
    foreach ($buttonsData as $key => $label) {
        if (count($buttons) >= 3) break;
        $portIndex = str_replace('btn-', '', $key); 
        $buttons[] = [
            'type' => 'reply',
            'reply' => ['id' => "flow_btn_{$nodeId}_{$portIndex}", 'title' => mb_substr($label, 0, 20)]
        ];
    }

    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'interactive',
        'interactive' => [
            'type' => 'button', 'body' => ['text' => $text], 'action' => ['buttons' => $buttons]
        ]
    ];
    return sendRequest($payload, $phoneId, $token);
}

/**
 * 3. Dynamic Flow Engine (JSON Parser)
 */
function runFlow($phone, $userId, $flowId, $nodeId = null, $phoneId = null, $token = null) {
    $db = Database::getInstance();

    // 1. Fetch the Flow JSON
    $flow = $db->fetch("SELECT flow_json FROM chatbot_flows WHERE id = ?", [$flowId]);
    if (!$flow) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Flow not found: $flowId\n", FILE_APPEND);
        return;
    }

    $data = json_decode($flow['flow_json'], true);
    $nodes = $data['drawflow']['Home']['data'] ?? [];

    // 2. Identify Current Node (if null, find a start node)
    if ($nodeId === null) {
        foreach ($nodes as $nId => $nData) {
            // A node is a start point if it has no inputs OR its inputs are empty
            $hasInputs = !empty($nData['inputs']);
            if (!$hasInputs || (isset($nData['inputs']['input_1']) && empty($nData['inputs']['input_1']['connections']))) {
                if ($nData['name'] === 'start') {
                    $nodeId = $nId;
                    break;
                }
            }
        }
        // Fallback to first node if no explicit start found
        if ($nodeId === null && !empty($nodes)) {
            $nodeIds = array_keys($nodes);
            $nodeId = $nodeIds[0];
        }
    }

    if ($nodeId === null || !isset($nodes[$nodeId])) {
        file_put_contents(__DIR__ . '/webhook_debug.log', "[" . date('Y-m-d H:i:s') . "] Target node $nodeId not found in flow\n", FILE_APPEND);
        return;
    }

    $currentNode = $nodes[$nodeId];
    $nodeType = $currentNode['name'];
    $nodeData = $currentNode['data'];

    // Update Session State
    setSession($phone, $userId, $flowId, $nodeId, 'active');

    // 3. Execute Node Action
    $isInteractive = false;
    
    switch ($nodeType) {
        case 'text':
            $res = sendText($phone, $nodeData['text'] ?? '', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'text', $nodeData['text'] ?? '', $res);
            break;
            
        case 'image':
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
            $res = sendAudio($phone, $nodeData['audio-url'] ?? '', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'audio', 'Audio', $res, $nodeData['audio-url'] ?? '');
            break;
            
        case 'video':
            $res = sendVideo($phone, $nodeData['video-url'] ?? '', $nodeData['caption'] ?? '', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'video', 'Video', $res, $nodeData['video-url'] ?? '');
            break;
            
        case 'file':
            $res = sendDocument($phone, $nodeData['file-url'] ?? '', $nodeData['filename'] ?? 'document', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'document', 'Document', $res, $nodeData['file-url'] ?? '');
            break;
            
        case 'cta':
            $res = sendText($phone, $nodeData['message'] ?? 'Click the link:', $phoneId, $token);
            logChatbotMessage($userId, $phone, 'text', $nodeData['message'] ?? 'CTA', $res);
            break;

        case 'delay':
            sleep(max(1, (int)($nodeData['delay-seconds'] ?? 2)));
            break;
    }

    // 4. Move to Next Node (if not interactive)
    if (!$isInteractive) {
        $connections = $currentNode['outputs']['output_1']['connections'] ?? [];
        if (!empty($connections)) {
            $nextNodeId = $connections[0]['node'];
            runFlow($phone, $userId, $flowId, $nextNodeId, $phoneId, $token); 
        } else {
            setSession($phone, $userId, $flowId, $nodeId, 'finished');
        }
    }
}

/**
 * 4. Improved Session Helpers
 */
function setSession($phone, $userId, $flowId, $nodeId, $state) {
    $db = Database::getInstance();
    $sql = "INSERT INTO chatbot_sessions (phone, user_id, flow_id, current_node_id, state) VALUES (?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE flow_id = ?, current_node_id = ?, state = ?";
    return $db->query($sql, [$phone, $userId, $flowId, $nodeId, $state, $flowId, $nodeId, $state]);
}

function getSession($phone, $userId) {
    $db = Database::getInstance();
    return $db->fetch("SELECT * FROM chatbot_sessions WHERE phone = ? AND user_id = ?", [$phone, $userId]);
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
