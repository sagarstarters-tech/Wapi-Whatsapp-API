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
        // After node is created, check if it's an interactive node to restore buttons
        const node = editor.getNodeFromId(nodeId);
        if (node.name === 'interactive' && node.data) {
             restoreInteractivePorts(nodeId);
        }
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

    // Auto-load master flow on start
    setTimeout(() => loadFlow(true), 100);
});

function restoreInteractivePorts(nodeId) {
    const node = editor.getNodeFromId(nodeId);
    const nodeEl = document.getElementById('node-' + nodeId);
    if (!nodeEl || !node.data) return;
    
    const btnList = nodeEl.querySelector('#btn-list');
    if (!btnList) return;

    // Clear and reconstruction buttons from data keys (df-btn-0, df-btn-1, etc.)
    btnList.innerHTML = '';
    Object.keys(node.data).forEach(key => {
        if (key.startsWith('btn-')) {
            const index = key.replace('btn-', '');
            const btnWrapper = document.createElement('div');
            btnWrapper.className = 'btn-item mb-1';
            btnWrapper.innerHTML = `
                <input type="text" class="form-control form-control-sm border-0 bg-transparent p-0" placeholder="Button text" df-${key} value="${node.data[key]}">
                <i class="bi bi-x-circle-fill remove-btn" onclick="removeButtonFromNode(this, ${nodeId})"></i>
            `;
            btnList.appendChild(btnWrapper);
        }
    });
}

/**
 * 1. Node Templates Management - Redesigned UI
 */
function getDrawflowStats() {
    return `
        <div class="node-stats-row border-bottom">
            <div class="stat-col"><i class="bi bi-send"></i><span>0</span></div>
            <div class="stat-col"><i class="bi bi-check2-all"></i><span>0</span></div>
            <div class="stat-col"><i class="bi bi-person"></i><span>0</span></div>
            <div class="stat-col"><i class="bi bi-exclamation-triangle"></i><span>0</span></div>
        </div>
        <div class="node-delay-row">
            <span>... Delay</span><span>0 Sec</span>
        </div>
    `;
}

