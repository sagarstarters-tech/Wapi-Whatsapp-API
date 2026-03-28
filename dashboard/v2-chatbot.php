<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$pageTitle = 'Chatbot Builder';
include __DIR__ . '/../includes/v2-header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-8">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= baseUrl('dashboard/v2-index.php'); ?>" class="btn btn-icon bg-white text-slate-500 shadow-sm"><i class="bi bi-chevron-left"></i></a>
            <div>
                <h4 class="m-0 fw-bold">Customer Support Bot</h4>
                <div style="font-size: 0.8125rem; color: var(--dash-slate-500);">Last edited 2 hours ago</div>
            </div>
        </div>
    </div>
    <div class="col-4 text-end">
        <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200 me-2 shadow-sm">Preview</button>
        <button class="btn btn-primary btn-sm shadow-sm px-4">Publish Flow</button>
    </div>
</div>

<div class="card border-0 shadow-premium overflow-hidden" style="border-radius: 16px; height: calc(100vh - 200px);">
    <div class="inbox-layout">
        <!-- Sidebar: Blocks & Components -->
        <div class="inbox-sidebar" style="width: 280px; background: white;">
            <div class="p-3 border-bottom">
                <input type="text" class="form-control form-control-sm" placeholder="Search components..." style="background: var(--dash-slate-50); border: 0; border-radius: 8px;">
            </div>
            
            <div class="flex-1 overflow-auto p-3">
                <div style="font-size: 0.7rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase; margin-bottom: 12px;">Messages</div>
                <div class="builder-palette gap-2 d-flex flex-column">
                    <div class="palette-item d-flex align-items-center gap-2 p-2 border rounded-3 cursor-pointer hover-bg-slate-50" draggable="true" data-type="text">
                        <i class="bi bi-chat-text-fill text-primary"></i> <span style="font-size: 0.875rem; font-weight: 500;">Send Message</span>
                    </div>
                    <div class="palette-item d-flex align-items-center gap-2 p-2 border rounded-3 cursor-pointer hover-bg-slate-50" draggable="true" data-type="interactive">
                        <i class="bi bi-hand-index-thumb-fill text-info"></i> <span style="font-size: 0.875rem; font-weight: 500;">Buttons / Lists</span>
                    </div>
                    <div class="palette-item d-flex align-items-center gap-2 p-2 border rounded-3 cursor-pointer hover-bg-slate-50" draggable="true" data-type="media">
                        <i class="bi bi-image-fill text-warning"></i> <span style="font-size: 0.875rem; font-weight: 500;">Media (Image/Video)</span>
                    </div>
                </div>
                
                <div style="font-size: 0.7rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase; margin: 24px 0 12px;">Logic</div>
                <div class="builder-palette gap-2 d-flex flex-column">
                    <div class="palette-item d-flex align-items-center gap-2 p-2 border rounded-3 cursor-pointer hover-bg-slate-50" draggable="true" data-type="condition">
                        <i class="bi bi-signpost-split-fill text-danger"></i> <span style="font-size: 0.875rem; font-weight: 500;">Condition (if/else)</span>
                    </div>
                    <div class="palette-item d-flex align-items-center gap-2 p-2 border rounded-3 cursor-pointer hover-bg-slate-50" draggable="true" data-type="delay">
                        <i class="bi bi-clock-fill text-secondary"></i> <span style="font-size: 0.875rem; font-weight: 500;">Delay / Wait</span>
                    </div>
                    <div class="palette-item d-flex align-items-center gap-2 p-2 border rounded-3 cursor-pointer hover-bg-slate-50" draggable="true" data-type="action">
                        <i class="bi bi-lightning-fill text-success"></i> <span style="font-size: 0.875rem; font-weight: 500;">Advanced Action</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Canvas Builder -->
        <div class="builder-container flex-1" id="builderCanvas" style="background-color: #fcfcfc;">
            <!-- Simple SVG overlay for connections -->
            <svg id="connectionsLayer" style="position: absolute; width: 100%; height: 100%; pointer-events: none;"></svg>
            
            <!-- Nodes (Mocked for UI demo) -->
            <div class="builder-node shadow-premium" style="top: 100px; left: 100px; border-left: 4px solid var(--dash-primary);">
                <div class="node-header"><i class="bi bi-play-circle-fill text-primary"></i> <span>Start Event</span></div>
                <div class="node-content">Trigger: New Message<br><span class="text-muted">Keywords: hello, hi, help</span></div>
                <div class="node-connector out"></div>
            </div>
            
            <div class="builder-node shadow-premium" style="top: 100px; left: 450px; border-left: 4px solid var(--dash-primary);">
                <div class="node-header"><i class="bi bi-chat-text-fill text-primary"></i> <span>Welcome Msg</span></div>
                <div class="node-content">"Hi! Welcome to our store. How can we help you today?"</div>
                <div class="node-connector in"></div>
                <div class="node-connector out"></div>
            </div>
            
            <div class="builder-node shadow-premium" style="top: 300px; left: 450px; border-left: 4px solid var(--dash-danger);">
                <div class="node-header"><i class="bi bi-signpost-split-fill text-danger"></i> <span>Check Office Hours</span></div>
                <div class="node-content">Condition: Is Monday-Friday?<br>9:00 AM - 6:00 PM</div>
                <div class="node-connector in"></div>
                <div class="node-connector out" style="top: 30%;"></div>
                <div class="node-connector out" style="top: 70%;"></div>
            </div>
            
            <!-- Zoom Controls -->
            <div class="position-absolute bottom-0 start-0 m-4 shadow-sm bg-white rounded-3 overflow-hidden d-flex">
                <button class="btn btn-sm btn-icon border-end"><i class="bi bi-plus"></i></button>
                <div class="px-2 py-1 align-self-center" style="font-size: 0.75rem; font-weight: 600;">100%</div>
                <button class="btn btn-sm btn-icon border-start"><i class="bi bi-dash"></i></button>
                <button class="btn btn-sm btn-icon border-start"><i class="bi bi-fullscreen"></i></button>
            </div>
        </div>
        
        <!-- Right Sidebar: Properties -->
        <div class="inbox-sidebar" style="width: 320px; background: white; border-left: 1px solid var(--dash-slate-200); border-right: 0;">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold">Node Properties</span>
                <i class="bi bi-x-lg text-slate-400 cursor-pointer"></i>
            </div>
            <div class="p-4 flex-1 overflow-auto">
                <label class="form-label fw-bold text-slate-500" style="font-size: 0.75rem; text-transform: uppercase;">Message Content</label>
                <textarea class="form-control mb-4" rows="6" placeholder="Type your WhatsApp message here..." style="font-size: 0.875rem;">Hi! Welcome to our store. How can we help you today?</textarea>
                
                <label class="form-label fw-bold text-slate-500" style="font-size: 0.75rem; text-transform: uppercase;">Buttons (Interactive)</label>
                <div class="d-flex flex-column gap-2 mb-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-plus-circle"></i></span>
                        <input type="text" class="form-control" value="View Catalog">
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" value="Talk to Agent">
                    </div>
                    <button class="btn btn-outline-primary btn-sm mt-1" style="font-size: 0.75rem;">+ Add New Button</button>
                </div>
                
                <hr class="my-4 border-slate-200">
                
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-slate-500" style="font-size: 0.875rem;">Preview on phone</span>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" checked>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.builder-palette .palette-item { transition: all 0.2s; border-color: var(--dash-slate-200); }
.builder-palette .palette-item:hover { border-color: var(--dash-primary); transform: translateY(-2px); shadow: var(--dash-card-shadow); }
.cursor-pointer { cursor: pointer; }
.hover-bg-slate-50:hover { background-color: var(--dash-slate-50); }
.node-connector.out { position: absolute; width: 14px; height: 14px; background: white; border: 3px solid #cbd5e1; border-radius: 50%; right: -7px; top: 50%; margin-top: -7px; cursor: crosshair; transition: all 0.1s; }
.node-connector.in { position: absolute; width: 14px; height: 14px; background: white; border: 3px solid #cbd5e1; border-radius: 50%; left: -7px; top: 50%; margin-top: -7px; cursor: crosshair; }
.node-connector:hover { border-color: var(--dash-primary); background: var(--dash-primary); transform: scale(1.2); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('builderCanvas');
    const nodes = document.querySelectorAll('.builder-node');
    const svg = document.getElementById('connectionsLayer');
    
    let activeNode = null;
    let offsetX, offsetY;
    
    // Position nodes to start
    nodes[0].style.left = '100px'; nodes[0].style.top = '100px';
    nodes[1].style.left = '450px'; nodes[1].style.top = '100px';
    nodes[2].style.left = '450px'; nodes[2].style.top = '300px';

    nodes.forEach(node => {
        node.addEventListener('mousedown', e => {
            if (e.target.classList.contains('node-connector')) return;
            activeNode = node;
            offsetX = e.clientX - node.getBoundingClientRect().left;
            offsetY = e.clientY - node.getBoundingClientRect().top;
            node.style.zIndex = 1000;
        });
    });
    
    document.addEventListener('mousemove', e => {
        if (!activeNode) return;
        
        const canvasRect = canvas.getBoundingClientRect();
        let x = e.clientX - canvasRect.left - offsetX;
        let y = e.clientY - canvasRect.top - offsetY;
        
        activeNode.style.left = x + 'px';
        activeNode.style.top = y + 'px';
        
        updateConnections();
    });
    
    document.addEventListener('mouseup', () => {
        if (activeNode) activeNode.style.zIndex = 10;
        activeNode = null;
    });
    
    function updateConnections() {
        svg.innerHTML = '';
        drawNodeConnection(nodes[0], nodes[1], '#25D366');
        drawNodeConnection(nodes[1], nodes[2], '#cbd5e1');
    }
    
    function drawNodeConnection(n1, n2, color) {
        const c1 = canvas.getBoundingClientRect();
        const r1 = n1.getBoundingClientRect();
        const r2 = n2.getBoundingClientRect();
        
        // Out point of node 1
        const x1 = r1.left - c1.left + r1.width;
        const y1 = r1.top - c1.top + (r1.height / 2);
        
        // In point of node 2
        const x2 = r2.left - c1.left;
        const y2 = r2.top - c1.top + (r2.height / 2);
        
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        const dx = Math.max(Math.abs(x2 - x1) * 0.5, 40);
        path.setAttribute('d', `M ${x1} ${y1} C ${x1 + dx} ${y1}, ${x2 - dx} ${y2}, ${x2} ${y2}`);
        path.setAttribute('stroke', color || '#cbd5e1');
        path.setAttribute('stroke-width', '3');
        path.setAttribute('fill', 'none');
        svg.appendChild(path);
    }
    
    window.addEventListener('resize', updateConnections);
    setTimeout(updateConnections, 100);
});
</script>


<?php include __DIR__ . '/../includes/v2-footer.php'; ?>
