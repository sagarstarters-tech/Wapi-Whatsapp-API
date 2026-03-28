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
    <!-- Top Palette Toolbar -->
    <header class="top-palette align-items-center bg-light border-bottom px-3 py-2 d-flex justify-content-between">
        <div class="d-flex align-items-center gap-2 drag-items-row">
            <!-- Brand / Logo Icon (Optional) -->
            <div class="brand-icon me-3 bg-white border rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                <i class="bi bi-robot text-primary fs-5"></i>
            </div>
            
            <!-- Draggable Icons -->
            <div class="drag-drawflow icon-item" draggable="true" ondragstart="drag(event)" data-node="text" title="Text Message">
                <i class="bi bi-fonts" style="color: #4B6EAF;"></i>
            </div>
            <div class="drag-drawflow icon-item" draggable="true" ondragstart="drag(event)" data-node="image" title="Image">
                <i class="bi bi-image" style="color: #8C52FF;"></i>
            </div>
            <div class="drag-drawflow icon-item" draggable="true" ondragstart="drag(event)" data-node="audio" title="Audio">
                <i class="bi bi-mic-fill" style="color: #FFB020;"></i>
            </div>
            <div class="drag-drawflow icon-item" draggable="true" ondragstart="drag(event)" data-node="video" title="Video">
                <i class="bi bi-youtube" style="color: #FF3B30;"></i>
            </div>
            <div class="drag-drawflow icon-item" draggable="true" ondragstart="drag(event)" data-node="file" title="File">
                <i class="bi bi-paperclip" style="color: #34C759;"></i>
            </div>
            <div class="drag-drawflow icon-item" draggable="true" ondragstart="drag(event)" data-node="interactive" title="Buttons">
                <i class="bi bi-ui-radios" style="color: #FF9500;"></i>
            </div>
            <div class="drag-drawflow icon-item" draggable="true" ondragstart="drag(event)" data-node="condition" title="Condition">
                <i class="bi bi-chevron-right" style="color: #AF52DE;"></i>
            </div>
            <div class="drag-drawflow icon-item" draggable="true" ondragstart="drag(event)" data-node="start" title="Start Flow">
                <i class="bi bi-play-circle-fill" style="color: #32ADE6;"></i>
            </div>
        </div>
        
        <div class="d-flex align-items-center gap-2 builder-actions">
            <!-- Back Button -->
            <a href="<?= baseUrl('dashboard/'); ?>" class="action-circle-btn" title="Back to Dashboard">
                <i class="bi bi-arrow-left"></i>
            </a>
            <!-- Reset Button -->
            <button class="action-circle-btn" onclick="clearCanvas()" title="Clear Canvas">
                <i class="bi bi-x-lg"></i>
            </button>
            <!-- Flow Name Input -->
            <input type="text" id="flowNameInput" class="form-control form-control-sm text-center mx-2" value="Demo_bot" style="width: 150px; font-weight: 500;">
            
            <!-- Save Button -->
            <button class="btn btn-sm btn-success d-flex align-items-center px-3" onclick="saveFlow()" style="font-weight: 500;">
                <i class="bi bi-save2 me-2"></i> Save
            </button>
        </div>
    </header>

    <div class="builder-layout d-flex h-100">
        <!-- Configuration Panel (Hidden by default, slides in from left) -->
        <aside class="config-sidebar bg-light border-end d-none" id="configSidebar" style="width: 350px; flex-shrink: 0; display: flex; flex-direction: column;">
            <div class="config-header bg-secondary text-white text-center py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold m-auto">Configure Button</h6>
            </div>
            <div class="config-body p-3 flex-grow-1 overflow-auto" id="configBody">
                <!-- Dynamic Content Form will load here -->
            </div>
            <div class="config-footer p-3 border-top d-flex justify-content-between align-items-center bg-white">
                <button class="btn btn-primary btn-sm px-4" onclick="saveConfig()"><i class="bi bi-save me-1"></i> Save</button>
                <button class="btn btn-light border btn-sm px-3" onclick="closeConfig()"><i class="bi bi-x-circle me-1"></i> Close</button>
            </div>
        </aside>

        <!-- Main Canvas Area -->
        <main class="builder-canvas-area flex-grow-1 position-relative" id="drawflow-canvas" ondrop="drop(event)" ondragover="allowDrop(event)">
            <!-- Drawflow will be initialized here -->
            
            <!-- Canvas Controls -->
            <div class="canvas-controls position-absolute bottom-0 end-0 m-3">
                <button class="btn btn-light shadow-sm me-1" onclick="editor.zoom_out()"><i class="bi bi-dash-lg"></i></button>
                <button class="btn btn-light shadow-sm me-1" onclick="editor.zoom_reset()"><i class="bi bi-aspect-ratio"></i></button>
                <button class="btn btn-light shadow-sm" onclick="editor.zoom_in()"><i class="bi bi-plus-lg"></i></button>
            </div>
        </main>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
