<?php
/**
 * WAPI SaaS - WhatsApp Chatbot Core Functions (V2 Refactored)
 * Dynamic Flow Engine and Messaging Helpers.
 */

require_once __DIR__ . '/config.php';

/**
 * 1. Meta API Request Helper (using cURL)
 */
function sendRequest($payload) {
    if (!$payload) return false;

    // Optional: Log outgoing requests for debugging
    error_log("Sending Request: " . json_encode($payload));

    $url = "https://graph.facebook.com/" . WHATSAPP_API_VERSION . "/" . PHONE_NUMBER_ID . "/messages";
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WHATSAPP_API_TOKEN
        ],
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Meta API CONNECTION Error: " . $error);
        return false;
    }

    $result = json_decode($response, true);
    if ($httpCode >= 400 && isset($result['error'])) {
        error_log("Meta API Response Error (HTTP " . $httpCode . "): " . json_encode($result['error']));
        return false;
    }

    return $result;
}

/**
 * 2. Specialized Messaging Helpers
 */
function sendText($phone, $message) {
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $phone,
        'type' => 'text',
        'text' => ['preview_url' => false, 'body' => $message]
    ];
    return sendRequest($payload);
}

function sendImage($phone, $imageUrl, $caption = '') {
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'image',
        'image' => ['link' => $imageUrl, 'caption' => $caption]
    ];
    return sendRequest($payload);
}

function sendAudio($phone, $audioUrl) {
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'audio',
        'audio' => ['link' => $audioUrl]
    ];
    return sendRequest($payload);
}

function sendVideo($phone, $videoUrl, $caption = '') {
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'video',
        'video' => ['link' => $videoUrl, 'caption' => $caption]
    ];
    return sendRequest($payload);
}

function sendDocument($phone, $docUrl, $filename = '') {
    $payload = [
        'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $phone, 'type' => 'document',
        'document' => ['link' => $docUrl, 'filename' => $filename]
    ];
    return sendRequest($payload);
}

function sendButtons($phone, $text, $buttonsData, $nodeId) {
    $buttons = [];
    foreach ($buttonsData as $key => $label) {
        if (count($buttons) >= 3) break;
        // The ID will be formatted as "flow_node_ID_PORT" (e.g., flow_node_15_output_1)
        $portIndex = str_replace('btn-', '', $key); // from btn-0, btn-1 etc
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
    return sendRequest($payload);
}

/**
 * 3. Dynamic Flow Engine (JSON Parser)
 */
function runFlow($phone, $userId, $flowId, $nodeId = null) {
    $db = Database::getInstance();

    // 1. Fetch the Flow JSON
    $flow = $db->fetch("SELECT flow_json FROM chatbot_flows WHERE id = ?", [$flowId]);
    if (!$flow) {
        error_log("Flow not found for ID: " . $flowId);
        return;
    }

    $data = json_decode($flow['flow_json'], true);
    $nodes = $data['drawflow']['Home']['data'] ?? [];

    // 2. Identify Current Node
    if ($nodeId === null) {
        // Find the "start" node (usually node ID 1 or the one with no inputs)
        foreach ($nodes as $nId => $nData) {
            if (empty($nData['inputs']['input_1']['connections'])) {
                $nodeId = $nId;
                break;
            }
        }
        // Fallback to node 1 if no "headless" node is found
        if ($nodeId === null) $nodeId = 1;
    }

    if (!isset($nodes[$nodeId])) {
        error_log("Node ID not found: " . $nodeId);
        return;
    }

    $currentNode = $nodes[$nodeId];
    $nodeType = $currentNode['name'];
    $nodeData = $currentNode['data'];

    // Update Session State
    setSession($phone, $flowId, $nodeId, 'active');

    // 3. Execute Node Action
    $isInteractive = false;
    
    switch ($nodeType) {
        case 'text':
            sendText($phone, $nodeData['text'] ?? '');
            break;
            
        case 'image':
            sendImage($phone, $nodeData['image-url'] ?? '', $nodeData['caption'] ?? '');
            break;

        case 'interactive':
            // Extraction of buttons: find all keys starting with btn-
            $buttonsData = [];
            foreach ($nodeData as $key => $val) {
                if (strpos($key, 'btn-') === 0) $buttonsData[$key] = $val;
            }
            sendButtons($phone, $nodeData['prompt'] ?? 'Select an option:', $buttonsData, $nodeId);
            $isInteractive = true; // Wait for user reply
            break;
            
        case 'audio':
            sendAudio($phone, $nodeData['audio-url'] ?? '');
            break;

        case 'video':
            sendVideo($phone, $nodeData['video-url'] ?? '', $nodeData['caption'] ?? '');
            break;

        case 'file':
            sendDocument($phone, $nodeData['file-url'] ?? '', $nodeData['filename'] ?? 'document');
            break;

        case 'delay':
            $delay = max(1, (int)($nodeData['delay-seconds'] ?? 2));
            sleep($delay);
            break;
    }

    // 4. Move to Next Node (if not interactive)
    if (!$isInteractive) {
        $connections = $currentNode['outputs']['output_1']['connections'] ?? [];
        if (!empty($connections)) {
            $nextNodeId = $connections[0]['node'];
            runFlow($phone, $userId, $flowId, $nextNodeId); // Recursion
        } else {
            // End of flow
            setSession($phone, $flowId, $nodeId, 'finished');
        }
    }
}

/**
 * 4. Improved Session Helpers
 */
function setSession($phone, $flowId, $nodeId, $state) {
    $db = Database::getInstance();
    $sql = "INSERT INTO chatbot_sessions (phone, flow_id, current_node_id, state) VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE flow_id = ?, current_node_id = ?, state = ?";
    return $db->query($sql, [$phone, $flowId, $nodeId, $state, $flowId, $nodeId, $state]);
}

function getSession($phone) {
    $db = Database::getInstance();
    return $db->fetch("SELECT * FROM chatbot_sessions WHERE phone = ?", [$phone]);
}
?>
