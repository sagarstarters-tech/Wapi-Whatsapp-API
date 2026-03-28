<?php
/**
 * WAPI SaaS - Chatbot Flow Builder
 * Visual Node-based Editor
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$flowId = isset($_GET['id']) ? sanitizeInt($_GET['id']) : 0;
$hideNav = true; // Use dashboard full-width layout

// If specific flow ID provided, fetch it
$flow = null;
if ($flowId > 0) {
    $flow = $db->fetch("SELECT * FROM chatbot_flows WHERE id = ? AND user_id = ?", [$flowId, $userId]);
    if (!$flow) redirect('dashboard/chatbot.php');
}

$pageTitle = 'Chatbot Flow Builder';
$extraCss = [
    asset('assets/css/dashboard.css'),
    asset('assets/css/builder.css')
];
$extraJs = [
    'https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js',
    asset('assets/js/flow-builder.js')
];

include __DIR__ . '/../includes/header.php';
?>

<div class="builder-root d-flex flex-column" style="height: 100vh; overflow: hidden; background: #f8fafc;">
    <!-- Builder Header -->
    <header class="builder-header d-flex align-items-center justify-content-between px-4 py-3 border-bottom bg-white shadow-sm" style="z-index: 1001; min-height: 70px;">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= baseUrl('dashboard/chatbot.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Back to Dashboard"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h5 class="mb-0 fw-bold" id="flowNameDisplay"><?= e($flow['name'] ?? 'Untitled Flow'); ?></h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 0.75rem;">Last saved: <span id="lastSavedTime">Never</span></span>
                    <span class="badge bg-success" style="font-size: 0.65rem;">Auto-save ON</span>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center bg-light rounded-pill p-1">
                <button class="btn btn-icon btn-sm border-0" id="btnZoomOut"><i class="bi bi-dash-lg"></i></button>
                <span class="text-muted px-2 small fw-bold" style="min-width: 50px; text-align: center;" id="zoomLevel">100%</span>
                <button class="btn btn-icon btn-sm border-0" id="btnZoomIn"><i class="bi bi-plus-lg"></i></button>
            </div>
            <div class="vr mx-1"></div>
            <button class="btn btn-light btn-sm fw-semibold border px-3" id="btnSaveDraft"><i class="bi bi-cloud-check me-1"></i> Save Draft</button>
            <button class="btn btn-primary btn-sm fw-bold px-4 shadow-sm" id="btnPublish"><i class="bi bi-rocket-takeoff-fill me-1"></i> Publish</button>
        </div>
    </header>

    <div class="d-flex flex-grow-1" style="overflow: hidden;">
        <!-- Node Library Sidebar -->
        <aside class="builder-sidebar p-3">
            <div class="mb-4">
                <label class="property-label mb-3">CONVERSATION STEPS</label>
                
                <div class="node-library-item" draggable="true" data-type="text">
                    <div class="node-icon" style="background: #25D366;"><i class="bi bi-chat-left-text"></i></div>
                    <span>Send Message</span>
                </div>
                
                <div class="node-library-item" draggable="true" data-type="image">
                    <div class="node-icon" style="background: #3b82f6;"><i class="bi bi-image"></i></div>
                    <span>Image / Video</span>
                </div>
                
                <div class="node-library-item" draggable="true" data-type="buttons">
                    <div class="node-icon" style="background: #f59e0b;"><i class="bi bi-menu-button-wide"></i></div>
                    <span>Buttons / CTA</span>
                </div>
                
                <div class="node-library-item" draggable="true" data-type="list">
                    <div class="node-icon" style="background: #8b5cf6;"><i class="bi bi-list-ul"></i></div>
                    <span>List Menu</span>
                </div>
            </div>

            <div class="mb-4">
                <label class="property-label mb-3">LOGIC & FLOW</label>
                
                <div class="node-library-item" draggable="true" data-type="condition">
                    <div class="node-icon" style="background: #ef4444;"><i class="bi bi-diagram-3"></i></div>
                    <span>Condition (If/Else)</span>
                </div>
                
                <div class="node-library-item" draggable="true" data-type="delay">
                    <div class="node-icon" style="background: #64748b;"><i class="bi bi-hourglass-split"></i></div>
                    <span>Wait / Delay</span>
                </div>

                <div class="node-library-item" draggable="true" data-type="api">
                    <div class="node-icon" style="background: #ec4899;"><i class="bi bi-box-arrow-up-right"></i></div>
                    <span>External API</span>
                </div>
            </div>

            <div class="mt-auto p-3 bg-light rounded" style="font-size: 0.75rem;">
                <p class="mb-2 fw-bold text-secondary"><i class="bi bi-patch-question me-1"></i> PRO TIP</p>
                Drag elements onto the canvas to start building your flow. Connect them with lines by dragging from the dots.
            </div>
        </aside>

        <!-- Main Canvas -->
        <main class="builder-canvas-wrapper flex-grow-1" id="canvasContainer">
            <div id="builderCanvas" style="width: 5000px; height: 5000px; transform: translate(0, 0) scale(1);">
                <!-- Nodes and SVG connections will be rendered here via JS -->
                <svg id="svgContainer" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0; pointer-events: none;"></svg>
            </div>
        </main>

        <!-- Properties Panel (Context Sensitive) -->
        <aside class="properties-panel border-start bg-white" id="propertiesPanel">
            <!-- Empty state when no node is selected -->
            <div class="text-center py-5 text-muted" id="propEmptyState">
                <i class="bi bi-mouse2" style="font-size: 2.5rem; opacity: 0.3;"></i>
                <p class="mt-3 px-4">Select a step on the canvas to edit its properties.</p>
            </div>

            <!-- Content Area (filled by JS) -->
            <div id="propContent" class="d-none">
                <!-- Shared Header -->
                <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="node-icon small" id="propIcon"></div>
                        <h6 class="mb-0 fw-bold" id="propTypeTitle">Text Message</h6>
                    </div>
                </div>

                <div id="dynamicFields">
                    <!-- Specific fields rendered here -->
                </div>
                
                <hr class="my-4">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-danger btn-sm" id="btnDeleteNode"><i class="bi bi-trash3 me-1"></i> Delete Step</button>
                    <button class="btn btn-light btn-sm" id="btnDuplicateNode"><i class="bi bi-layers me-1"></i> Duplicate</button>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Modal for Flow Settings (Keywords, Name) -->
<div class="modal fade" id="flowSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Flow Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-600">Flow Name</label>
                    <input type="text" id="flowNameInput" class="form-control" placeholder="e.g., Customer Support FAQ" value="<?= e($flow['name'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-600">Trigger Keywords</label>
                    <input type="text" id="triggerKeywordsInput" class="form-control" placeholder="Type and press enter (e.g., hello, help, price)" value="<?= e($flow['trigger_keywords'] ?? ''); ?>">
                    <div class="form-text mt-1">Separate keywords with commas. If any of these are received, this flow starts.</div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-600">Match Type</label>
                    <select id="matchTypeInput" class="form-select">
                        <option value="contains" <?= ($flow['match_type'] ?? '') == 'contains' ? 'selected' : ''; ?>>Contains Keyword</option>
                        <option value="exact" <?= ($flow['match_type'] ?? '') == 'exact' ? 'selected' : ''; ?>>Exact Match</option>
                        <option value="starts_with" <?= ($flow['match_type'] ?? '') == 'starts_with' ? 'selected' : ''; ?>>Starts With</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btnSaveFlowSettings">Save Settings</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Flow Data from PHP
    window.INITIAL_FLOW_DATA = <?= json_encode($flow); ?>;
    window.BASE_URL = '<?= baseUrl(); ?>';
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
