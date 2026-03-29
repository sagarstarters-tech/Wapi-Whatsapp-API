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
function setMatchType(val, btn) {
    document.getElementById('conf-match').value = val;
    const btns = btn.parentElement.querySelectorAll('.cfg-segment-btn');
    btns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function showNodeConfig(nodeId) {
    const node = editor.getNodeFromId(nodeId);
    const configSidebar = document.getElementById('configSidebar');
    const configBody = document.getElementById('configBody');
    const configHeader = configSidebar.querySelector('.config-header h6');

    if (node.name === 'start') {
        configHeader.innerText = "Configure Reference";
    } else {
        configHeader.innerText = "Configure " + node.name.charAt(0).toUpperCase() + node.name.slice(1);
    }
    
    configBody.innerHTML = ''; // Clear existing

    // Generate Form based on type
    let html = '';
    const data = node.data;

    switch (node.name) {
        case 'start':
            html = `
                <div class="mb-3">
                    <label class="cfg-label">Write down the keywords for which the bot will be triggered</label>
                    <input type="text" class="form-control cfg-input" id="conf-keywords" value="${data.keywords || ''}" placeholder="Hello, Hi, Start">
                </div>
                
                <div class="mb-3">
                    <label class="cfg-label">Send reply based on your matching type</label>
                    <div class="cfg-segment-control">
                        <button type="button" class="cfg-segment-btn ${data.match === 'exact' || !data.match ? 'active' : ''}" onclick="setMatchType('exact', this)">Exact keyword match</button>
                        <button type="button" class="cfg-segment-btn ${data.match === 'contains' ? 'active' : ''}" onclick="setMatchType('contains', this)">String match</button>
                    </div>
                    <input type="hidden" id="conf-match" value="${data.match || 'exact'}">
                </div>
                
                <div class="mb-3">
                    <label class="cfg-label">Title</label>
                    <input type="text" class="form-control cfg-input" id="conf-title" value="${data.title || ''}">
                </div>
                
                <div class="row mb-3">
                    <div class="col-6 pe-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="cfg-label mb-0">Add Label(s)</label>
                            <a href="#" class="text-decoration-none" style="font-size: 11px;"><i class="bi bi-plus-circle-fill"></i> New</a>
                        </div>
                        <input type="text" class="form-control cfg-input" id="conf-add-labels" value="${data.addLabels || ''}">
                    </div>
                    <div class="col-6 ps-2">
                        <label class="cfg-label mb-1">Remove Label(s)</label>
                        <input type="text" class="form-control cfg-input mt-3" style="margin-top: 15px !important;" id="conf-remove-labels" value="${data.removeLabels || ''}">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6 pe-2">
                        <label class="cfg-label">Subscribe to Sequence</label>
                        <select class="form-select cfg-input" id="conf-sub-seq">
                            <option value="">Select a Sequence</option>
                            <option value="1" ${data.subSeq === '1' ? 'selected' : ''}>Sequence 1</option>
                        </select>
                    </div>
                    <div class="col-6 ps-2">
                        <label class="cfg-label">Unsubscribe from Sequence</label>
                        <select class="form-select cfg-input" id="conf-unsub-seq">
                            <option value="">Select a Sequence</option>
                            <option value="1" ${data.unsubSeq === '1' ? 'selected' : ''}>Sequence 1</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6 pe-2">
                        <label class="cfg-label">Assign Conversation to a group</label>
                        <select class="form-select cfg-input" id="conf-assign-group">
                            <option value="">Select Team Role</option>
                            <option value="support" ${data.assignGroup === 'support' ? 'selected' : ''}>Support</option>
                        </select>
                    </div>
                    <div class="col-6 ps-2">
                        <label class="cfg-label">Assign conversation to a user</label>
                        <select class="form-select cfg-input" id="conf-assign-user">
                            <option value="">Select Team Member</option>
                            <option value="john" ${data.assignUser === 'john' ? 'selected' : ''}>John Doe</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="cfg-label">Send data to Webhook URL</label>
                    <input type="text" class="form-control cfg-input" id="conf-webhook" value="${data.webhook || ''}">
                </div>
                
            `;
            break;
        case 'text':
            html = `
                <div class="mb-3">
                    <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Please provide your reply message</label>
                    <div class="d-flex align-items-center gap-2 mb-2 mt-1">
                        <button class="btn btn-sm btn-light text-primary border" style="font-size:12px; font-weight: 500; background: #fff;"><i class="bi bi-link-45deg"></i> Custom <i class="bi bi-caret-down-fill" style="font-size:10px;"></i></button>
                        <button class="btn btn-sm btn-light text-primary border" style="font-size:12px; font-weight: 500; background: #fff;"><i class="bi bi-person"></i> Name</button>
                    </div>
                    <div class="position-relative">
                        <textarea class="form-control cfg-input" id="conf-text" rows="5" placeholder="#LEAD_USER_FIRST_NAME# How are you?" style="background: #fafafa; border: 1px solid #ddd;">${data.text || ''}</textarea>
                        <i class="bi bi-emoji-smile position-absolute text-muted" style="top: 8px; right: 10px; cursor:pointer;" title="Emoji"></i>
                    </div>
                </div>
                
                <div class="mb-3 mt-4 pt-3 border-top" style="border-top-color: #ddd !important;">
                    <div class="d-flex justify-content-between">
                        <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Delay in reply - <span id="delay-val">${data.delay || 0}</span> sec</label>
                    </div>
                    <input type="range" class="form-range mt-2" id="conf-delay" min="0" max="60" value="${data.delay || 0}" oninput="document.getElementById('delay-val').innerText = this.value">
                </div>
            `;
            break;
        case 'image':
            html = `
                <div class="mb-3">
                    <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Please provide your reply image</label>
                    <input type="text" class="form-control cfg-input" id="conf-caption" value="${data.caption || ''}" placeholder="Check out that Image" style="background: #fafafa; border: 1px solid #ddd; height: 38px;">
                </div>
                
                <div class="mb-3 mt-4">
                    <div class="upload-box-wrapper" style="border: 1px dashed #007bff; border-radius: 4px; padding: 40px; text-align: center; background: transparent; cursor: pointer; position: relative;">
                        <input type="file" id="conf-upload" accept="image/png, image/jpeg" style="position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;" onchange="document.getElementById('conf-url').value = this.files[0] ? this.files[0].name : ''">
                        <i class="bi bi-cloud-arrow-up-fill" style="font-size: 2rem; color: #007bff;"></i>
                    </div>
                    <div class="text-center mt-2" style="font-size: 12px; color: #999; font-weight: 500;">
                        Supported types: png, jpg
                    </div>
                    <!-- Hidden URL field to maintain backend compatibility -->
                    <input type="hidden" id="conf-url" value="${data['image-url'] || ''}">
                </div>
                
                <div class="mb-3 mt-4 pt-3 border-top" style="border-top-color: #ddd !important;">
                    <div class="d-flex justify-content-between">
                        <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Delay in reply - <span id="delay-val">${data.delay || 0}</span> sec</label>
                    </div>
                    <input type="range" class="form-range mt-2" id="conf-delay" min="0" max="60" value="${data.delay || 0}" oninput="document.getElementById('delay-val').innerText = this.value">
                </div>
            `;
            break;
        case 'video':
            html = `
                <div class="mb-3">
                    <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Please provide your reply video url</label>
                    <input type="text" class="form-control cfg-input" id="conf-video-url" value="${data['video-url'] || ''}" placeholder="Put your video url here or click the upload box." style="background: #fafafa; border: 1px solid #ddd; height: 38px;">
                </div>
                
                <div class="mb-3 mt-4">
                    <div class="upload-box-wrapper" style="border: 1px dashed #007bff; border-radius: 4px; padding: 40px; text-align: center; background: transparent; cursor: pointer; position: relative;">
                        <input type="file" id="conf-upload" accept="video/mp4, video/x-flv, video/x-ms-wmv" style="position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;" onchange="document.getElementById('conf-video-url').value = this.files[0] ? this.files[0].name : ''">
                        <i class="bi bi-cloud-arrow-up-fill" style="font-size: 2rem; color: #007bff;"></i>
                    </div>
                    <div class="text-center mt-2" style="font-size: 12px; color: #999; font-weight: 500;">
                        Supported types: mp4, flv, wmv
                    </div>
                </div>
                
                <div class="mb-3 mt-4 pt-3 border-top" style="border-top-color: #ddd !important;">
                    <div class="d-flex justify-content-between">
                        <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Delay in reply - <span id="delay-val">${data.delay || 0}</span> sec</label>
                    </div>
                    <input type="range" class="form-range mt-2" id="conf-delay" min="0" max="60" value="${data.delay || 0}" oninput="document.getElementById('delay-val').innerText = this.value">
                </div>
            `;
            break;
        case 'audio':
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
        case 'cta':
            html = `
                <div class="mb-3">
                    <label class="form-label fw-bold">Message Text</label>
                    <textarea class="form-control" id="conf-cta-text" rows="3">${data.text || ''}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Button Text</label>
                    <input type="text" class="form-control" id="conf-cta-btn-text" value="${data.btnText || ''}" placeholder="Visit Website">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Button URL</label>
                    <input type="text" class="form-control" id="conf-cta-url" value="${data.url || ''}" placeholder="https://...">
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
            newData.title = document.getElementById('conf-title').value;
            newData.addLabels = document.getElementById('conf-add-labels').value;
            newData.removeLabels = document.getElementById('conf-remove-labels').value;
            newData.subSeq = document.getElementById('conf-sub-seq').value;
            newData.unsubSeq = document.getElementById('conf-unsub-seq').value;
            newData.assignGroup = document.getElementById('conf-assign-group').value;
            newData.assignUser = document.getElementById('conf-assign-user').value;
            newData.webhook = document.getElementById('conf-webhook').value;
            break;
        case 'text':
            newData.text = document.getElementById('conf-text').value;
            newData.delay = document.getElementById('conf-delay').value;
            break;
        case 'image':
            newData['image-url'] = document.getElementById('conf-url').value;
            newData.caption = document.getElementById('conf-caption').value;
            newData.delay = document.getElementById('conf-delay').value;
            break;
        case 'video':
            newData['video-url'] = document.getElementById('conf-video-url').value;
            newData.delay = document.getElementById('conf-delay').value;
            break;
        case 'audio':
        case 'file':
            const key = node.name === 'file' ? 'file-url' : node.name + '-url';
            newData[key] = document.getElementById('conf-url').value;
            newData.caption = document.getElementById('conf-caption').value;
            break;
        case 'interactive':
            newData.prompt = document.getElementById('conf-prompt').value;
            // Buttons are saved as we add/edit them usually, but we ensure consistency here
            break;
        case 'cta':
            newData.text = document.getElementById('conf-cta-text').value;
            newData.btnText = document.getElementById('conf-cta-btn-text').value;
            newData.url = document.getElementById('conf-cta-url').value;
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
            const titleBox = nodeEl.querySelector('.cfg-title-display');
            if (titleBox) titleBox.value = node.data.title || 'Demo_bot';
            
            const kwBox = nodeEl.querySelector('.cfg-kw-display');
            if (kwBox) kwBox.textContent = node.data.keywords || 'hi, hello';
            
            const matchEl = nodeEl.querySelector('.cfg-match-display');
            if (matchEl) matchEl.textContent = (node.data.match === 'contains' ? 'String match' : 'Exact keyword match');
            break;
        }
        case 'text': {
            // Stats are static for now, no message preview string displayed inside the canvas text node in screenshot!
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
        case 'cta': {
            const promptBox = nodeEl.querySelector('.node-message-box');
            if (promptBox && node.data.text) promptBox.innerHTML = node.data.text;
            const urlLabel = nodeEl.querySelector('.url-label');
            if (urlLabel && node.data.btnText) urlLabel.textContent = node.data.btnText;
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
                <div class="node-root" style="min-width: 250px;">
                    <div class="node-header-custom" style="justify-content:flex-start; background: #EEF2F6; border-radius: 12px 12px 0 0; border-bottom: none;"><i class="bi bi-person-walking" style="color:#333;"></i> Start Bot Flow</div>
                    <div class="node-body-content px-3 pt-1 pb-3" style="background: #EEF2F6;">
                        <input type="text" class="cfg-title-display w-100 text-center mb-3" style="background:#e1e9f4; border:none; border-radius:4px; font-weight:500; font-size:12px; padding:4px; color:#5c719e;" value="Demo_bot" disabled>
                        
                        <div style="font-size:10px; color:#9ca3af; line-height: 1;">Bot trigger keywords</div>
                        <div class="cfg-kw-display" style="font-size:12px; color:#4e5d78; margin-bottom: 8px;">hi, hello</div>
                        
                        <div style="font-size:10px; color:#9ca3af; line-height: 1;">Keyword matching type</div>
                        <div class="cfg-match-display" style="font-size:12px; color:#4e5d78;">Exact keyword match</div>
                    </div>
                    <div class="port-labels-container" style="background: #EEF2F6; border-top:1px dashed #cbd5e1;">
                        <div class="port-label-row d-flex justify-content-end align-items-center" style="padding-right: 15px;">
                            <span style="font-size:10px; color:#555; position: relative; left: -8px;">Compose Next Message</span>
                        </div>
                    </div>
                </div>
            `;
        case 'text':
            return `
                <div class="node-root" style="min-width: 250px; background: #EEF2F6;">
                    <div class="node-header-custom" style="border-bottom:none; background: #EEF2F6;"><i class="bi bi-list" style="color:#4e5d78;"></i> Text</div>
                    <div class="node-stats-row border-0 mt-1 px-2 pb-4" style="background: #EEF2F6; justify-content: space-around;">
                        <div class="stat-col"><i class="bi bi-cursor-fill text-primary" style="opacity: 0.8;"></i><span style="font-size:9px;">Sent</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-check-circle text-success" style="opacity: 0.8;"></i><span style="font-size:9px;">Delivered</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-person-x text-primary" style="opacity: 0.8; font-size: 14px;"></i><span style="font-size:9px;">Subscribers</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-heart-fill text-danger" style="opacity: 0.8;"></i><span style="font-size:9px;">Errors</span><span style="font-size:11px;">0</span></div>
                    </div>
                    <div class="port-labels-container" style="background:#EEF2F6; border-top:1px dashed #cbd5e1; margin-top:15px; padding: 10px 0;">
                        <div class="port-label-row d-flex justify-content-between align-items-center" style="padding: 2px 15px;">
                            <span style="font-size:11px; color:#555; position: relative; right: -8px;">Message</span>
                            <span style="font-size:10px; color:#555; position: relative; left: -8px;">Compose Next Message</span>
                        </div>
                    </div>
                </div>
            `;
        case 'image':
            return `
                <div class="node-root" style="min-width: 250px; background: #EEF2F6;">
                    <div class="node-header-custom" style="border-bottom:none; background: #EEF2F6;"><i class="bi bi-camera-fill" style="color:#4e5d78;"></i> Image</div>
                    <div class="node-stats-row border-0 mt-1 px-2 pb-4" style="background: #EEF2F6; justify-content: space-around;">
                        <div class="stat-col"><i class="bi bi-cursor-fill text-primary" style="opacity: 0.8;"></i><span style="font-size:9px;">Sent</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-check-circle text-success" style="opacity: 0.8;"></i><span style="font-size:9px;">Delivered</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-person-x text-primary" style="opacity: 0.8; font-size: 14px;"></i><span style="font-size:9px;">Subscribers</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-heart-fill text-danger" style="opacity: 0.8;"></i><span style="font-size:9px;">Errors</span><span style="font-size:11px;">0</span></div>
                    </div>
                    <div class="node-body-content text-center pb-2 pt-0" style="background:#EEF2F6;">
                        <div class="node-image-preview" style="background: transparent;">
                            <i class="bi bi-hand-index" style="font-size:2rem; color:#4e5d78;"></i>
                        </div>
                    </div>
                    <div class="port-labels-container" style="background:#EEF2F6; border-top:1px dashed #cbd5e1; padding: 10px 0;">
                        <div class="port-label-row d-flex justify-content-between align-items-center" style="padding: 2px 15px;">
                            <span style="font-size:11px; color:#555; position: relative; right: -8px;">Message</span>
                            <span style="font-size:10px; color:#555; position: relative; left: -8px;">Compose Next Message</span>
                        </div>
                        <div class="port-label-row d-flex justify-content-end align-items-center mt-2" style="padding: 2px 15px;">
                            <span style="font-size:10px; color:#555; position: relative; left: -8px;">Keyboard Button</span>
                        </div>
                        <div class="port-label-row d-flex justify-content-end align-items-center mt-2" style="padding: 2px 15px;">
                            <span style="font-size:10px; color:#555; position: relative; left: -8px;">Add Buttons</span>
                        </div>
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
                    <div class="node-header-custom button-hd"><i class="bi bi-cursor-fill"></i> Link Button</div>
                    <div class="node-body-content py-3 p-2 text-center border-bottom">
                         <div class="node-message-box text-start small text-muted">Configure in sidebar</div>
                         <div class="mt-2 text-primary fw-bold border rounded p-1" style="border-color: #007AFF !important;"><i class="bi bi-box-arrow-up-right me-1"></i><span class="url-label">Click Here</span></div>
                    </div>
                    <div class="port-labels-container">
                        <div class="port-label-row"><span class="text-start">Reply</span><span class="text-end text-muted">Next</span></div>
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
                <div class="node-root" style="min-width: 250px; background: #EEF2F6;">
                    <div class="node-header-custom" style="border-bottom:none; background: #EEF2F6;"><i class="bi bi-camera-video-fill" style="color:#4e5d78;"></i> Video</div>
                    <div class="node-stats-row border-0 mt-1 px-2 pb-4" style="background: #EEF2F6; justify-content: space-around;">
                        <div class="stat-col"><i class="bi bi-cursor-fill text-primary" style="opacity: 0.8;"></i><span style="font-size:9px;">Sent</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-check-circle text-success" style="opacity: 0.8;"></i><span style="font-size:9px;">Delivered</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-person-x text-primary" style="opacity: 0.8; font-size: 14px;"></i><span style="font-size:9px;">Subscribers</span><span style="font-size:11px;">0</span></div>
                        <div class="stat-col"><i class="bi bi-heart-fill text-danger" style="opacity: 0.8;"></i><span style="font-size:9px;">Errors</span><span style="font-size:11px;">0</span></div>
                    </div>
                    <div class="node-body-content text-center pb-2 pt-0" style="background:#EEF2F6;">
                        <div class="node-image-preview" style="background: transparent;">
                            <i class="bi bi-hand-index" style="font-size:2rem; color:#4e5d78;"></i>
                        </div>
                    </div>
                    <div class="port-labels-container" style="background:#EEF2F6; border-top:1px dashed #cbd5e1; padding: 10px 0;">
                        <div class="port-label-row d-flex justify-content-between align-items-center" style="padding: 2px 15px;">
                            <span style="font-size:11px; color:#555; position: relative; right: -8px;">Message</span>
                            <span style="font-size:10px; color:#555; position: relative; left: -8px;">Compose Next Message</span>
                        </div>
                        <div class="port-label-row d-flex justify-content-end align-items-center mt-2" style="padding: 2px 15px;">
                            <span style="font-size:10px; color:#555; position: relative; left: -8px;">Keyboard Button</span>
                        </div>
                        <div class="port-label-row d-flex justify-content-end align-items-center mt-2" style="padding: 2px 15px;">
                            <span style="font-size:10px; color:#555; position: relative; left: -8px;">Add Buttons</span>
                        </div>
                    </div>
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
    if (type === 'image' || type === 'video') outputs = 3;

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

