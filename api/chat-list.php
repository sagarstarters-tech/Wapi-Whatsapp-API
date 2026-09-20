<?php
/**
 * WAPI SaaS - Chat List API
 * Returns conversation list for sidebar auto-refresh in live chat
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

$conversations = $db->fetchAll("
    SELECT m.*, c.name as contact_name 
    FROM messages m 
    LEFT JOIN contacts c ON m.contact_id = c.id 
    WHERE m.user_id = ? 
    AND m.id IN (
        SELECT MAX(id) FROM messages WHERE user_id = ? GROUP BY to_number
    )
    ORDER BY m.created_at DESC
", [$userId, $userId]);

$response = [];
foreach ($conversations as $c) {
    $msgType = $c['type'] ?? 'text';
    $preview = substr($c['content'] ?? '', 0, 30);

    $rawError = $c['error_message'] ?? '';
    $detectedOtp = extractOtpFromMessage($c['content'] ?? '', $rawError);

    if ($detectedOtp) {
        $preview = '🔐 OTP: ' . $detectedOtp;
    } elseif ($msgType === 'image') $preview = '📷 ' . ($preview !== '[Image]' ? $preview : 'Photo');
    elseif ($msgType === 'video') $preview = '🎥 ' . ($preview !== '[Video]' ? $preview : 'Video');
    elseif ($msgType === 'audio' || $msgType === 'voice') $preview = '🎵 Audio';
    elseif ($msgType === 'document') $preview = '📄 ' . ($preview !== '[Document]' ? $preview : 'Document');
    elseif ($msgType === 'sticker') $preview = '🏷️ Sticker';
    elseif ($msgType === 'location') $preview = '📍 Location';
    elseif ($msgType === 'button') $preview = '🔑 ' . $preview;
    elseif ($msgType === 'unsupported') {
        $preview = !empty($c['content']) && strpos($c['content'], '⚠️') !== false ? $c['content'] : '⚠️ Unsupported message';
    }
    elseif ($msgType === 'reaction') $preview = '😊 Reaction';
    elseif ($msgType === 'order') $preview = '🛒 Order';
    elseif ($msgType === 'contacts') $preview = '👤 Contact';
    elseif (strpos($c['content'] ?? '', '[UNSUPPORTED') === 0) $preview = '⚠️ Unsupported message';

    $response[] = [
        'phone' => $c['to_number'],
        'name' => $c['contact_name'] ?? $c['to_number'],
        'preview' => $preview,
        'direction' => $c['direction'],
        'time' => date('H:i', strtotime($c['created_at']))
    ];
}

echo json_encode($response);
exit;
