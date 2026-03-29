<?php
/**
 * WAPI SaaS - Get Chatbot Flow API
 * Fetches the saved JSON flow from the database.
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';

// Auth Check
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

try {
    // Support load_latest=1 to get the most recently saved flow
    if (!empty($_GET['load_latest'])) {
        $flow = $db->fetch(
            "SELECT name, flow_json FROM chatbot_flows WHERE user_id = ? ORDER BY updated_at DESC, id DESC LIMIT 1",
            [$userId]
        );
    } else {
        $flowName = $_GET['name'] ?? 'Master Flow';
        $flow = $db->fetch(
            "SELECT name, flow_json FROM chatbot_flows WHERE user_id = ? AND name = ?",
            [$userId, $flowName]
        );
    }

    if ($flow && $flow['flow_json']) {
        $flowData = json_decode($flow['flow_json'], true);
        echo json_encode([
            'success'    => true,
            'flow'       => $flowData,
            'flow_name'  => $flow['name']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No flow found'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
