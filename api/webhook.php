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
    $payload = json_decode(file_get_contents('php://input'), true);

    if ($payload) {
        $wa = new WhatsApp();
        $wa->processWebhook($payload);
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
