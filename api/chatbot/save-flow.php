<?php
/**
 * WAPI SaaS - Save Chatbot Flow API (With JIT Migration)
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

function performSave($db, $userId, $flowName, $flowJson) {
    $existing = $db->fetch("SELECT id FROM chatbot_flows WHERE user_id = ? AND name = ?", [$userId, $flowName]);
    if ($existing) {
        $db->update('chatbot_flows', ['flow_json' => $flowJson], 'id = ?', [$existing['id']]);
        return $existing['id'];
    } else {
        return $db->insert('chatbot_flows', [
            'user_id' => $userId,
            'name' => $flowName,
            'flow_json' => $flowJson
        ]);
    }
}

try {
    // Attempt Primary Save
    $flowId = performSave($db, $userId, $flowName, $flowJson);
    echo json_encode(['success' => true, 'message' => 'Flow saved successfully!', 'flow_id' => $flowId]);
} catch (Exception $e) {
    // JIT Migration: If any chatbot columns are missing, auto-fix and retry
    if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), '1054') !== false) {
        try {
            // Fix Chatbot Flows Table
            $db->query("ALTER TABLE `chatbot_flows` ADD COLUMN IF NOT EXISTS `flow_json` LONGTEXT AFTER `name` ");
            $db->query("ALTER TABLE `chatbot_flows` MODIFY COLUMN `response_content` TEXT NULL");
            
            // Fix Chatbot Sessions Table (Optional but important for engine)
            $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN IF NOT EXISTS `flow_id` INT AFTER `phone` ");
            $db->query("ALTER TABLE `chatbot_sessions` ADD COLUMN IF NOT EXISTS `current_node_id` VARCHAR(50) AFTER `user_id` ");

            // Retry Save
            $flowId = performSave($db, $userId, $flowName, $flowJson);
            echo json_encode(['success' => true, 'message' => 'Schema updated and Flow saved successfully!', 'flow_id' => $flowId]);
        } catch (Exception $migErr) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Auto-schema update failed: ' . $migErr->getMessage()]));
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
