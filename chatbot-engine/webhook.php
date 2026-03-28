<?php
/**
 * WhatsApp Chatbot Webhook Handler
 * This script processes incoming messages from the Meta WhatsApp Cloud API.
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
    // Read the raw JSON input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        http_response_code(400);
        die();
    }

    // Extract necessary information from the Meta JSON payload
    // Structure: entry[0].changes[0].value.messages[0]
    $entry = $data['entry'][0]['changes'][0]['value'] ?? [];
    $messages = $entry['messages'] ?? [];

    if (!empty($messages)) {
        foreach ($messages as $msg) {
            $from = $msg['from'] ?? ''; // Sender's phone number
            $type = $msg['type'] ?? 'text'; // Message type (text / interactive / etc)

            // Handle Interaction Replies (Button clicks)
            if ($type === 'interactive' && isset($msg['interactive']['button_reply'])) {
                $buttonId = $msg['interactive']['button_reply']['id'] ?? '';
                runFlow($from, $buttonId); // Case matching flow logic
            }
            // Handle Text Messages
            elseif ($type === 'text') {
                $textBody = strtolower(trim($msg['text']['body'] ?? ''));

                // Handle basic commands or keyword triggers
                if ($textBody === 'start' || $textBody === 'hi' || $textBody === 'menu') {
                    runFlow($from, 'start');
                } else {
                    // Check previous user session if available
                    $currentState = getSession($from);
                    runFlow($from, $currentState === 'start' ? 'unknown' : $currentState);
                }
            }
        }
    }

    // Acknowledge receipt to Meta API (Status 200 OK)
    http_response_code(200);
    echo json_encode(['status' => 'success']);
}
?>
