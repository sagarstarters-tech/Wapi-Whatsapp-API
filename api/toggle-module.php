<?php
/**
 * WAPI SaaS - Toggle Module Status API
 * Allows enabling or disabling Chatbot Builder and AI ChatBot Builder modules.
 * Accessible to authenticated users and administrators.
 * Method: POST
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

// User Auth check (require login)
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage automation modules.']);
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
$csrfToken = $input['_csrf_token'] 
    ?? $input['csrf_token'] 
    ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
    ?? null;

if (!CSRF::validateToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired security token. Please refresh the page.']);
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

$settingKey   = $allowedModules[$module];
$settingValue = $status ? '1' : '0';
$userId       = $_SESSION['user_id'];
$isAdmin      = Auth::isAdmin();

try {
    $db = Database::getInstance();
    $settings = new Settings();

    // 1. Update platform setting
    $settings->set($settingKey, $settingValue);

    // 2. Update user preference setting
    $settings->set("user_{$userId}_{$module}", $settingValue);

    // 3. Synchronize user's flow or bot records
    if ($module === 'chatbot_builder') {
        if ($status === 1) {
            $activeCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM chatbot_flows WHERE user_id = ? AND is_active = 1", [$userId]);
            if ($activeCount === 0) {
                $db->query("UPDATE chatbot_flows SET is_active = 1 WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1", [$userId]);
            }
        } else {
            $db->query("UPDATE chatbot_flows SET is_active = 0 WHERE user_id = ?", [$userId]);
        }
    } elseif ($module === 'ai_chatbot_builder') {
        if ($status === 1) {
            $activeCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM ai_bots WHERE user_id = ? AND status = 'active'", [$userId]);
            if ($activeCount === 0) {
                $db->query("UPDATE ai_bots SET status = 'active' WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1", [$userId]);
            }
        } else {
            $db->query("UPDATE ai_bots SET status = 'inactive' WHERE user_id = ?", [$userId]);
        }
    }

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
