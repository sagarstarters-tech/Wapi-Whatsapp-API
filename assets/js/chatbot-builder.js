/**
 * WAPI SaaS - Visual Chatbot Builder Engine (V2 Full Integration)
 * Core logic for node management, connections, and flow serialization.
 */

let editor;
const id = document.getElementById("drawflow-canvas");

// Initialize Drawflow
document.addEventListener("DOMContentLoaded", () => {
    const canvas = document.getElementById("drawflow-canvas");
    if (!canvas) {
        console.error("Drawflow canvas not found!");
        return;
    }
    
    editor = new Drawflow(canvas);
    editor.reroute = true;
    editor.start();

    // Event Listeners
    editor.on('nodeCreated', function(nodeId) {
        console.log("Node created " + nodeId);
    });

    // Fix for port interaction: ensure ports are clickable even if node has inputs
    canvas.addEventListener('mousedown', (e) => {
        // Find if target is a Drawflow port (input/output)
        if (e.target.classList.contains('input') || e.target.classList.contains('output')) {
             return; // Let Drawflow handle it
        }

        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            e.stopPropagation();
        }
    }, true);
});

/**
 * 1. Node Templates Management
 */
function getNodeTemplate(type) {
    switch (type) {
        case 'start':
            return `
                <div class="node-root">
                    <div class="node-header bg-success text-white"><i class="bi bi-play-circle-fill"></i> Start / Trigger</div>
                    <div class="node-body">
                        <label class="mb-1 small fw-bold">Trigger Keywords</label>
                        <input type="text" class="form-control form-control-sm mb-2" placeholder="Hi, Hello, Start..." df-keywords>
                        <p class="text-muted mb-0" style="font-size: 0.65rem;">Flow starts when user sends any of these keywords (comma separated). Leave empty for any message.</p>
                    </div>
                </div>
            `;
        case 'text':
            return `
                <div>
                    <div class="node-header"><i class="bi bi-chat-left-text whatsapp"></i> Text Message</div>
                    <div class="node-body">
                        <label>Message Body</label>
                        <textarea class="form-control" rows="3" placeholder="Type your message..." df-text></textarea>
                    </div>
                </div>
            `;
        case 'image':
            return `
                <div>
                    <div class="node-header"><i class="bi bi-image success"></i> Image</div>
                    <div class="node-body">
                        <label>Image URL (Public)</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Paste image URL here" df-image-url onchange="updatePreview(this)">
                        <div class="node-preview mt-2" id="preview-image">
                            <i class="bi bi-image placeholder"></i>
                        </div>
                    </div>
                </div>
            `;
        case 'interactive':
            return `
                <div>
                    <div class="node-header bg-warning text-white"><i class="bi bi-ui-checks-grid"></i> Quick Replies</div>
                    <div class="node-body">
                        <label>Message Body</label>
                        <input type="text" class="form-control form-control-sm mb-2" placeholder="Greeting/Prompt..." df-prompt>
                        <label>Reply Buttons</label>
                        <div id="btn-list" class="btn-list"></div>
                        <button class="btn btn-outline-warning btn-sm w-100 mt-2" onclick="addButtonToNode(this)">
                            <i class="bi bi-plus-circle"></i> Add Reply Button
                        </button>
                    </div>
                </div>
            `;
        case 'cta':
            return `
                <div class="node-root">
                    <div class="node-header bg-info text-white"><i class="bi bi-box-arrow-up-right"></i> Buttons (CTA)</div>
                    <div class="node-body">
                        <label>Message Body</label>
                        <textarea class="form-control mb-2" rows="2" placeholder="Promotion/Offer text..." df-message></textarea>
                        
                        <div class="cta-input mb-2 p-2 rounded bg-light border">
                            <label class="d-flex align-items-center gap-1"><i class="bi bi-link-45deg"></i> Website Button</label>
                            <input type="text" class="form-control form-control-sm mb-1" placeholder="Button Title (e.g. Buy Now)" df-link-title>
                            <input type="text" class="form-control form-control-sm" placeholder="https://..." df-link-url>
                        </div>
                        
                        <div class="cta-input p-2 rounded bg-light border">
                            <label class="d-flex align-items-center gap-1"><i class="bi bi-telephone-fill" style="font-size: 0.6rem;"></i> Call Button</label>
                            <input type="text" class="form-control form-control-sm mb-1" placeholder="Button Title (e.g. Call Us)" df-call-title>
                            <input type="text" class="form-control form-control-sm" placeholder="+91..." df-call-number>
                        </div>
                    </div>
                </div>
            `;
        case 'audio':
            return `
                <div>
                    <div class="node-header"><i class="bi bi-mic info"></i> Audio</div>
                    <div class="node-body">
                        <label>Audio URL</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Paste audio URL here" df-audio-url>
                    </div>
                </div>
            `;
        case 'video':
            return `
                <div>
                    <div class="node-header"><i class="bi bi-play-circle-fill danger"></i> Video</div>
                    <div class="node-body">
                        <label>Video URL</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Paste video URL here" df-video-url>
                    </div>
                </div>
            `;
        case 'file':
            return `
                <div>
                    <div class="node-header"><i class="bi bi-file-earmark-arrow-up primary"></i> Document</div>
                    <div class="node-body">
                        <label>File URL</label>
                        <input type="text" class="form-control form-control-sm" placeholder="Paste document URL here" df-file-url>
                    </div>
                </div>
            `;
        case 'condition':
            return `
                <div>
                    <div class="node-header"><i class="bi bi-diagram-2 secondary"></i> Condition</div>
                    <div class="node-body">
                        <label>Keyword Check</label>
                        <input type="text" class="form-control form-control-sm" placeholder="e.g. Sales" df-keyword>
                    </div>
                </div>
            `;
        case 'delay':
            return `
                <div>
                    <div class="node-header"><i class="bi bi-hourglass-split info"></i> Delay</div>
                    <div class="node-body">
                        <label>Wait Duration (Seconds)</label>
                        <input type="number" class="form-control" value="2" min="1" max="60" df-delay-seconds>
                    </div>
                </div>
            `;
        default:
            return `<div>Node type not found</div>`;
    }
}

