<?php
/**
 * WAPI SaaS - Message Details & Webhook Raw Payload Inspector API
 * Returns parsed message details, raw Meta payload, and extracted OTP/verification code.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

$id = sanitizeInt($_GET['id'] ?? 0);
$wamid = sanitize($_GET['wamid'] ?? '');

if ($id <= 0 && empty($wamid)) {
    echo json_encode(['success' => false, 'message' => 'Message ID or WAMID required.']);
    exit;
}

$where = "m.user_id = ?";
$params = [$userId];

if ($id > 0) {
    $where .= " AND m.id = ?";
    $params[] = $id;
} else {
    $where .= " AND m.message_id = ?";
    $params[] = $wamid;
}

$msg = $db->fetch("
    SELECT m.*, c.name as contact_name 
    FROM messages m 
    LEFT JOIN contacts c ON m.contact_id = c.id 
    WHERE {$where} 
    LIMIT 1
", $params);

if (!$msg) {
    echo json_encode(['success' => false, 'message' => 'Message not found.']);
    exit;
}

$rawPayload = null;

// 1. Try decoding error_message column if it contains JSON
if (!empty($msg['error_message'])) {
    $decoded = json_decode($msg['error_message'], true);
    if (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) {
        $rawPayload = $decoded;
    }
}

// 2. If no raw payload yet, check webhook_logs by message_id (WAMID)
if (!$rawPayload && !empty($msg['message_id'])) {
    $log = $db->fetch(
        "SELECT payload FROM webhook_logs WHERE payload LIKE ? ORDER BY id DESC LIMIT 1",
        ['%' . $msg['message_id'] . '%']
    );
    if ($log && !empty($log['payload'])) {
        $logJson = json_decode($log['payload'], true);
        if ($logJson) {
            // Find specific message in webhook changes
            $foundMsg = null;
            foreach ($logJson['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    foreach ($change['value']['messages'] ?? [] as $m) {
                        if (($m['id'] ?? '') === $msg['message_id']) {
                            $foundMsg = $m;
                            break 3;
                        }
                    }
                }
            }
            $rawPayload = $foundMsg ?: $logJson;
        }
    }
}

// 3. Fallback: check webhook_logs by phone number and timestamp window
if (!$rawPayload && !empty($msg['to_number']) && !empty($msg['created_at'])) {
    $log = $db->fetch(
        "SELECT payload FROM webhook_logs WHERE event_type = 'incoming' AND payload LIKE ? AND created_at BETWEEN DATE_SUB(?, INTERVAL 10 MINUTE) AND DATE_ADD(?, INTERVAL 10 MINUTE) ORDER BY id DESC LIMIT 1",
        ['%' . $msg['to_number'] . '%', $msg['created_at'], $msg['created_at']]
    );
    if ($log && !empty($log['payload'])) {
        $rawPayload = json_decode($log['payload'], true);
    }
}

// 4. Detect OTP from content or raw payload (only for inbound messages)
$detectedOtp = ($msg['direction'] === 'inbound') ? extractOtpFromMessage($msg['content'], $rawPayload) : null;

// 5. Retroactive self-healing: if message in DB has generic notice or missing raw payload, update it
if ($rawPayload && (empty($msg['error_message']) || strpos($msg['content'] ?? '', 'isn\'t supported in the chat viewer yet') !== false)) {
    $updateFields = [
        'error_message' => json_encode($rawPayload, JSON_UNESCAPED_UNICODE)
    ];
    if ($detectedOtp && strpos($msg['content'] ?? '', 'isn\'t supported in the chat viewer yet') !== false) {
        $unsupType = $rawPayload['unsupported']['type'] ?? '';
        $updateFields['content'] = "🔐 OTP / Verification Code: {$detectedOtp}" . ($unsupType ? "\n[System message type: {$unsupType}]" : '');
        $msg['content'] = $updateFields['content'];
    }
    $db->update('messages', $updateFields, 'id = ?', [$msg['id']]);
}

echo json_encode([
    'success'      => true,
    'id'           => $msg['id'],
    'message_id'   => $msg['message_id'] ?? '',
    'to_number'    => $msg['to_number'],
    'contact_name' => $msg['contact_name'] ?? $msg['to_number'],
    'direction'    => $msg['direction'],
    'type'         => $msg['type'],
    'content'      => $msg['content'] ?? '',
    'media_url'    => $msg['media_url'] ?? '',
    'status'       => $msg['status'],
    'time'         => date('d M Y, H:i:s', strtotime($msg['created_at'])),
    'detected_otp' => $detectedOtp,
    'raw_payload'  => $rawPayload
], JSON_UNESCAPED_UNICODE);
exit;
