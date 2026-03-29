let editor;
let currentNodeId = null;

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
        const node = editor.getNodeFromId(nodeId);
        if (node.name === 'interactive' && node.data) {
             restoreInteractivePorts(nodeId);
        }
    });

    editor.on('nodeSelected', function(nodeId) {
        currentNodeId = nodeId;
        showNodeConfig(nodeId);
    });

    editor.on('nodeUnselected', function() {
        // Optional: close sidebar on unselect? 
        // User might prefer it stay open for the clicked node until they close it manually.
    });

    // Fix for port interaction
    canvas.addEventListener('mousedown', (e) => {
        if (e.target.classList.contains('input') || e.target.classList.contains('output')) {
             return;
        }
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            e.stopPropagation();
        }
    }, true);

    // Auto-load master flow on start
    setTimeout(() => loadFlow(true), 100);
});

/**
 * Sidebar Config Logic
 */
function showNodeConfig(nodeId) {
    const node = editor.getNodeFromId(nodeId);
    const configSidebar = document.getElementById('configSidebar');
    const configBody = document.getElementById('configBody');
    const configHeader = configSidebar.querySelector('.config-header h6');

    configHeader.innerText = "Configure " + node.name.charAt(0).toUpperCase() + node.name.slice(1);
    configBody.innerHTML = ''; // Clear existing

    // Generate Form based on type
    let html = '';
    const data = node.data;

    switch (node.name) {
        case 'start':
            html = `
                <div class="mb-3">
                    <label class="form-label fw-bold">Trigger Keywords</label>
                    <input type="text" class="form-control" id="conf-keywords" value="${data.keywords || ''}" placeholder="Hi, Hello (comma separated)">
                    <div class="form-text">Bot starts when user sends these words.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Matching Mode</label>
                    <select class="form-select" id="conf-match">
                        <option value="exact" ${data.match === 'exact' ? 'selected' : ''}>Exact match</option>
                        <option value="contains" ${data.match === 'contains' ? 'selected' : ''}>Contains keyword</option>
                    </select>
                </div>
            `;
            break;
        case 'text':
            html = `
                <div class="mb-3">
                    <label class="form-label fw-bold">Message Content</label>
                    <textarea class="form-control" id="conf-text" rows="5">${data.text || ''}</textarea>
                </div>
            `;
            break;
        case 'image':
        case 'audio':
        case 'video':
        case 'file':
            const key = node.name === 'file' ? 'file-url' : node.name + '-url';
            html = `
                <div class="mb-3">
                    <label class="form-label fw-bold">Resource URL</label>
                    <input type="text" class="form-control" id="conf-url" value="${data[key] || ''}" placeholder="https://...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Caption (Optional)</label>
                    <input type="text" class="form-control" id="conf-caption" value="${data.caption || ''}">
                </div>
            `;
            break;
        case 'interactive':
            html = `
                <div class="mb-3">
                    <label class="form-label fw-bold">Message Prompt</label>
                    <input type="text" class="form-control" id="conf-prompt" value="${data.prompt || ''}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-primary">Buttons Configuration</label>
                    <div id="sidebar-btn-list" class="mb-2"></div>
                    <button class="btn btn-outline-primary btn-sm w-100" onclick="addButtonToSelectedNode()">+ Add Button</button>
                </div>
            `;
            break;
        default:
            html = `<p class="text-muted">No specific configuration for this node.</p>`;
    }

    configBody.innerHTML = html;
    
    // Special handling for interactive buttons in sidebar
    if (node.name === 'interactive') {
        renderSidebarButtons(nodeId);
    }

    openConfig();
}

function openConfig() {
    const sidebar = document.getElementById('configSidebar');
    sidebar.classList.remove('d-none');
}

function closeConfig() {
    const sidebar = document.getElementById('configSidebar');
    sidebar.classList.add('d-none');
}

