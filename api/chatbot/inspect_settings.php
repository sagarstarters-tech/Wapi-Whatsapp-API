<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../classes/Database.php';

    $db = Database::getInstance();
    $rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_name', 'site_logo', 'logo_height', 'site_favicon')");
    $settings = [];
    foreach ($rows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }

    echo json_encode([
        'settings' => $settings,
        'app_url' => defined('APP_URL') ? APP_URL : null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $t) {
    echo json_encode(['error' => $t->getMessage()]);
}
