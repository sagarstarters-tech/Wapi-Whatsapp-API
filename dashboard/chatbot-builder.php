<?php
/**
 * WAPI SaaS - Visual Chatbot Flow Builder
 * A modern, node-based UI for creating automated WhatsApp conversations.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$pageTitle = 'Chatbot Flow Builder';
$hideNav = true;

// Custom Assets for this page
$extraCss = [
    'https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.css',
    asset('assets/css/chatbot-builder.css')
];
$extraJs = [
    'https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11',
    asset('assets/js/chatbot-builder.js')
];

include __DIR__ . '/../includes/header.php';
?>

<div class="builder-wrapper">
    <!-- Builder Header / Toolbar -->
    <header class="builder-header">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= baseUrl('dashboard/'); ?>" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Back to Dashboard">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h5 class="mb-0 fw-bold">Chatbot Flow Builder 🤖</h5>
        </div>
        
        <div class="builder-actions">
            <button class="btn btn-light btn-sm px-3" onclick="clearCanvas()">
                <i class="bi bi-trash3 me-1"></i> Clear
            </button>
            <button class="btn btn-light btn-sm px-3" onclick="loadFlow()">
                <i class="bi bi-folder2-open me-1"></i> Load
            </button>
            <button class="btn btn-outline-primary btn-sm px-3" onclick="exportJSON()">
                <i class="bi bi-code-slash me-1"></i> Export JSON
            </button>
            <button class="btn btn-primary btn-sm px-4 shadow-sm" onclick="saveFlow()">
                <i class="bi bi-cloud-check me-1"></i> Save Flow
            </button>
        </div>
    </header>

    <div class="builder-layout">
        <!-- Sidebar: Node Palette -->
        <aside class="builder-sidebar">
            <div class="sidebar-label">Pills / Components</div>
            <div class="node-palette">
                <div class="drag-drawflow" draggable="true" ondragstart="drag(event)" data-node="text">
                    <i class="bi bi-chat-left-text whatsapp"></i>
                    <span>Text Message</span>
                </div>
                <div class="drag-drawflow" draggable="true" ondragstart="drag(event)" data-node="image">
                    <i class="bi bi-image success"></i>
                    <span>Image</span>
                </div>
                <div class="drag-drawflow" draggable="true" ondragstart="drag(event)" data-node="audio">
                    <i class="bi bi-mic info"></i>
                    <span>Audio</span>
                </div>
                <div class="drag-drawflow" draggable="true" ondragstart="drag(event)" data-node="video">
                    <i class="bi bi-play-circle-fill danger"></i>
                    <span>Video</span>
                </div>
                <div class="drag-drawflow" draggable="true" ondragstart="drag(event)" data-node="file">
                    <i class="bi bi-file-earmark-arrow-up primary"></i>
                    <span>File / Doc</span>
                </div>
                <div class="drag-drawflow" draggable="true" ondragstart="drag(event)" data-node="interactive">
                    <i class="bi bi-ui-checks-grid warning"></i>
                    <span>Interactive Buttons</span>
                </div>
                <hr class="my-3 opacity-10">
                <div class="sidebar-label">Logic</div>
                <div class="drag-drawflow" draggable="true" ondragstart="drag(event)" data-node="condition">
                    <i class="bi bi-diagram-2 secondary"></i>
                    <span>Condition</span>
                </div>
                <div class="drag-drawflow" draggable="true" ondragstart="drag(event)" data-node="delay">
                    <i class="bi bi-hourglass-split info"></i>
                    <span>Delay</span>
                </div>
            </div>
            
            <div class="builder-tips mt-auto">
                <h6><i class="bi bi-lightbulb"></i> Tip</h6>
                <p>Drag components onto the canvas and connect their ports to build logic flow.</p>
            </div>
        </aside>

        <!-- Main Canvas Area -->
        <main class="builder-canvas-area" id="drawflow-canvas" ondrop="drop(event)" ondragover="allowDrop(event)">
            <!-- Drawflow will be initialized here -->
            
            <!-- Canvas Controls -->
            <div class="canvas-controls">
                <button onclick="editor.zoom_out()"><i class="bi bi-dash-lg"></i></button>
                <button onclick="editor.zoom_reset()"><i class="bi bi-aspect-ratio"></i></button>
                <button onclick="editor.zoom_in()"><i class="bi bi-plus-lg"></i></button>
            </div>
        </main>
    </div>
</div>

<!-- Modal for JSON Export (Preview) -->
<div class="modal fade" id="jsonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Exported Flow JSON</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <pre id="jsonOutput" class="m-0 p-4 bg-dark text-success" style="max-height: 500px; overflow: auto; font-size: 0.85rem; border-radius: 0 0 8px 8px;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="copyJSON()">Copy to Clipboard</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
