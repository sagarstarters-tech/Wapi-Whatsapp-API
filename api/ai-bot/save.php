<?php
/**
 * WAPI SaaS - AI Bot Builder: Save (Create/Update) Bot API
 * Creates a new bot or updates an existing one.
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

// Extract and sanitize fields
$botId                      = sanitizeInt($input['bot_id'] ?? 0);
$name                       = sanitize($input['name'] ?? '');
$description                = sanitize($input['description'] ?? '');
$aiModel                    = sanitize($input['ai_model'] ?? 'gpt-3.5-turbo');
$botRole                    = sanitize($input['bot_role'] ?? 'customer_support');
$businessType               = sanitize($input['business_type'] ?? '');
$responseTone               = sanitize($input['response_tone'] ?? 'professional');
$responseLength             = sanitize($input['response_length'] ?? 'medium');
$language                   = sanitize($input['language'] ?? 'en');
$systemPrompt               = trim($input['system_prompt'] ?? '');
$whatsappAccountId          = sanitizeInt($input['whatsapp_account_id'] ?? 0);
$handoverEnabled            = sanitizeInt($input['handover_enabled'] ?? 0) ? 1 : 0;
$handoverKeywords           = sanitize($input['handover_keywords'] ?? '');
$handoverConfidenceThreshold = sanitizeFloat($input['handover_confidence_threshold'] ?? 0.3);
$crmCaptureEnabled          = sanitizeInt($input['crm_capture_enabled'] ?? 0) ? 1 : 0;
$customApiEndpoint          = sanitizeUrl($input['custom_api_endpoint'] ?? '');
$customApiKeyRaw            = trim($input['custom_api_key'] ?? '');

// Validate required fields
$errors = [];
if (empty($name)) {
    $errors[] = 'Bot name is required.';
}
if (strlen($name) > 255) {
    $errors[] = 'Bot name must be under 255 characters.';
}
if (!empty($customApiEndpoint) && !filter_var($customApiEndpoint, FILTER_VALIDATE_URL)) {
    $errors[] = 'Custom API endpoint must be a valid URL.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// Encrypt custom API key if provided
$encryptedApiKey = '';
if (!empty($customApiKeyRaw)) {
    $encryptedApiKey = encryptData($customApiKeyRaw);
}

try {
    // Build data array
    $botData = [
        'name'                         => $name,
        'description'                  => $description,
        'ai_model'                     => $aiModel,
        'bot_role'                     => $botRole,
        'business_type'                => $businessType,
        'response_tone'                => $responseTone,
        'response_length'              => $responseLength,
        'language'                     => $language,
        'system_prompt'                => $systemPrompt,
        'whatsapp_account_id'          => $whatsappAccountId > 0 ? $whatsappAccountId : null,
        'handover_enabled'             => $handoverEnabled,
        'handover_keywords'            => $handoverKeywords,
        'handover_confidence_threshold'=> $handoverConfidenceThreshold,
        'crm_capture_enabled'          => $crmCaptureEnabled,
        'custom_api_endpoint'          => $customApiEndpoint ?: null,
        'custom_api_key'               => $encryptedApiKey ?: null,
    ];

    if ($botId > 0) {
        // Update existing bot
        $result = AIBot::update($botId, $userId, $botData);

        if (!$result) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Bot not found or you do not have permission to update it.']);
            exit;
        }

        $bot = AIBot::getById($botId, $userId);

        echo json_encode([
            'success' => true,
            'data'    => $bot,
            'message' => 'Bot updated successfully'
        ]);
    } else {
        // Check plan limit before creating
        $limitCheck = AIBot::checkPlanLimit($userId);

        if (!$limitCheck['allowed']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => $limitCheck['message'] ?? 'You have reached the maximum number of bots for your plan. Please upgrade to create more.'
            ]);
            exit;
        }

        // Create new bot
        $botData['user_id'] = $userId;
        $newBotId = AIBot::create($botData);

        $bot = AIBot::getById($newBotId, $userId);

        echo json_encode([
            'success' => true,
            'data'    => $bot,
            'message' => 'Bot created successfully'
        ]);
    }
} catch (Exception $e) {
    error_log("AI Bot save error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save bot. Please try again.']);
}
