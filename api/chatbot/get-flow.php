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
$flowName = $_GET['name'] ?? 'Master Flow';

try {
    $flow = $db->fetch("SELECT flow_json FROM chatbot_flows WHERE user_id = ? AND name = ?", [$userId, $flowName]);

    if ($flow) {
        $flowData = json_decode($flow['flow_json'], true);
        echo json_encode([
            'success' => true,
            'flow' => $flowData
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
