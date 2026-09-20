<?php
/**
 * WAPI SaaS - Chat History API
 * Fetches message history with a specific contact
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$phone = sanitize($_GET['phone'] ?? '');

if (empty($phone)) {
    echo json_encode([]);
    exit;
}

$messages = $db->fetchAll("
    SELECT * FROM messages 
    WHERE user_id = ? AND to_number = ? 
    ORDER BY created_at ASC 
    LIMIT 50
", [$userId, $phone]);

$response = [];
foreach ($messages as $m) {
    $rawError = $m['error_message'] ?? '';
    
    // Check for OTP in content or raw error
    $detectedOtp = extractOtpFromMessage($m['content'], $rawError);
    
    // If unsupported and missing raw payload, check webhook_logs for healing
    if (!$detectedOtp && ($m['type'] === 'unsupported' || strpos($m['content'] ?? '', 'isn\'t supported') !== false)) {
        if (!empty($m['message_id'])) {
            $log = $db->fetch("SELECT payload FROM webhook_logs WHERE payload LIKE ? ORDER BY id DESC LIMIT 1", ['%' . $m['message_id'] . '%']);
            if ($log) {
                $rawError = $log['payload'];
                $detectedOtp = extractOtpFromMessage($m['content'], $rawError);
                if ($detectedOtp) {
                    $db->update('messages', [
                        'content' => "🔐 OTP / Verification Code: {$detectedOtp}\n[System message format]",
                        'error_message' => $rawError
                    ], 'id = ?', [$m['id']]);
                    $m['content'] = "🔐 OTP / Verification Code: {$detectedOtp}\n[System message format]";
                }
            }
        }
    }

    $response[] = [
        'id'           => $m['id'],
        'message_id'   => $m['message_id'] ?? '',
        'content'      => $m['content'] ?? '',
        'type'         => $m['type'] ?? 'text',
        'media_url'    => $m['media_url'] ?? '',
        'direction'    => $m['direction'],
        'status'       => $m['status'],
        'detected_otp' => $detectedOtp,
        'has_raw'      => !empty($rawError),
        'time'         => date('H:i', strtotime($m['created_at']))
    ];
}

echo json_encode($response);
exit;
