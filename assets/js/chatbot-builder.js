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

    editor.on('connectionSelected', function(conn) {
        Swal.fire({
            title: 'Disconnect Line?',
            text: 'Are you sure you want to remove this connection?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (result.isConfirmed) {
                editor.removeSingleConnection(conn.output_id, conn.input_id, conn.output_class, conn.input_class);
            }
        });
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

    // Canvas Mouse Wheel Zoom Logic
    canvas.addEventListener('wheel', (e) => {
        if (!editor || editor.editor_mode === 'fixed') return;
        e.preventDefault(); // Prevent standard page scroll
        if (e.deltaY > 0) {
            editor.zoom_out();
        } else {
            editor.zoom_in();
        }
    }, { passive: false });

    // Auto-load master flow on start
    setTimeout(() => loadFlow(true), 100);

    // Live update start node badge when flow name changes
    const flowNameInput = document.getElementById('flowNameInput');
    if (flowNameInput) {
        flowNameInput.addEventListener('input', () => {
            const exportData = editor.export();
            const nodes = exportData.drawflow.Home.data || {};
            Object.keys(nodes).forEach(id => {
                if (nodes[id].name === 'start') {
                    editor.updateNodeDataFromId(id, { ...nodes[id].data, title: flowNameInput.value });
                    updateNodePreview(id);
                }
            });
        });
    }
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
                
                <div class="mb-3 mt-4 pt-3 border-top" style="border-top-color: #ddd !important;">
                    <div class="d-flex justify-content-between">
                        <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Delay in reply - <span id="delay-val">${data.delay || 0}</span> sec</label>
                    </div>
                    <input type="range" class="form-range mt-2" id="conf-delay" min="0" max="60" value="${data.delay || 0}" oninput="document.getElementById('delay-val').innerText = this.value">
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
                    <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Please provide your reply image URL</label>
                    <input type="text" class="form-control cfg-input" id="conf-url" value="${data['image-url'] || ''}" placeholder="https://..." style="background: #fafafa; border: 1px solid #ddd; height: 38px;">
                </div>
                
                <div class="mb-3 mt-4">
                    <div class="upload-box-wrapper" style="border: 1px dashed #007bff; border-radius: 4px; padding: 40px; text-align: center; background: transparent; cursor: pointer; position: relative;" onclick="document.getElementById('conf-upload-media').click()">
                        <!-- Currently visually mimics an upload box for design purposes -->
                        <i class="bi bi-cloud-arrow-up-fill" style="font-size: 2rem; color: #007bff;"></i>
                        <input type="file" id="conf-upload-media" accept="image/png, image/jpeg, image/webp" style="display:none;" onchange="uploadMediaToBot(this, 'conf-url', 'upload-status-media')">
                        <div id="upload-status-media" class="mt-2 text-muted" style="font-size:12px; font-weight: 500;">Click to upload (png, jpg, webp)</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Message (Optional)</label>
                    <div class="d-flex align-items-center gap-2 mb-2 mt-1">
                        <button class="btn btn-sm btn-light text-primary border" style="font-size:12px; font-weight: 500; background: #fff;"><i class="bi bi-link-45deg"></i> Custom <i class="bi bi-caret-down-fill" style="font-size:10px;"></i></button>
                        <button class="btn btn-sm btn-light text-primary border" style="font-size:12px; font-weight: 500; background: #fff;"><i class="bi bi-person"></i> Name</button>
                    </div>
                    <div class="position-relative">
                        <textarea class="form-control cfg-input" id="conf-caption" rows="4" placeholder="Type your image message here..." style="background: #fafafa; border: 1px solid #ddd;">${data.caption || ''}</textarea>
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
        case 'video':
            html = `
                <div class="mb-3">
                    <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Please provide your reply video url</label>
                    <input type="text" class="form-control cfg-input" id="conf-video-url" value="${data['video-url'] || ''}" placeholder="Put your video url here or click the upload box." style="background: #fafafa; border: 1px solid #ddd; height: 38px;">
                </div>
                
                <div class="mb-3 mt-4">
                    <div class="upload-box-wrapper" style="border: 1px dashed #007bff; border-radius: 4px; padding: 40px; text-align: center; background: transparent; cursor: pointer; position: relative;" onclick="document.getElementById('conf-upload-media-vid').click()">
                        <!-- Currently visually mimics an upload box for design purposes -->
                        <i class="bi bi-cloud-arrow-up-fill" style="font-size: 2rem; color: #007bff;"></i>
                        <input type="file" id="conf-upload-media-vid" accept="video/mp4, video/x-flv, video/x-ms-wmv" style="display:none;" onchange="uploadMediaToBot(this, 'conf-video-url', 'upload-status-media-vid')">
                        <div id="upload-status-media-vid" class="mt-2 text-muted" style="font-size:12px; font-weight: 500;">Click to upload (mp4, flv, wmv)</div>
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
            html = `
                <div class="mb-3">
                    <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Please provide reply audio url</label>
                    <input type="text" class="form-control cfg-input" id="conf-audio-url" value="${data['audio-url'] || ''}" placeholder="Put audio url here or click the upload box." style="background: #fafafa; border: 1px solid #ddd; height: 38px;">
                </div>
                
                <div class="mb-3 mt-4">
                    <div class="upload-box-wrapper" style="border: 1px dashed #007bff; border-radius: 4px; padding: 40px; text-align: center; background: transparent; cursor: pointer; position: relative;" onclick="document.getElementById('conf-upload-media-audio').click()">
                        <i class="bi bi-cloud-arrow-up-fill" style="font-size: 2rem; color: #007bff;"></i>
                        <input type="file" id="conf-upload-media-audio" accept="audio/amr, audio/mp3, audio/wav, audio/mpeg" style="display:none;" onchange="uploadMediaToBot(this, 'conf-audio-url', 'upload-status-media-audio')">
                        <div id="upload-status-media-audio" class="mt-2 text-muted" style="font-size:12px; font-weight: 500;">Supported types: amr, mp3, wav</div>
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
        case 'file':
            html = `
                <div class="mb-3">
                    <label class="form-label fw-bold">Resource URL</label>
                    <input type="text" class="form-control" id="conf-url" value="${data['file-url'] || ''}" placeholder="https://...">
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
                <input type="hidden" id="conf-cta-text" value="&#8203;">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size: 13px; color: #555;">Button Text</label>
                    <input type="text" class="form-control cfg-input" id="conf-cta-btn-text" value="${data.btnText || ''}" placeholder="Visit Website" style="background: #fafafa; border: 1px solid #ddd; height: 38px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size: 13px; color: #555;">Button URL</label>
                    <input type="text" class="form-control cfg-input" id="conf-cta-url" value="${data.url || ''}" placeholder="https://..." style="background: #fafafa; border: 1px solid #ddd; height: 38px;">
                </div>
                
                <div class="mb-3 mt-4 pt-3 border-top" style="border-top-color: #ddd !important;">
                    <div class="d-flex justify-content-between">
                        <label class="cfg-label" style="font-weight: 500; font-size: 13px; color: #555;">Delay in reply - <span id="delay-val">${data.delay || 0}</span> sec</label>
                    </div>
                    <input type="range" class="form-range mt-2" id="conf-delay" min="0" max="60" value="${data.delay || 0}" oninput="document.getElementById('delay-val').innerText = this.value">
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

function saveConfig(silent = false) {
    if (!currentNodeId) return;
    const node = editor.getNodeFromId(currentNodeId);
    if (!node) return;
    const newData = { ...node.data };

    // Grabbing data from our dynamic form
    switch (node.name) {
        case 'start':
            newData.keywords = document.getElementById('conf-keywords') ? document.getElementById('conf-keywords').value : newData.keywords;
            newData.match = document.getElementById('conf-match') ? document.getElementById('conf-match').value : newData.match;
            newData.delay = document.getElementById('conf-delay') ? document.getElementById('conf-delay').value : newData.delay;
            break;
        case 'text':
            newData.text = document.getElementById('conf-text') ? document.getElementById('conf-text').value : newData.text;
            newData.delay = document.getElementById('conf-delay') ? document.getElementById('conf-delay').value : newData.delay;
            break;
        case 'image':
            newData['image-url'] = document.getElementById('conf-url') ? document.getElementById('conf-url').value : newData['image-url'];
            newData.caption = document.getElementById('conf-caption') ? document.getElementById('conf-caption').value : newData.caption;
            newData.delay = document.getElementById('conf-delay') ? document.getElementById('conf-delay').value : newData.delay;
            break;
        case 'video':
            newData['video-url'] = document.getElementById('conf-video-url') ? document.getElementById('conf-video-url').value : newData['video-url'];
            newData.delay = document.getElementById('conf-delay') ? document.getElementById('conf-delay').value : newData.delay;
            break;
        case 'audio':
            newData['audio-url'] = document.getElementById('conf-audio-url') ? document.getElementById('conf-audio-url').value : newData['audio-url'];
            newData.delay = document.getElementById('conf-delay') ? document.getElementById('conf-delay').value : newData.delay;
            break;
        case 'file':
            newData['file-url'] = document.getElementById('conf-url') ? document.getElementById('conf-url').value : newData['file-url'];
            newData.caption = document.getElementById('conf-caption') ? document.getElementById('conf-caption').value : newData.caption;
            break;
        case 'interactive':
            newData.prompt = document.getElementById('conf-prompt') ? document.getElementById('conf-prompt').value : newData.prompt;
            break;
        case 'cta':
            newData.text = document.getElementById('conf-cta-text') ? document.getElementById('conf-cta-text').value : newData.text;
            newData.btnText = document.getElementById('conf-cta-btn-text') ? document.getElementById('conf-cta-btn-text').value : newData.btnText;
            newData.url = document.getElementById('conf-cta-url') ? document.getElementById('conf-cta-url').value : newData.url;
            newData.delay = document.getElementById('conf-delay') ? document.getElementById('conf-delay').value : newData.delay;
            break;
    }

    editor.updateNodeDataFromId(currentNodeId, newData);
    updateNodePreview(currentNodeId);
    
    if (!silent) {
        Swal.fire({
            icon: 'success',
            title: 'Updated',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1000
        });
    }
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
            if (titleBox) titleBox.textContent = node.data.title || document.getElementById('flowNameInput')?.value || 'Demo_bot';
            
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
                <div class="node-root start-node-root" style="min-width: 250px;">
                    <div class="start-node-header">
                        <i class="bi bi-person-walking"></i>
                        <span>Start Bot Flow</span>
                    </div>
                    <div class="start-node-body">
                        <div class="start-node-badge-wrap">
                            <span class="start-node-flow-badge cfg-title-display">Demo_bot</span>
                        </div>
                        <div class="start-node-info-row">
                            <span class="start-node-label">Bot trigger keywords</span>
                            <span class="cfg-kw-display start-node-value">hi, hello</span>
                        </div>
                        <div class="start-node-info-row">
                            <span class="start-node-label">Keyword matching type</span>
                            <span class="cfg-match-display start-node-value">Exact keyword match</span>
                        </div>
                    </div>
                    <div class="start-node-footer">
                        <span class="start-node-port-label">Compose Next Message</span>
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
                         <div class="mt-2 text-primary fw-bold border rounded p-1" style="border-color: #007AFF !important;"><i class="bi bi-box-arrow-up-right me-1"></i><span class="url-label">Click Here</span></div>
                    </div>
                    <div class="port-labels-container">
                        <div class="port-label-row"><span class="text-start">Reply</span><span class="text-end text-muted">Next</span></div>
                    </div>
                </div>
            `;
        case 'audio':
            return `
                <div class="node-root" style="min-width: 250px; background: #EEF2F6;">
                    <div class="node-header-custom" style="border-bottom:none; background: #EEF2F6;"><i class="bi bi-mic-fill" style="color:#4e5d78;"></i> Audio</div>
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
    if (type === 'image' || type === 'video' || type === 'audio') outputs = 3;

    const defaultData = {};
    if (type === 'start') {
        defaultData.keywords = 'hi, hello';
        defaultData.match = 'exact';
        defaultData.delay = 0;
        defaultData.title = document.getElementById('flowNameInput')?.value || 'Demo_bot';
    }

    const nodeId = editor.addNode(type, inputs, outputs, pos_x, pos_y, type, defaultData, template);
    
    // After creation, sync badge/preview with actual data
    if (type === 'start') {
        setTimeout(() => updateNodePreview(nodeId), 50);
    }
}

/**
 * 4. API Integration & Flow Management
 */
function validateFlow(data) {
    try {
        const nodes = data.drawflow.Home.data;
        const nodeIds = Object.keys(nodes);
        
        if (nodeIds.length === 0) {
            return { valid: false, message: 'Canvas is empty. Please add some nodes to build a flow.' };
        }

        const startNodes = nodeIds.filter(id => nodes[id].name === 'start');
        if (startNodes.length === 0) {
            return { valid: false, message: 'Missing Start node! You must have exactly one "Start/Trigger" node.' };
        }
        if (startNodes.length > 1) {
            return { valid: false, message: 'Multiple Start nodes found! You can only have one Start node per flow.' };
        }
        
        const startNode = nodes[startNodes[0]];
        const startConns = startNode.outputs.output_1 ? startNode.outputs.output_1.connections : [];
        if (!startConns || startConns.length === 0) {
            return { valid: false, message: 'Start node is disconnected! Please connect it to your first message.' };
        }

        for (let id of nodeIds) {
            const node = nodes[id];
            const ndata = node.data;
            
            if (node.name === 'text' && (!ndata.text || ndata.text.trim() === '')) {
                return { valid: false, message: 'A Text node is empty. Please configure it.', nodeId: id };
            }
            if (node.name === 'image' && (!ndata['image-url'] || ndata['image-url'].trim() === '')) {
                return { valid: false, message: 'An Image node is missing its file URL. Please configure it.', nodeId: id };
            }
            if (node.name === 'video' && (!ndata['video-url'] || ndata['video-url'].trim() === '')) {
                return { valid: false, message: 'A Video node is missing its file URL.', nodeId: id };
            }
            if (node.name === 'audio' && (!ndata['audio-url'] || ndata['audio-url'].trim() === '')) {
                return { valid: false, message: 'An Audio node is missing its file URL.', nodeId: id };
            }
            if (node.name === 'file' && (!ndata['file-url'] || ndata['file-url'].trim() === '')) {
                return { valid: false, message: 'A File/Document node is missing its URL.', nodeId: id };
            }
            if (node.name === 'cta' && (!ndata.url || ndata.url.trim() === '' || !ndata.btnText || ndata.btnText.trim() === '')) {
                return { valid: false, message: 'A Link Button node is incomplete. Both Button Text and URL are required.', nodeId: id };
            }
            if (node.name === 'interactive') {
                if (!ndata.prompt || ndata.prompt.trim() === '') {
                    return { valid: false, message: 'An Interactive Button node is missing its main message.', nodeId: id };
                }
                const btns = Object.keys(ndata).filter(k => k.startsWith('btn-'));
                if (btns.length === 0) {
                    return { valid: false, message: 'An Interactive Button node must have at least one button configured.', nodeId: id };
                }
            }
        }

        return { valid: true };
    } catch (err) {
        console.error('Validation error:', err);
        return { valid: true }; // Fallback to allow saving if validation logic errors
    }
}

function saveFlow() {
    // Auto sync any currently open node config before validating!
    if (currentNodeId && !document.getElementById('configSidebar').classList.contains('d-none')) {
        saveConfig(true); 
    }

    const data = editor.export();
    
    // Validate Flow before saving
    const validation = validateFlow(data);
    if (!validation.valid) {
        if (validation.nodeId) {
            // Highlight the problematic node visually if a nodeId is returned
            const nodeEl = document.getElementById('node-' + validation.nodeId);
            if (nodeEl) {
                nodeEl.style.boxShadow = '0 0 15px rgba(220, 53, 69, 0.8)';
                setTimeout(() => { nodeEl.style.boxShadow = ''; }, 3000);
            }
        }
        Swal.fire({ icon: 'warning', title: 'Attention Required', text: validation.message });
        return; // Abort saving
    }

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

/**
 * 5. Media Upload Handler
 */
async function uploadMediaToBot(inputElement, targetInputId, msgElementId) {
    if (!inputElement.files || inputElement.files.length === 0) return;
    const file = inputElement.files[0];
    const formData = new FormData();
    formData.append('file', file);
    
    const msgEl = document.getElementById(msgElementId);
    if (msgEl) msgEl.innerHTML = '<span class="text-primary spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';

    // The PHP endpoint we created is at /wapi/api/upload_media.php. 
    // Assuming this page is /wapi/admin/chatbot-builder.php
    try {
        const response = await fetch('../api/upload_media.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.status === 'success') {
            document.getElementById(targetInputId).value = data.url;
            if (msgEl) msgEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Uploaded!</span>';
        } else {
            if (msgEl) msgEl.innerHTML = '<span class="text-danger">' + data.message + '</span>';
            alert('Upload failed: ' + data.message);
        }
    } catch(err) {
        console.error('API Error:', err);
        if (msgEl) msgEl.innerHTML = '<span class="text-danger">HTTP Error during upload</span>';
    } finally {
        inputElement.value = ''; // allow re-upload
    }
}

/* ============================================================
 *  MY FLOWS PANEL  –  List / Load / Delete / New
 * ============================================================ */

function openFlowsPanel() {
    const panel   = document.getElementById('flowsPanel');
    const overlay = document.getElementById('flowsPanelOverlay');
    panel.style.right  = '0';
    overlay.style.display = 'block';
    loadFlowsList();
}

function closeFlowsPanel() {
    const panel   = document.getElementById('flowsPanel');
    const overlay = document.getElementById('flowsPanelOverlay');
    panel.style.right  = '-420px';
    overlay.style.display = 'none';
}

function loadFlowsList() {
    const container = document.getElementById('flowsList');
    container.innerHTML = `
        <div class="text-center text-muted py-5" style="font-size:13px;">
            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div><br>Loading your flows...
        </div>`;

    fetch('../api/chatbot/list-flows.php')
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                container.innerHTML = `<p class="text-danger text-center mt-4">Failed to load flows.</p>`;
                return;
            }
            if (!res.flows || res.flows.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-wind fs-2 d-block mb-2"></i>
                        No saved flows yet.<br>
                        <small>Click <strong>Create New Flow</strong> to start.</small>
                    </div>`;
                return;
            }
            container.innerHTML = res.flows.map(flow => {
                const updatedDate = flow.updated_at
                    ? new Date(flow.updated_at).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' })
                    : '';
                return `
                <div class="flow-item-card d-flex align-items-center justify-content-between mb-2 p-3"
                     style="background:#f8f9ff; border:1px solid #e3e8f0; border-radius:10px; transition: box-shadow 0.15s;">
                    <div style="min-width:0; flex:1;">
                        <div class="fw-semibold text-truncate" style="color:#1a1a2e; font-size:14px;" title="${escapeHtml(flow.name)}">
                            <i class="bi bi-diagram-3-fill text-primary me-1" style="font-size:12px;"></i>
                            ${escapeHtml(flow.name)}
                        </div>
                        <div class="text-muted" style="font-size:11px; margin-top:2px;">
                            <i class="bi bi-clock me-1"></i>${updatedDate}
                        </div>
                    </div>
                    <div class="d-flex gap-2 ms-2 flex-shrink-0">
                        <button class="btn btn-sm btn-outline-primary px-2 py-1"
                                onclick="openFlow(${flow.id}, '${escapeHtml(flow.name).replace(/'/g,"\\'")}');"
                                title="Edit Flow" style="font-size:12px;">
                            <i class="bi bi-pencil-fill"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger px-2 py-1"
                                onclick="deleteFlow(${flow.id}, '${escapeHtml(flow.name).replace(/'/g,"\\'")}');"
                                title="Delete Flow" style="font-size:12px;">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </div>
                </div>`;
            }).join('');
        })
        .catch(() => {
            container.innerHTML = `<p class="text-danger text-center mt-4">Connection error.</p>`;
        });
}

