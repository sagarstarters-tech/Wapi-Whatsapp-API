<?php
/**
 * WAPI SaaS - Bulk Send Batch Processor (AJAX)
 * Processes one batch of contacts at a time to avoid server timeouts
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

set_time_limit(120);
ignore_user_abort(false);
header('Content-Type: application/json');

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateToken()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db       = Database::getInstance();
$userId   = $_SESSION['user_id'];

$waAccount = $db->fetch("SELECT * FROM whatsapp_accounts WHERE user_id = ? AND status = 'active' LIMIT 1", [$userId]);
if (!$waAccount) {
    echo json_encode(['success' => false, 'message' => 'WhatsApp account not configured.']);
    exit;
}

$type       = sanitize($_POST['type'] ?? 'text');
$content    = $_POST['content'] ?? '';
$mediaUrl   = sanitize($_POST['media_url'] ?? '');
$phones     = json_decode($_POST['phones'] ?? '[]', true);

// Rebuild template components if any
$templateComponents = [];
$rawComponents = $_POST['template_components'] ?? '';
if ($rawComponents) {
    $decoded = json_decode($rawComponents, true);
    if (is_array($decoded)) {
        $templateComponents = $decoded;
    }
}

if (empty($phones) || !is_array($phones)) {
    echo json_encode(['success' => false, 'message' => 'No phone numbers in batch.']);
    exit;
}

$wa = new WhatsApp();
$sent   = 0;
$failed = 0;
$errors = [];

foreach ($phones as $phone) {
    $phone = trim($phone);
    if (empty($phone)) continue;

    switch ($type) {
        case 'text':
            $result = $wa->sendText($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $phone, $content);
            break;
        case 'image':
            $result = $wa->sendImage($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $phone, $mediaUrl, $content);
            break;
        case 'template':
            $templateLanguage = sanitize($_POST['template_language'] ?? 'en');
            $result = $wa->sendTemplate($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $phone, $content, $templateLanguage, $templateComponents);
            break;
        default:
            $result = $wa->sendText($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $phone, $content);
    }

    if ($result['success']) {
        $sent++;
    } else {
        $failed++;
        $errors[] = "$phone: " . $result['message'];
    }

    // 15ms delay – Meta rate limit
    usleep(15000);
}

echo json_encode([
    'success' => true,
    'sent'    => $sent,
    'failed'  => $failed,
    'errors'  => $errors
]);