function getNodeTemplate(type) {
    switch (type) {
        case 'start':
            return `
                <div class="node-root">
                    <div class="node-header-custom"><i class="bi bi-play-circle-fill" style="color:#32ADE6;"></i> Start Bot Flow</div>
                    <div class="node-body-content" style="padding:10px;">
                        <input type="text" class="form-control form-control-sm mb-2 text-center" style="background:#e2e8f0; font-weight:bold;" value="Demo_bot" disabled>
                        <div class="small fw-bold text-muted mb-1">Bot trigger keywords</div>
                        <input type="text" class="form-control form-control-sm mb-2" placeholder="Hi, Hello, Start..." df-keywords>
                        <div class="small fw-bold text-muted mb-1">Keyword matching type</div>
                        <select class="form-select form-select-sm mb-2"><option>Exact keyword match</option><option>Contains match</option></select>
                    </div>
                </div>
            `;
        case 'text':
            return `
                <div>
                    <div class="node-header-custom"><i class="bi bi-filter-left" style="color:#4B6EAF;"></i> Text</div>
                    ${getDrawflowStats()}
                    <div class="node-body-content">
                        <textarea class="form-control" rows="3" placeholder="Type your message..." df-text></textarea>
                    </div>
                    <div class="port-labels-container">
                        <div class="port-label-row">
                            <span class="text-start">Message</span>
                            <span class="text-end">Compose Next Message</span>
                        </div>
                    </div>
                </div>
            `;
        case 'image':
            return `
                <div>
                    <div class="node-header-custom"><i class="bi bi-image" style="color:#8C52FF;"></i> Image</div>
                    ${getDrawflowStats()}
                    <div class="node-body-content">
                        <div class="ref-preview" id="preview-image">
                            <!-- Image URL will be appended here via updatePreview -->
                            <i class="bi bi-image" style="font-size:3rem; color:#9ca3af; display:block; text-align:center; padding:20px;"></i>
                        </div>
                        <div class="ref-url-label">Resource URL</div>
                        <input type="text" class="form-control form-control-sm mt-1" placeholder="https://..." df-image-url onchange="updatePreview(this)">
                    </div>
                    <div class="port-labels-container">
                        <div class="port-label-row"><span class="text-start">Message</span><span class="text-end">Compose Next Message</span></div>
                        <div class="port-label-row justify-content-end"><span class="text-end text-muted" style="font-size:0.65rem;">Keyboard Button</span></div>
                    </div>
                </div>
            `;
        case 'interactive':
            return `
                <div>
                    <div class="node-header-custom interactive-hd"><i class="bi bi-menu-button-wide-fill"></i> Interactive</div>
                    ${getDrawflowStats()}
                    <div class="node-body-content p-2">
                        <div class="node-message-box">
                            <strong>Visit Our Site</strong><br>
                            If you are interested to visit our site
                        </div>
                        <textarea class="form-control d-none" df-prompt>Visit Our Site - If you are interested</textarea>
                    </div>
                    <div class="port-labels-container">
                        <div class="port-label-row"><span class="text-start">Reply</span><span class="text-end">Next</span></div>
                        <div class="port-label-row justify-content-end"><span class="text-end fw-bold">Buttons</span></div>
                        <div class="port-label-row justify-content-end"><span class="text-end text-muted">List Messages</span></div>
                        <div class="port-label-row justify-content-end"><span class="text-end text-muted">E-commerce</span></div>
                    </div>
                </div>
            `;
        case 'cta':
            return `
                <div class="node-root">
                    <div class="node-header-custom button-hd"><i class="bi bi-cursor-fill"></i> Button</div>
                    <div class="node-stats-row border-bottom">
                        <div class="stat-col"><i class="bi bi-hand-index"></i><span>0</span></div>
                        <div class="stat-col"><i class="bi bi-person"></i><span>0</span></div>
                        <div class="stat-col"><i class="bi bi-exclamation-triangle border-danger text-danger"></i><span>0</span></div>
                    </div>
                    <div class="node-body-content py-4 text-center">
                        <i class="bi bi-hand-index-thumb" style="font-size:2rem; opacity:0.5;"></i>
                        <input type="hidden" df-message value="Button Event">
                    </div>
                    <div class="port-labels-container">
                        <div class="port-label-row"><span class="text-start">Reply</span><span class="text-end text-muted">Next</span></div>
                        <div class="port-label-row justify-content-end"><span class="text-end">Subscribe to Sequence</span></div>
                    </div>
                </div>
            `;
        case 'audio':
            return `
                <div>
                    <div class="node-header-custom"><i class="bi bi-mic-fill" style="color:#FFB020;"></i> Audio</div>
                    ${getDrawflowStats()}
                    <div class="node-body-content">
                        <div class="ref-preview-audio mb-2">
                            <i class="bi bi-play-circle-fill text-primary" style="font-size:1.5rem;"></i>
                            <div class="flex-grow-1" style="height:3px; background:#c8d3e0; border-radius:3px;"></div>
                            <i class="bi bi-volume-up-fill"></i>
                        </div>
                        <div class="ref-url-label">Resource URL</div>
                        <input type="text" class="form-control form-control-sm mt-1" placeholder="Audio URL..." df-audio-url>
                    </div>
                    <div class="port-labels-container"><div class="port-label-row"><span class="text-start">Message</span><span class="text-end">Compose Next Message</span></div></div>
                </div>
            `;
        case 'video':
            return `
                <div>
                    <div class="node-header-custom"><i class="bi bi-youtube" style="color:#FF3B30;"></i> Video</div>
                    ${getDrawflowStats()}
                    <div class="node-body-content">
                        <div class="ref-preview bg-dark d-flex align-items-center justify-content-center" style="height:80px;">
                            <i class="bi bi-play-circle text-white fs-3"></i>
                        </div>
                        <div class="ref-url-label">Resource URL</div>
                        <input type="text" class="form-control form-control-sm mt-1" placeholder="Video URL..." df-video-url>
                    </div>
                    <div class="port-labels-container"><div class="port-label-row"><span class="text-start">Message</span><span class="text-end">Compose Next Message</span></div></div>
                </div>
            `;
        case 'file':
            return `
                <div>
                    <div class="node-header-custom"><i class="bi bi-paperclip" style="color:#34C759;"></i> File</div>
                    ${getDrawflowStats()}
                    <div class="node-body-content text-center py-3">
                        <i class="bi bi-folder-fill" style="font-size:3rem; color:#FF9500;"></i>
                        <div class="mt-2 text-muted" style="font-size:0.65rem;">NewBuilder... .docx</div>
                        <div class="ref-url-label text-start mt-3">Resource URL</div>
                        <input type="text" class="form-control form-control-sm mt-1" placeholder="File URL..." df-file-url>
                    </div>
                    <div class="port-labels-container"><div class="port-label-row"><span class="text-start">Message</span><span class="text-end">Compose Next Message</span></div></div>
                </div>
            `;
        case 'condition':
            return `
                <div>
                    <div class="node-header-custom"><i class="bi bi-chevron-right" style="color:#AF52DE;"></i> Condition</div>
                    <div class="node-body-content">
                        <label class="small fw-bold">Logic</label>
                        <input type="text" class="form-control form-control-sm" placeholder="if keyword == X" df-keyword>
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
    if (type === 'interactive') outputs = 4;
    if (type === 'cta') outputs = 2;

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

function loadFlow(quiet = false) {
    if (!quiet) Swal.fire({ title: 'Loading Flow...', didOpen: () => Swal.showLoading() });
    
    fetch('../api/chatbot/get-flow.php?name=Master Flow')
    .then(res => res.json())
    .then(res => {
        if (!quiet) Swal.close();
        if (res.success && res.flow) {
            editor.import(res.flow);
            if (!quiet) Swal.fire({ icon: 'success', title: 'Flow Loaded!', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 });
        } else if (!quiet) {
            Swal.fire({ icon: 'info', title: 'No Flow Found', text: 'You haven\'t saved any flow yet. Start by dragging nodes!' });
        }
    })
    .catch(err => {
        if (!quiet) Swal.fire('Error', 'Failed to load flow: ' + err.message, 'error');
        console.error("Load Error:", err);
    });
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
