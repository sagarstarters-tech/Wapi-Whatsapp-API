<?php
/**
 * WAPI SaaS - AI Bot Builder: Test Bot API
 * Sends a test message to a bot and returns the AI response (without sending via WhatsApp).
 * Method: POST
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

// Auth check
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// CSRF validation
if (!CSRF::validateToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

$userId = $_SESSION['user_id'];

// Read input from JSON body or POST data
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
$input    = is_array($jsonData) ? $jsonData : $_POST;

$botId   = sanitizeInt($input['bot_id'] ?? 0);
$message = trim($input['message'] ?? '');

// Validate inputs
if ($botId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid bot ID']);
    exit;
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

if (strlen($message) > 4000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message must be under 4000 characters']);
    exit;
}

try {
    // Verify bot ownership
    $bot = AIBot::getById($botId, $userId);
    if (!$bot) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Bot not found or access denied']);
        exit;
    }

    // Process message with test flag — returns AI response without sending via WhatsApp
    $response = AIOrchestrator::processMessage([
        'bot_id'    => $botId,
        'user_id'   => $userId,
        'message'   => $message,
        'sender'    => 'test_user_' . $userId,
        'test_mode' => true
    ]);

    if (!$response || !isset($response['content'])) {
        echo json_encode([
            'success' => true,
            'data'    => [
                'response'     => $response['content'] ?? 'No response generated. Check your bot configuration and knowledge base.',
                'model_used'   => $response['model'] ?? $bot['ai_model'] ?? 'unknown',
                'tokens_used'  => $response['tokens_used'] ?? null,
                'kb_sources'   => $response['kb_sources'] ?? [],
                'processing_ms'=> $response['processing_ms'] ?? null,
                'test_mode'    => true
            ],
            'message' => 'Test response generated'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data'    => [
            'response'      => $response['content'],
            'model_used'    => $response['model'] ?? $bot['ai_model'] ?? 'unknown',
            'tokens_used'   => $response['tokens_used'] ?? null,
            'kb_sources'    => $response['kb_sources'] ?? [],
            'processing_ms' => $response['processing_ms'] ?? null,
            'confidence'    => $response['confidence'] ?? null,
            'test_mode'     => true
        ],
        'message' => 'Test response generated successfully'
    ]);
} catch (Exception $e) {
    error_log("AI Bot test-bot error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate test response: ' . $e->getMessage()
    ]);
}