/**
 * 2. Drag & Drop Engine
 */
function allowDrop(ev) { ev.preventDefault(); }
function drag(ev) { ev.dataTransfer.setData("node", ev.target.getAttribute('data-node')); }
function drop(ev) {
    ev.preventDefault();
    const type = ev.dataTransfer.getData("node");
    addNodeToDrawflow(type, ev.clientX, ev.clientY);
}

function addNodeToDrawflow(type, pos_x, pos_y) {
    if (editor.editor_mode === 'fixed') return false;
    pos_x = pos_x * (editor.precanvas.clientWidth / (editor.precanvas.clientWidth * editor.zoom)) - (editor.precanvas.getBoundingClientRect().x * (editor.precanvas.clientWidth / (editor.precanvas.clientWidth * editor.zoom)));
    pos_y = pos_y * (editor.precanvas.clientHeight / (editor.precanvas.clientHeight * editor.zoom)) - (editor.precanvas.getBoundingClientRect().y * (editor.precanvas.clientHeight / (editor.precanvas.clientHeight * editor.zoom)));

    const template = getNodeTemplate(type);
    let inputs = 1; let outputs = 1;
    if (type === 'start') inputs = 0;
    if (type === 'condition') outputs = 2;
    // Interactive nodes now start with 1 output for a "Default" path
    if (type === 'interactive') outputs = 1; 

    editor.addNode(type, inputs, outputs, pos_x, pos_y, type, {}, template);
}

/**
 * 3. Interactions Logic
 */
