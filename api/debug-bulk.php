<?php
/**
 * Temporary debug endpoint - check last failed bulk messages and their errors
 * DELETE THIS FILE AFTER DEBUGGING
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

Auth::requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get last 10 failed template messages with their error details
$failed = $db->fetchAll(
    "SELECT id, to_number, type, content, template_name, status, error_message, created_at 
     FROM messages 
     WHERE user_id = ? AND status = 'failed' AND type = 'template' 
     ORDER BY created_at DESC LIMIT 10",
    [$userId]
);

// Get templates list
$templates = $db->fetchAll(
    "SELECT id, name, language, status FROM templates WHERE user_id = ? ORDER BY name",
    [$userId]
);

// Check the bulk_send.log if exists
$bulkLog = '';
$logFile = APP_ROOT . '/logs/bulk_send.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $bulkLog = implode('', array_slice($lines, -10));
}

// Check whatsapp_api.log if exists
$apiLog = '';
$apiLogFile = APP_ROOT . '/logs/whatsapp_api.log';
if (file_exists($apiLogFile)) {
    $lines = file($apiLogFile);
    $apiLog = implode('', array_slice($lines, -10));
}

echo json_encode([
    'failed_messages' => $failed,
    'templates' => $templates,
    'bulk_send_log' => $bulkLog,
    'api_error_log' => $apiLog
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
