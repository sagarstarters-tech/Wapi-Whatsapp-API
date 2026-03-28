<?php
/**
 * WAPI SaaS - Save Chatbot Flow API
 * Receives JSON from flow builder and stores it in the database.
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

// Get Input Data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['flow'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid data payload']));
}

$flowName = $data['name'] ?? 'My Master Flow';
$flowJson = json_encode($data['flow']);

try {
    // Check if flow exists for this user (Master Flow example)
    $existing = $db->fetch("SELECT id FROM chatbot_flows WHERE user_id = ? AND name = ?", [$userId, $flowName]);

    if ($existing) {
        $db->update('chatbot_flows', ['flow_json' => $flowJson], 'id = ?', [$existing['id']]);
        $flowId = $existing['id'];
    } else {
        $flowId = $db->insert('chatbot_flows', [
            'user_id' => $userId,
            'name' => $flowName,
            'flow_json' => $flowJson
        ]);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Flow saved successfully!',
        'flow_id' => $flowId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