function updatePreview(input) {
    const url = input.value;
    const previewContainer = input.closest('.drawflow-node').querySelector('#preview-image');
    if (url) {
        previewContainer.innerHTML = `<img src="${url}" onerror="this.innerHTML='<i class=\\'bi bi-exclamation-triangle-fill danger\\'></i> Error'">`;
    } else {
        previewContainer.innerHTML = '<i class="bi bi-image placeholder"></i>';
    }
}

function addButtonToNode(btn) {
    const btnList = btn.closest('.node-body').querySelector('#btn-list');
    const nodeId = btn.closest('.drawflow-node').id.replace('node-', '');
    const count = btnList.children.length;
    
    if (count >= 3) {
        Swal.fire({ icon: 'warning', title: 'Limit reached', text: 'WhatsApp only supports 3 buttons.', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
        return;
    }

    const btnWrapper = document.createElement('div');
    btnWrapper.className = 'btn-item mb-1';
    btnWrapper.innerHTML = `
        <input type="text" class="form-control form-control-sm border-0 bg-transparent p-0" placeholder="Button text" df-btn-${count}>
        <i class="bi bi-x-circle-fill remove-btn" onclick="removeButtonFromNode(this, ${nodeId})"></i>
    `;
    btnList.appendChild(btnWrapper);
    editor.addNodeOutput(nodeId);
}

function removeButtonFromNode(delBtn, nodeId) {
    const parentContainer = delBtn.closest('.btn-item');
    parentContainer.remove();
    editor.removeNodeOutput(nodeId, 'output_1'); // Simplified removal
}

/**
 * 4. API Integration & Flow Management
 */
function saveFlow() {
    const data = editor.export();
    
    Swal.fire({ title: 'Saving Master Flow...', didOpen: () => Swal.showLoading() });

    fetch('../api/chatbot/save-flow.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: 'Master Flow', flow: data })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Saved!', text: res.message });
        } else {
            Swal.fire({ icon: 'error', title: 'Oops!', text: res.message });
        }
    })
    .catch(err => Swal.fire('Error', 'Connection failed: ' + err.message, 'error'));
}

function exportJSON() {
    const data = editor.export();
    document.getElementById('jsonOutput').innerText = JSON.stringify(data, null, 4);
    new bootstrap.Modal(document.getElementById('jsonModal')).show();
}

function clearCanvas() {
    Swal.fire({ icon: 'warning', title: 'Clear?', text: 'Delete everything?', showCancelButton: true }).then(r => r.isConfirmed && editor.clearModuleSelected());
}

function loadFlow() {
    // Basic loading logic for existing flows could go here
    Swal.fire('Tip', 'Master flow is automatically loaded on start if found.', 'info');
}

/**
 * 5. Test Flow (UI Simulation)
 */
function testFlow() {
    const data = editor.export();
    const nodes = data.drawflow.Home.data;
    
    // Find Start Node
    let currentId = Object.keys(nodes).find(id => nodes[id].inputs.input_1.connections.length === 0) || 1;
    
    Swal.fire({
        title: 'Testing Flow 🚀',
        html: '<div id="test-output" class="text-start p-3 bg-light rounded" style="font-size: 0.85rem; height: 150px; overflow-y: auto;"></div>',
        showConfirmButton: true,
        confirmButtonText: 'Next Step',
        showCancelButton: true,
        cancelButtonText: 'Stop'
    }).then(result => {
        if (result.isConfirmed) {
             // Logic for browser-based simulation would be implemented here as a recursive loop
        }
    });

    const output = document.getElementById('test-output');
    simulateNode(currentId, nodes, output);
}

function simulateNode(id, nodes, output) {
    const node = nodes[id];
    if (!node) return;
    
    output.innerHTML += `<div class="mb-2"><strong>[${node.name.toUpperCase()}]:</strong> Executing node...</div>`;
    
    if (node.name === 'interactive') {
        output.innerHTML += `<div class="text-primary italic">Waiting for user interaction...</div>`;
    } else {
        const next = node.outputs.output_1.connections[0];
        if (next) setTimeout(() => simulateNode(next.node, nodes, output), 1000);
    }
}
