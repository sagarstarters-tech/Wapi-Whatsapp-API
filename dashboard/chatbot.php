<?php
/**
 * WAPI SaaS - Visual Chatbot Flow Builder
 * Drag & Drop Node-Based Flow Editor
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$settings = new Settings();
$userId = $_SESSION['user_id'];

$hideNav = true; // Prevents landing page nav from appearing in dashboard

// Handle AJAX save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'save_flow' && CSRF::validateToken()) {
        $flowId = sanitizeInt($_POST['flow_id'] ?? 0);
        $flowName = sanitize($_POST['flow_name'] ?? 'Untitled Flow');
        $flowData = $_POST['flow_data'] ?? '{}';

        // Extract keywords from start node for DB indexing
        $parsed = json_decode($flowData, true);
        $keywords = '';
        if ($parsed && isset($parsed['nodes']['node_start']['data']['keywords'])) {
            $keywords = implode(',', $parsed['nodes']['node_start']['data']['keywords']);
        }
        $matchType = $parsed['nodes']['node_start']['data']['matchType'] ?? 'contains';

        try {
            if ($flowId > 0) {
                $db->update('chatbot_flows', [
                    'name' => $flowName,
                    'trigger_keyword' => mb_substr($keywords, 0, 100), // Prevent too long error
                    'match_type' => $matchType,
                    'response_type' => 'text',
                    'response_content' => $flowData
                ], 'id = ? AND user_id = ?', [$flowId, $userId]);
            } else {
                $flowId = $db->insert('chatbot_flows', [
                    'user_id' => $userId,
                    'name' => $flowName,
                    'trigger_keyword' => mb_substr($keywords, 0, 100), // Prevent too long error
                    'match_type' => $matchType,
                    'response_type' => 'text',
                    'response_content' => $flowData,
                    'is_active' => 1,
                    'priority' => 0
                ]);
            }

            echo json_encode(['success' => true, 'flow_id' => $flowId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_flow' && CSRF::validateToken()) {
        $flowId = sanitizeInt($_POST['flow_id'] ?? 0);
        $db->delete('chatbot_flows', 'id = ? AND user_id = ?', [$flowId, $userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Check mode: list or builder
$mode = $_GET['mode'] ?? 'list';
$editFlowId = sanitizeInt($_GET['edit'] ?? 0);

// Load existing flow for editing
$editFlow = null;
if ($editFlowId > 0) {
    $editFlow = $db->fetch("SELECT * FROM chatbot_flows WHERE id = ? AND user_id = ?", [$editFlowId, $userId]);
    $mode = 'builder';
}

// Load all flows for list view
$flows = $db->fetchAll("SELECT * FROM chatbot_flows WHERE user_id = ? ORDER BY created_at DESC", [$userId]);

$pageTitle = 'Chatbot Flow Builder';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?> - <?= e($settings->get('site_name', 'WAPI')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= asset('assets/css/style.css'); ?>" rel="stylesheet">
    <link href="<?= asset('assets/css/dashboard.css'); ?>" rel="stylesheet">
    <link href="<?= asset('assets/css/chatbot-builder.css'); ?>" rel="stylesheet">
</head>
<body>

<?php if ($mode === 'builder'): ?>
<!-- ============ FLOW BUILDER MODE ============ -->
<div class="chatbot-builder-page">
    <!-- Top Bar -->
    <div class="builder-topbar">
        <a href="<?= baseUrl('dashboard/chatbot.php'); ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <input type="text" id="flowName" class="flow-name-input" value="<?= e($editFlow['name'] ?? 'New Bot Flow'); ?>" placeholder="Flow Name">
        <div class="builder-actions">
            <button class="btn-icon" onclick="builder.exportFlow()" title="Export JSON"><i class="bi bi-download"></i></button>
            <button class="btn-icon" onclick="builder.centerCanvas()" title="Center View"><i class="bi bi-fullscreen"></i></button>
            <button class="btn-save" onclick="builder.saveFlow()"><i class="bi bi-check-lg"></i> Save</button>
        </div>
    </div>

    <!-- Component Toolbar -->
    <div class="component-toolbar">
        <span class="toolbar-label">Components</span>
        <button class="component-btn text-type" data-type="text"><i class="bi bi-chat-text-fill"></i><span>Text</span></button>
        <button class="component-btn image-type" data-type="image"><i class="bi bi-image-fill"></i><span>Image</span></button>
        <button class="component-btn audio-type" data-type="audio"><i class="bi bi-volume-up-fill"></i><span>Audio</span></button>
        <button class="component-btn video-type" data-type="video"><i class="bi bi-camera-video-fill"></i><span>Video</span></button>
        <button class="component-btn file-type" data-type="file"><i class="bi bi-file-earmark-fill"></i><span>File</span></button>
        <button class="component-btn button-type" data-type="button"><i class="bi bi-hand-index-thumb-fill"></i><span>Button</span></button>
        <button class="component-btn interactive-type" data-type="interactive"><i class="bi bi-chat-quote-fill"></i><span>Interactive</span></button>
        <button class="component-btn condition-type" data-type="condition"><i class="bi bi-signpost-split-fill"></i><span>Condition</span></button>
        <button class="component-btn delay-type" data-type="delay"><i class="bi bi-clock-fill"></i><span>Delay</span></button>
    </div>

    <!-- Canvas -->
    <div class="builder-canvas" id="builderCanvas">
        <div class="builder-canvas-inner" id="canvasInner">
            <svg class="connections-svg" id="connectionsSvg" xmlns="http://www.w3.org/2000/svg"></svg>
        </div>

        <!-- Zoom Controls -->
        <div class="zoom-controls">
            <button onclick="builder.setZoom(builder.zoom + 0.1)"><i class="bi bi-plus"></i></button>
            <div class="zoom-level" id="zoomLevel">100%</div>
            <button onclick="builder.setZoom(builder.zoom - 0.1)"><i class="bi bi-dash"></i></button>
            <button onclick="builder.setZoom(1)" title="Reset Zoom"><i class="bi bi-aspect-ratio"></i></button>
        </div>
    </div>

    <!-- Hidden fields -->
    <input type="hidden" id="flowId" value="<?= $editFlowId; ?>">
    <input type="hidden" id="csrfToken" value="<?= CSRF::generateToken(); ?>">
</div>

<script src="<?= asset('assets/js/chatbot-builder.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvasEl = document.getElementById('builderCanvas');
    builder = new ChatbotFlowBuilder(canvasEl);

    <?php if ($editFlow && !empty($editFlow['response_content'])): ?>
    // Load existing flow
    try {
        const flowData = <?= $editFlow['response_content']; ?>;
        builder.loadFlow(flowData);
    } catch(e) { console.warn('Could not load flow data:', e); }
    <?php endif; ?>
});
</script>

<?php else: ?>
<!-- ============ FLOW LIST MODE ============ -->
<?php
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Chatbot Flows</h1>
                <div class="dash-breadcrumb"><a href="<?= baseUrl('dashboard/'); ?>">Dashboard</a><i class="bi bi-chevron-right"></i><span>Chatbot</span></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
                <a href="<?= baseUrl('dashboard/chatbot.php?mode=builder'); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Create New Flow</a>
            </div>
        </div>

        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle-fill"></i> Create visual chatbot flows with our drag-and-drop builder. When someone messages your WhatsApp number, the bot automatically replies based on the flow.
        </div>

        <div class="row g-4">
            <?php if (empty($flows)): ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="bi bi-robot" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                    <h5>No Chatbot Flows Yet</h5>
                    <p style="color: var(--text-muted); max-width: 400px; margin: 0 auto 1.5rem;">Create your first chatbot flow using our visual drag-and-drop builder.</p>
                    <a href="<?= baseUrl('dashboard/chatbot.php?mode=builder'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Create Your First Flow</a>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($flows as $flow): ?>
            <div class="col-lg-4 col-md-6" id="flow-card-<?= $flow['id']; ?>">
                <div class="card h-100" style="border-radius: var(--border-radius); transition: all 0.2s;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bold mb-1"><?= e($flow['name']); ?></h6>
                                <span class="status-badge status-<?= $flow['is_active'] ? 'active' : 'inactive'; ?>"><?= $flow['is_active'] ? 'Active' : 'Disabled'; ?></span>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="<?= baseUrl('dashboard/chatbot.php?edit=' . $flow['id']); ?>" class="btn btn-sm" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-sm" style="background: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.2);" onclick="deleteFlow(<?= $flow['id']; ?>)">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>

                        <?php if (!empty($flow['trigger_keyword'])): ?>
                        <div class="mb-3">
                            <div style="font-size: 0.6875rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Trigger Keywords</div>
                            <?php foreach (explode(',', $flow['trigger_keyword']) as $kw): ?>
                            <span style="display:inline-block; background: rgba(37,211,102,0.1); color: #25D366; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; margin: 2px;"><?= e(trim($kw)); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex gap-3" style="font-size: 0.75rem; color: var(--text-muted);">
                            <span><i class="bi bi-diagram-3"></i> <?= ucfirst(str_replace('_', ' ', $flow['match_type'])); ?></span>
                            <span><i class="bi bi-clock"></i> <?= timeAgo($flow['created_at']); ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top p-3">
                        <a href="<?= baseUrl('dashboard/chatbot.php?edit=' . $flow['id']); ?>" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-pencil-square"></i> Open in Builder
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </main>
</div>

<script>
function deleteFlow(id) {
    if (!confirm('Delete this chatbot flow?')) return;
    const form = new FormData();
    form.append('action', 'delete_flow');
    form.append('flow_id', id);
    form.append('csrf_token', '<?= CSRF::generateToken(); ?>');
    fetch('<?= baseUrl('dashboard/chatbot.php'); ?>', { method: 'POST', body: form })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('flow-card-' + id)?.remove();
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php endif; ?>

</body>
</html>