function openFlow(flowId, flowName) {
    Swal.fire({ title: 'Loading "' + flowName + '"...', didOpen: () => Swal.showLoading() });

    fetch('../api/chatbot/get-flow.php?id=' + encodeURIComponent(flowId))
        .then(r => r.json())
        .then(res => {
            Swal.close();
            if (res.success && res.flow) {
                editor.clearModuleSelected();
                editor.import(res.flow);
                const nameInput = document.getElementById('flowNameInput');
                if (nameInput) nameInput.value = res.flow_name || flowName;
                setTimeout(() => updateAllNodePreviews(), 100);
                closeFlowsPanel();
                Swal.fire({ icon: 'success', title: 'Loaded!', text: '"' + flowName + '" is ready to edit.', timer: 1500, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Could not load flow.' });
            }
        })
        .catch(() => Swal.fire('Error', 'Connection failed.', 'error'));
}

function deleteFlow(flowId, flowName) {
    Swal.fire({
        icon: 'warning',
        title: 'Delete Flow?',
        html: `Are you sure you want to permanently delete <strong>${escapeHtml(flowName)}</strong>?<br><small class="text-muted">This cannot be undone.</small>`,
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete!',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (!result.isConfirmed) return;

        Swal.fire({ title: 'Deleting...', didOpen: () => Swal.showLoading() });

        fetch('../api/chatbot/delete-flow.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ flow_id: flowId })
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Deleted!', timer: 1200, showConfirmButton: false });
                loadFlowsList(); // Refresh panel list
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            }
        })
        .catch(() => Swal.fire('Error', 'Connection failed.', 'error'));
    });
}

function newFlow() {
    Swal.fire({
        title: 'New Flow Name',
        input: 'text',
        inputPlaceholder: 'e.g. Welcome_Bot',
        inputAttributes: { maxlength: 80 },
        showCancelButton: true,
        confirmButtonText: 'Create',
        inputValidator: (value) => {
            if (!value || value.trim() === '') return 'Please enter a flow name!';
        }
    }).then(result => {
        if (!result.isConfirmed) return;
        editor.clearModuleSelected();
        const nameInput = document.getElementById('flowNameInput');
        if (nameInput) nameInput.value = result.value.trim();
        closeFlowsPanel();
        Swal.fire({ icon: 'success', title: 'Canvas cleared!', text: 'Start building "' + result.value.trim() + '"', timer: 1500, showConfirmButton: false });
    });
}

function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
