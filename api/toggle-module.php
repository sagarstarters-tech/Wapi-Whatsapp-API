<?php
/**
 * WAPI SaaS - Toggle Module Status API (Admin Only)
 * Allows enabling or disabling Chatbot Builder and AI ChatBot Builder modules.
 * Method: POST
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

// Admin Auth check
if (!Auth::isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin privileges required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read JSON input or POST data
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
$input    = is_array($jsonData) ? $jsonData : $_POST;

// CSRF check
$csrfToken = $input['_csrf_token'] ?? $input['csrf_token'] ?? null;
if (!CSRF::validateToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh.']);
    exit;
}

$module = sanitize($input['module'] ?? '');
$status = sanitizeInt($input['status'] ?? 0);

$allowedModules = [
    'chatbot_builder'    => 'enable_chatbot_builder',
    'ai_chatbot_builder' => 'enable_ai_chatbot_builder'
];

if (!isset($allowedModules[$module])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid module specified.']);
    exit;
}

$settingKey = $allowedModules[$module];
$settingValue = $status ? '1' : '0';

try {
    $db = Database::getInstance();
    $settings = new Settings();
    $settings->set($settingKey, $settingValue);

    $moduleNames = [
        'chatbot_builder'    => 'Chatbot Builder',
        'ai_chatbot_builder' => 'AI ChatBot Builder'
    ];

    $label = $moduleNames[$module] ?? 'Module';
    $stateText = $status ? 'enabled' : 'disabled';

    echo json_encode([
        'success'      => true,
        'module'       => $module,
        'setting_key'  => $settingKey,
        'status'       => (int)$status,
        'message'      => "$label has been $stateText successfully."
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
