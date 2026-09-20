<?php
/**
 * WAPI SaaS - AI Bot Builder: Clear / Delete Conversations API
 * Supports deleting a single conversation, batch delete, or clear all (with filters).
 * Method: POST
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

// Auth check
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Method check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read input from JSON body or POST data
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);
$input    = is_array($jsonData) ? $jsonData : $_POST;

// CSRF validation
$csrfToken = $input['_csrf_token'] ?? $input['csrf_token'] ?? null;
if (!CSRF::validateToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = sanitize($input['action'] ?? 'delete_single');

try {
    $db = Database::getInstance();

    if ($action === 'delete_single') {
        $convId = sanitizeInt($input['conversation_id'] ?? 0);
        if ($convId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid conversation ID.']);
            exit;
        }

        // Verify ownership
        $conv = $db->fetch(
            "SELECT c.id FROM ai_conversations c 
             JOIN ai_bots b ON c.bot_id = b.id 
             WHERE c.id = ? AND (c.user_id = ? OR b.user_id = ?)",
            [$convId, $userId, $userId]
        );

        if (!$conv) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Conversation not found or access denied.']);
            exit;
        }

        // Delete child records first to ensure clean removal
        $db->query("DELETE FROM ai_messages WHERE conversation_id = ?", [$convId]);
        $db->query("DELETE FROM ai_handovers WHERE conversation_id = ?", [$convId]);
        $db->query("DELETE FROM ai_conversations WHERE id = ?", [$convId]);

        echo json_encode([
            'success' => true,
            'message' => 'Conversation deleted successfully.',
            'deleted_id' => $convId
        ]);
        exit;
    }

    if ($action === 'delete_batch') {
        $convIds = array_map('intval', (array)($input['conversation_ids'] ?? []));
        $convIds = array_filter($convIds, function($id) { return $id > 0; });

        if (empty($convIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No conversations selected.']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($convIds), '?'));
        $validConvs = $db->fetchAll(
            "SELECT c.id FROM ai_conversations c 
             JOIN ai_bots b ON c.bot_id = b.id 
             WHERE c.id IN ($placeholders) AND (c.user_id = ? OR b.user_id = ?)",
            array_merge($convIds, [$userId, $userId])
        );
        $validIds = array_column($validConvs, 'id');

        if (!empty($validIds)) {
            $chunks = array_chunk($validIds, 100);
            foreach ($chunks as $chunk) {
                $delPh = implode(',', array_fill(0, count($chunk), '?'));
                $db->query("DELETE FROM ai_messages WHERE conversation_id IN ($delPh)", $chunk);
                $db->query("DELETE FROM ai_handovers WHERE conversation_id IN ($delPh)", $chunk);
                $db->query("DELETE FROM ai_conversations WHERE id IN ($delPh)", $chunk);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => count($validIds) . ' conversation(s) deleted successfully.',
            'deleted_count' => count($validIds)
        ]);
        exit;
    }

    if ($action === 'clear_all') {
        $botId  = sanitizeInt($input['bot_id'] ?? 0);
        $status = sanitize($input['status'] ?? '');

        $conditions = ['(c.user_id = ? OR b.user_id = ?)'];
        $params     = [$userId, $userId];

        if ($botId > 0) {
            $conditions[] = 'c.bot_id = ?';
            $params[]     = $botId;
        }

        if ($status && in_array($status, ['active', 'resolved', 'handed_over', 'expired'])) {
            $conditions[] = 'c.status = ?';
            $params[]     = $status;
        }

        $whereSql = implode(' AND ', $conditions);
        $matching = $db->fetchAll(
            "SELECT c.id FROM ai_conversations c 
             JOIN ai_bots b ON c.bot_id = b.id 
             WHERE $whereSql",
            $params
        );
        $matchingIds = array_column($matching, 'id');
        $count = count($matchingIds);

        if ($count > 0) {
            $chunks = array_chunk($matchingIds, 100);
            foreach ($chunks as $chunk) {
                $delPh = implode(',', array_fill(0, count($chunk), '?'));
                $db->query("DELETE FROM ai_messages WHERE conversation_id IN ($delPh)", $chunk);
                $db->query("DELETE FROM ai_handovers WHERE conversation_id IN ($delPh)", $chunk);
                $db->query("DELETE FROM ai_conversations WHERE id IN ($delPh)", $chunk);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => $count . ' conversation(s) cleared successfully.',
            'deleted_count' => $count
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);

} catch (Exception $e) {
    error_log("Clear conversations error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error while clearing conversations.']);
}
