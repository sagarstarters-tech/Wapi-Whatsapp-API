<?php
/**
 * WhatsApp Chatbot Core Functions
 * Handles all logic for sending messages via Meta Cloud API and managing user states.
 */

require_once __DIR__ . '/config.php';

/**
 * 1. Meta API Request Helper (using cURL)
 */
function sendRequest($payload) {
    if (!$payload) return false;

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
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("Meta API CONNECTION Error: " . $error);
        return false;
    }

    $result = json_decode($response, true);
    if (isset($result['error'])) {
        error_log("Meta API Response Error: " . json_encode($result['error']));
        return false;
    }

    return $result;
}

/**
 * 2. Send Plain Text Message
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

/**
 * 3. Send Image Message
 */
function sendImage($phone, $imageUrl, $caption = '') {
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $phone,
        'type' => 'image',
        'image' => ['link' => $imageUrl, 'caption' => $caption]
    ];

    return sendRequest($payload);
}

/**
 * 4. Send Interactive Buttons (Quick Replies)
 */
function sendButtons($phone, $text, $buttonLabels) {
    $buttons = [];
    foreach ($buttonLabels as $id => $label) {
        if (count($buttons) >= 3) break; // Meta limit: 3 buttons
        $buttons[] = [
            'type' => 'reply',
            'reply' => ['id' => $id, 'title' => mb_substr($label, 0, 20)]
        ];
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $phone,
        'type' => 'interactive',
        'interactive' => [
            'type' => 'button',
            'body' => ['text' => $text],
            'action' => ['buttons' => $buttons]
        ]
    ];

    return sendRequest($payload);
}

/**
 * 5. State-Based Flow Engine
 */
function runFlow($phone, $step) {
    $db = Database::getInstance();
    $step = strtolower(trim($step));

    switch ($step) {
        case 'start':
            // Update session state
            setSession($phone, 'start');
            
            // Send Greeting + Image + Selection
            sendText($phone, "Hello! Welcome to our automated assistant. 🤖");
            sendImage($phone, "https://picsum.photos/800/400", "How can we help you today?");
            sendButtons($phone, "Please select an option below:", [
                'buy' => '🛒 Buy Product',
                'agent' => '📞 Talk to Agent'
            ]);
            break;

        case 'buy':
            setSession($phone, 'buy');
            sendText($phone, "Our store is coming soon! 🛍️\nUse the 'agent' button if you have specific product queries.");
            // Loop back to main menu after a small delay simulation (just sending buttons again for now)
            sendButtons($phone, "Anything else?", [
                'start' => '🔙 Back to Menu',
                'agent' => '📞 Talk to Agent'
            ]);
            break;

        case 'agent':
            setSession($phone, 'agent');
            sendText($phone, "Understood. 🔄 Connecting you to our support team. An agent will reach out manually soon!");
            break;

        default:
            // Fallback for unknown input
            sendText($phone, "Sorry, I didn't catch that. Please use the menu buttons.");
            runFlow($phone, 'start');
            break;
    }
}

/**
 * Session Management
 */
function setSession($phone, $state) {
    $db = Database::getInstance();
    return $db->query("INSERT INTO chatbot_sessions (phone, state) VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE state = ?", [$phone, $state, $state]);
}

function getSession($phone) {
    $db = Database::getInstance();
    $session = $db->fetch("SELECT state FROM chatbot_sessions WHERE phone = ?", [$phone]);
    return $session ? $session['state'] : 'start';
}
?>
