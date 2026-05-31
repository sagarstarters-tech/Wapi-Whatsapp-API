<?php
/**
 * WAPI SaaS - Webhook Logs Viewer (Diagnostic)
 * Usage: Access via browser: /api/view-logs.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

header('Content-Type: text/plain; charset=utf-8');

echo "=== webhook_raw.log ===\n";
$rawPath = __DIR__ . '/webhook_raw.log';
if (file_exists($rawPath)) {
    echo file_get_contents($rawPath);
} else {
    echo "No raw payload log found at $rawPath\n";
}

echo "\n\n=== webhook_root.log ===\n";
$rootPath = __DIR__ . '/../logs/webhook_root.log';
if (file_exists($rootPath)) {
    echo file_get_contents($rootPath);
} else {
    echo "No root webhook log found at $rootPath\n";
}
exit;