function saveConfig() {
    if (!currentNodeId) return;
    const node = editor.getNodeFromId(currentNodeId);
    const newData = { ...node.data };

    // Grabbing data from our dynamic form
    switch (node.name) {
        case 'start':
            newData.keywords = document.getElementById('conf-keywords').value;
            newData.match = document.getElementById('conf-match').value;
            break;
        case 'text':
            newData.text = document.getElementById('conf-text').value;
            break;
        case 'image':
        case 'audio':
        case 'video':
        case 'file':
            const key = node.name === 'file' ? 'file-url' : node.name + '-url';
            newData[key] = document.getElementById('conf-url').value;
            newData.caption = document.getElementById('conf-caption').value;
            break;
        case 'interactive':
            newData.prompt = document.getElementById('conf-prompt').value;
            // Buttons are saved as we add/edit them usually, but we ensure consistency here
            break;
    }

    editor.updateNodeDataFromId(currentNodeId, newData);
    updateNodePreview(currentNodeId);
    
    Swal.fire({
        icon: 'success',
        title: 'Updated',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1000
    });
}

/**
 * Update Node UI on canvas after config change
 */
function updateNodePreview(nodeId) {
    const node = editor.getNodeFromId(nodeId);
    const nodeEl = document.getElementById('node-' + nodeId);
    if (!nodeEl) return;

    switch (node.name) {
        case 'start': {
            const kw = node.data.keywords || 'hi, hello';
            const match = node.data.match || 'exact';
            const kwBox = nodeEl.querySelector('.node-message-box');
            if (kwBox) kwBox.textContent = kw;
            const matchEl = nodeEl.querySelector('.small.opacity-75');
            if (matchEl) matchEl.textContent = (match === 'contains' ? 'Contains keyword' : 'Exact keyword match');
            break;
        }
        case 'text': {
            const box = nodeEl.querySelector('.node-message-box');
            if (box) box.textContent = node.data.text || 'Type your message in sidebar...';
            break;
        }
        case 'image':
        case 'video':
        case 'audio':
        case 'file': {
            const urlKey = node.name === 'file' ? 'file-url' : node.name + '-url';
            const urlEl = nodeEl.querySelector('.small.text-muted.text-truncate') || nodeEl.querySelector('.small.text-muted');
            if (urlEl) urlEl.textContent = node.data[urlKey] || 'Click to set URL';
            break;
        }
        case 'interactive': {
            const promptBox = nodeEl.querySelector('.node-message-box');
            if (promptBox && node.data.prompt) promptBox.innerHTML = '<strong>' + node.data.prompt + '</strong>';
            break;
        }
    }
}

function updateAllNodePreviews() {
    const exportData = editor.export();
    const nodes = exportData.drawflow.Home.data || {};
    Object.keys(nodes).forEach(id => updateNodePreview(id));
}

function renderSidebarButtons(nodeId) {
    const node = editor.getNodeFromId(nodeId);
    const container = document.getElementById('sidebar-btn-list');
    container.innerHTML = '';

    Object.keys(node.data).forEach(key => {
        if (key.startsWith('btn-')) {
            const row = document.createElement('div');
            row.className = 'd-flex gap-2 mb-2';
            row.innerHTML = `
                <input type="text" class="form-control form-control-sm" value="${node.data[key]}" onchange="updateSidebarBtnText('${key}', this.value)">
                <button class="btn btn-danger btn-sm" onclick="removeButtonFromSelectedNode('${key}')"><i class="bi bi-trash"></i></button>
            `;
            container.appendChild(row);
        }
    });
}

function updateSidebarBtnText(key, val) {
    if (!currentNodeId) return;
    const node = editor.getNodeFromId(currentNodeId);
    node.data[key] = val;
    editor.updateNodeDataFromId(currentNodeId, node.data);
}

function addButtonToSelectedNode() {
    if (!currentNodeId) return;
    const node = editor.getNodeFromId(currentNodeId);
    const currentBtns = Object.keys(node.data).filter(k => k.startsWith('btn-'));
    
    if (currentBtns.length >= 3) {
        Swal.fire({ icon: 'warning', title: 'WhatsApp limit: 3 buttons' });
        return;
    }

    const nextIdx = currentBtns.length;
    node.data['btn-' + nextIdx] = "New Button";
    editor.updateNodeDataFromId(currentNodeId, node.data);
    editor.addNodeOutput(currentNodeId);
    renderSidebarButtons(currentNodeId);
}

