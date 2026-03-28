<?php
/**
 * WAPI SaaS - Save Chatbot Flow API
 * Handles saving nodes and connections for a specific flow
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
Auth::requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get posted JSON data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['nodes'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid data provided.']);
}

$flowId = sanitizeInt($data['id'] ?? 0);
$nodes = $data['nodes'] ?? [];
$connections = $data['connections'] ?? [];
$isPublished = sanitizeInt($data['is_published'] ?? 0);

try {
    $db->beginTransaction();

    // 1. Ensure the flow exists and belongs to the user
    if ($flowId > 0) {
        $flow = $db->fetch("SELECT id FROM chatbot_flows WHERE id = ? AND user_id = ?", [$flowId, $userId]);
        if (!$flow) throw new Exception("Unauthorized or flow not found.");
        
        $db->update('chatbot_flows', [
            'is_published' => $isPublished,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$flowId]);
    } else {
        // Create new flow if no ID
        $flowId = $db->insert('chatbot_flows', [
            'user_id' => $userId,
            'name' => 'New Flow',
            'is_published' => $isPublished
        ]);
    }

    // 2. Clear existing nodes and connections
    $db->delete('chatbot_nodes', 'flow_id = ?', [$flowId]);
    $db->delete('chatbot_connections', 'flow_id = ?', [$flowId]);

    // 3. Save new nodes
    foreach ($nodes as $node) {
        $db->insert('chatbot_nodes', [
            'flow_id' => $flowId,
            'node_uuid' => $node['node_uuid'],
            'type' => $node['type'],
            'data' => json_encode($node['data']),
            'x' => (int)$node['x'],
            'y' => (int)$node['y']
        ]);
    }

    // 4. Save new connections
    foreach ($connections as $conn) {
        $db->insert('chatbot_connections', [
            'flow_id' => $flowId,
            'from_node_uuid' => $conn['fromNode'],
            'from_port' => $conn['fromPort'],
            'to_node_uuid' => $conn['toNode']
        ]);
    }

    $db->commit();
    jsonResponse(['success' => true, 'flow_id' => $flowId, 'message' => 'Flow saved successfully.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonResponse(['success' => false, 'message' => $e->getMessage()]);
}