function removeButtonFromSelectedNode(key) {
    if (!currentNodeId) return;
    const node = editor.getNodeFromId(currentNodeId);
    delete node.data[key];
    editor.updateNodeDataFromId(currentNodeId, node.data);
    editor.removeNodeOutput(currentNodeId, 'output_' + (Object.keys(node.data).filter(k => k.startsWith('btn-')).length + 1));
    renderSidebarButtons(currentNodeId);
}

function restoreInteractivePorts(nodeId) {
    // Legacy integration - might not be needed with sidebar but kept for safety
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
                        <div class="node-message-box py-1" style="min-height:30px; border-style:dashed;">hi, hello</div>
                        <div class="small fw-bold text-muted mt-2 mb-1">Keyword matching type</div>
                        <div class="small opacity-75">Exact keyword match</div>
                    </div>
                </div>
            `;
        case 'text':
            return `
                <div>
                    <div class="node-header-custom"><i class="bi bi-filter-left" style="color:#4B6EAF;"></i> Text</div>
                    ${getDrawflowStats()}
                    <div class="node-body-content">
                        <div class="node-message-box">Type your message in sidebar...</div>
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
                            <i class="bi bi-image" style="font-size:3rem; color:#9ca3af; display:block; text-align:center; padding:20px;"></i>
                        </div>
                        <div class="ref-url-label">Resource URL</div>
                        <div class="small text-muted text-truncate">Click to set URL</div>
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
                        <div class="small text-muted">Audio link...</div>
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
                        <div class="small text-muted text-truncate">Video link...</div>
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
                    </div>
                    <div class="port-labels-container"><div class="port-label-row"><span class="text-start">Message</span><span class="text-end">Compose Next Message</span></div></div>
                </div>
            `;
        case 'condition':
            return `
                <div>
                    <div class="node-header-custom"><i class="bi bi-chevron-right" style="color:#AF52DE;"></i> Condition</div>
                    <div class="node-body-content">
                        <div class="node-message-box py-1 text-center">Set Logic In Sidebar</div>
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
 * 4. API Integration & Flow Management
 */
function saveFlow() {
    const data = editor.export();
    const flowName = document.getElementById('flowNameInput').value || 'Master Flow';
    
    Swal.fire({ title: 'Saving Flow...', didOpen: () => Swal.showLoading() });

    fetch('../api/chatbot/save-flow.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: flowName, flow: data })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Saved!', text: res.message, timer: 1500 });
        } else {
            Swal.fire({ icon: 'error', title: 'Oops!', text: res.message });
        }
    })
    .catch(err => Swal.fire('Error', 'Connection failed: ' + err.message, 'error'));
}

function loadFlow(quiet = false) {
    if (!quiet) Swal.fire({ title: 'Loading Flow...', didOpen: () => Swal.showLoading() });
    
    // Load latest flow for this user (not hardcoded name)
    fetch('../api/chatbot/get-flow.php?load_latest=1')
    .then(res => res.json())
    .then(res => {
        if (!quiet) Swal.close();
        if (res.success && res.flow) {
            editor.import(res.flow);
            // Update flow name in header input
            if (res.flow_name) {
                const nameInput = document.getElementById('flowNameInput');
                if (nameInput) nameInput.value = res.flow_name;
            }
            // Update all node previews to show actual saved data
            setTimeout(() => updateAllNodePreviews(), 100);
        } else if (!quiet) {
            Swal.fire({ icon: 'info', title: 'No Flow Found', text: 'Start by dragging nodes from the top bar!' });
        }
    })
    .catch(err => {
        if (!quiet) Swal.fire('Error', 'Failed to load flow', 'error');
    });
}

function clearCanvas() {
    Swal.fire({ 
        icon: 'warning', 
        title: 'Clear Canvas?', 
        text: 'This will delete all blocks. Are you sure?', 
        showCancelButton: true 
    }).then(r => r.isConfirmed && editor.clearModuleSelected());
}

