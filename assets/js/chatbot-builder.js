/**
 * WAPI SaaS - Visual Chatbot Flow Builder
 * Drag & Drop Node-Based Flow Editor (v2 - Fixed)
 */

class ChatbotFlowBuilder {
    constructor(canvasEl) {
        this.canvas = canvasEl;
        this.canvasInner = canvasEl.querySelector('.builder-canvas-inner');
        this.svg = this.canvasInner.querySelector('.connections-svg');
        this.nodes = {};
        this.connections = [];
        this.nodeCounter = 0;
        this.selectedNode = null;

        // Drag state
        this.draggingNode = null;
        this.dragStartX = 0;
        this.dragStartY = 0;
        this.dragNodeStartX = 0;
        this.dragNodeStartY = 0;

        // Pan state
        this.pan = { x: 0, y: 0 };
        this.zoom = 1;
        this.isPanning = false;
        this.panStartX = 0;
        this.panStartY = 0;
        this.panStartPanX = 0;
        this.panStartPanY = 0;

        // Connection state
        this.isConnecting = false;
        this.connectFromNode = null;
        this.connectFromPort = null;
        this.tempLine = null;

        this.init();
    }

    init() {
        this._setupCanvasEvents();
        this._setupToolbar();
        this._setupKeyboard();
        this.addStartNode();
        this._centerView();
    }

    // ========== Canvas Pan & Zoom ==========
    _setupCanvasEvents() {
        // Mouse down on canvas -> start panning
        this.canvas.addEventListener('mousedown', (e) => {
            // Only pan if clicking on canvas background (not on a node)
            if (e.target === this.canvas || e.target.classList.contains('builder-canvas-inner') ||
                e.target.tagName === 'svg' || e.target.classList.contains('canvas-grid')) {
                this.isPanning = true;
                this.panStartX = e.clientX;
                this.panStartY = e.clientY;
                this.panStartPanX = this.pan.x;
                this.panStartPanY = this.pan.y;
                this.canvas.style.cursor = 'grabbing';
                this._deselectAll();
                e.preventDefault();
            }
        });

        // Mouse move -> pan or drag node or draw connection
        window.addEventListener('mousemove', (e) => {
            if (this.isPanning) {
                this.pan.x = this.panStartPanX + (e.clientX - this.panStartX);
                this.pan.y = this.panStartPanY + (e.clientY - this.panStartY);
                this._applyTransform();
            }
            if (this.draggingNode) {
                this._onDragMove(e);
            }
            if (this.isConnecting) {
                this._drawTempLine(e);
            }
        });

        // Mouse up -> stop everything
        window.addEventListener('mouseup', (e) => {
            if (this.isPanning) {
                this.isPanning = false;
                this.canvas.style.cursor = 'grab';
            }
            if (this.draggingNode) {
                const el = this.canvasInner.querySelector(`[data-id="${this.draggingNode}"]`);
                if (el) el.classList.remove('dragging');
                this.draggingNode = null;
            }
            if (this.isConnecting) {
                this._finishConnect(e);
            }
        });

        // Wheel -> zoom
        this.canvas.addEventListener('wheel', (e) => {
            e.preventDefault();
            const oldZoom = this.zoom;
            const delta = e.deltaY > 0 ? -0.08 : 0.08;
            this.zoom = Math.max(0.25, Math.min(2.5, this.zoom + delta));
            // Zoom towards mouse position
            const rect = this.canvas.getBoundingClientRect();
            const mx = e.clientX - rect.left;
            const my = e.clientY - rect.top;
            this.pan.x = mx - (mx - this.pan.x) * (this.zoom / oldZoom);
            this.pan.y = my - (my - this.pan.y) * (this.zoom / oldZoom);
            this._applyTransform();
            this._updateZoomLabel();
        }, { passive: false });
    }

    _applyTransform() {
        this.canvasInner.style.transform = `translate(${this.pan.x}px, ${this.pan.y}px) scale(${this.zoom})`;
    }

    _updateZoomLabel() {
        const el = document.getElementById('zoomLevel');
        if (el) el.textContent = Math.round(this.zoom * 100) + '%';
    }

    _centerView() {
        const rect = this.canvas.getBoundingClientRect();
        this.pan.x = rect.width / 4;
        this.pan.y = rect.height / 4;
        this._applyTransform();
    }

    setZoom(val) {
        const oldZoom = this.zoom;
        this.zoom = Math.max(0.1, Math.min(2.5, val));
        
        // Zoom relative to center
        const rect = this.canvas.getBoundingClientRect();
        const cx = rect.width / 2;
        const cy = rect.height / 2;
        
        this.pan.x = cx - (cx - this.pan.x) * (this.zoom / oldZoom);
        this.pan.y = cy - (cy - this.pan.y) * (this.zoom / oldZoom);
        
        this._applyTransform();
        this._updateZoomLabel();
    }

    centerCanvas() { this._centerView(); }

    // ========== Toolbar ==========
    _setupToolbar() {
        document.querySelectorAll('.component-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.type;
                // Place new node near center of current view
                const rect = this.canvas.getBoundingClientRect();
                const cx = (rect.width / 2 - this.pan.x) / this.zoom;
                const cy = (rect.height / 2 - this.pan.y) / this.zoom;
                this.addNode(type, cx + (Math.random() - 0.5) * 100, cy + (Math.random() - 0.5) * 100);
            });
        });
    }

    // ========== Keyboard ==========
    _setupKeyboard() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Delete' && this.selectedNode && this.selectedNode !== 'node_start') {
                if (!e.target.closest('input, textarea, select')) {
                    this.deleteNode(this.selectedNode);
                }
            }
        });
    }

    // ========== Convert screen coords to canvas coords ==========
    _screenToCanvas(sx, sy) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: (sx - rect.left - this.pan.x) / this.zoom,
            y: (sy - rect.top - this.pan.y) / this.zoom
        };
    }

    // ========== Add Start Node ==========
    addStartNode() {
        const id = 'node_start';
        this.nodes[id] = {
            id, type: 'start', x: 80, y: 100,
            data: { keywords: ['Hi', 'Hello', 'Start'], matchType: 'contains' },
            stats: { sent: 0, delivered: 0, subscribers: 0, errors: 0 }
        };
        this._renderNode(id);
    }

    // ========== Add Node ==========
    addNode(type, x, y) {
        this.nodeCounter++;
        const id = 'node_' + this.nodeCounter + '_' + Date.now();
        const defaults = {
            text:      { message: '', delay: 0, delayUnit: 'Sec' },
            image:     { url: '', caption: '', delay: 0, delayUnit: 'Sec' },
            audio:     { url: '', delay: 0, delayUnit: 'Sec' },
            video:     { url: '', caption: '', delay: 0, delayUnit: 'Sec' },
            file:      { url: '', filename: '', delay: 0, delayUnit: 'Sec' },
            button:    { message: '', buttons: ['Visit store'] },
            condition: { variable: '', operator: 'equals', value: '' },
            delay:     { duration: 5, unit: 'Sec' },
            interactive: { message: 'Visit Our Site', description: 'if you are interested to visit our site', delay: 0, buttons: ['Buttons', 'List Messages', 'E-commerce'] }
        };
        this.nodes[id] = {
            id, type, x: Math.round(x), y: Math.round(y),
            data: { ...(defaults[type] || {}) },
            stats: { sent: 0, delivered: 0, subscribers: 0, errors: 0 }
        };
        this._renderNode(id);
        this._selectNode(id);
        return id;
    }

    // ========== Render Node ==========
    _renderNode(id) {
        const node = this.nodes[id];
        if (!node) return;
        const el = document.createElement('div');
        el.className = `flow-node ${node.type}-node new-node`;
        el.dataset.id = id;
        el.style.left = node.x + 'px';
        el.style.top = node.y + 'px';
        el.innerHTML = this._nodeHTML(node);
        this.canvasInner.appendChild(el);

        setTimeout(() => el.classList.remove('new-node'), 350);

        this._attachNodeEvents(el, id);
    }

    _refreshNode(id) {
        const el = this.canvasInner.querySelector(`[data-id="${id}"]`);
        if (!el) return;
        el.innerHTML = this._nodeHTML(this.nodes[id]);
        this._attachNodeEvents(el, id);
        this._drawConnections();
    }

    _attachNodeEvents(el, id) {
        const self = this;

        // --- Node Drag (from header) ---
        const header = el.querySelector('.node-header');
        if (header) {
            header.addEventListener('mousedown', (e) => {
                if (e.target.closest('.node-delete')) return;
                e.stopPropagation();
                e.preventDefault();
                self.draggingNode = id;
                self.dragStartX = e.clientX;
                self.dragStartY = e.clientY;
                self.dragNodeStartX = self.nodes[id].x;
                self.dragNodeStartY = self.nodes[id].y;
                el.classList.add('dragging');
                self._selectNode(id);
            });
        }

        // --- Select node ---
        el.addEventListener('mousedown', (e) => {
            if (!e.target.closest('.port') && !e.target.closest('.node-delete') &&
                !e.target.closest('input') && !e.target.closest('textarea') &&
                !e.target.closest('select') && !e.target.closest('button')) {
                self._selectNode(id);
            }
        });

        // --- Output ports (start connection) ---
        el.querySelectorAll('.port-out').forEach(port => {
            port.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                e.preventDefault();
                self.isConnecting = true;
                self.connectFromNode = id;
                self.connectFromPort = port.dataset.port;
            });
        });

        // --- Input ports (finish connection) ---
        el.querySelectorAll('.port-in').forEach(port => {
            port.addEventListener('mouseup', (e) => {
                e.stopPropagation();
                if (self.isConnecting && self.connectFromNode !== id) {
                    // Remove existing connection from THIS SPECIFIC output port
                    self.connections = self.connections.filter(c =>
                        !(c.fromNode === self.connectFromNode && c.fromPort === self.connectFromPort)
                    );
                    self.connections.push({
                        fromNode: self.connectFromNode,
                        fromPort: self.connectFromPort,
                        toNode: id,
                        toPort: 'in'
                    });
                    self.isConnecting = false;
                    self.connectFromNode = null;
                    self._removeTempLine();
                    self._drawConnections();
                }
            });
        });

        // --- Prevent canvas pan on inputs ---
        el.querySelectorAll('input, textarea, select').forEach(inp => {
            inp.addEventListener('mousedown', e => e.stopPropagation());
            inp.addEventListener('change', () => self._syncNodeData(id));
            inp.addEventListener('input', () => self._syncNodeData(id));
        });
    }

    // ========== Node Drag ==========
    _onDragMove(e) {
        const id = this.draggingNode;
        const node = this.nodes[id];
        if (!node) return;

        const dx = (e.clientX - this.dragStartX) / this.zoom;
        const dy = (e.clientY - this.dragStartY) / this.zoom;
        node.x = Math.max(0, Math.round(this.dragNodeStartX + dx));
        node.y = Math.max(0, Math.round(this.dragNodeStartY + dy));

        const el = this.canvasInner.querySelector(`[data-id="${id}"]`);
        if (el) {
            el.style.left = node.x + 'px';
            el.style.top = node.y + 'px';
        }
        this._drawConnections();
    }

    // ========== Selection ==========
    _selectNode(id) {
        this._deselectAll();
        const el = this.canvasInner.querySelector(`[data-id="${id}"]`);
        if (el) el.classList.add('selected');
        this.selectedNode = id;
    }

    _deselectAll() {
        this.canvasInner.querySelectorAll('.flow-node.selected').forEach(n => n.classList.remove('selected'));
        this.selectedNode = null;
    }

    // ========== Delete Node ==========
    deleteNode(id) {
        if (id === 'node_start') return;
        this.connections = this.connections.filter(c => c.fromNode !== id && c.toNode !== id);
        const el = this.canvasInner.querySelector(`[data-id="${id}"]`);
        if (el) el.remove();
        delete this.nodes[id];
        if (this.selectedNode === id) this.selectedNode = null;
        this._drawConnections();
    }

    // ========== Get Port Position (relative to canvasInner) ==========
    _getPortPos(nodeId, portType, portName) {
        const node = this.nodes[nodeId];
        if (!node) return null;
        const el = this.canvasInner.querySelector(`[data-id="${nodeId}"]`);
        if (!el) return null;

        if (portType === 'out') {
            const port = el.querySelector(`.port-out[data-port="${portName}"]`);
            if (!port) return null;
            // Port is anchored to right edge of node
            const nodeRect = el.getBoundingClientRect();
            const portRect = port.getBoundingClientRect();
            const cInnerRect = this.canvasInner.getBoundingClientRect();
            return {
                x: (portRect.left + portRect.width / 2 - cInnerRect.left) / this.zoom,
                y: (portRect.top + portRect.height / 2 - cInnerRect.top) / this.zoom
            };
        } else {
            const port = el.querySelector('.port-in');
            if (!port) return null;
            const portRect = port.getBoundingClientRect();
            const cInnerRect = this.canvasInner.getBoundingClientRect();
            return {
                x: (portRect.left + portRect.width / 2 - cInnerRect.left) / this.zoom,
                y: (portRect.top + portRect.height / 2 - cInnerRect.top) / this.zoom
            };
        }
    }

    // ========== Draw All Connections ==========
    _drawConnections() {
        // Clear all existing paths
        this.svg.querySelectorAll('.conn-path').forEach(p => p.remove());

        this.connections.forEach((conn, idx) => {
            const from = this._getPortPos(conn.fromNode, 'out', conn.fromPort);
            const to = this._getPortPos(conn.toNode, 'in', conn.toPort);
            if (!from || !to) return;

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', this._bezier(from.x, from.y, to.x, to.y));
            path.setAttribute('class', 'conn-path');
            path.setAttribute('data-idx', idx);
            path.style.pointerEvents = 'stroke';
            path.style.cursor = 'pointer';
            path.addEventListener('dblclick', (e) => {
                e.stopPropagation();
                this.connections.splice(idx, 1);
                this._drawConnections();
            });
            this.svg.appendChild(path);
        });

        // Update port visuals
        this.canvasInner.querySelectorAll('.port').forEach(p => p.classList.remove('connected'));
        this.connections.forEach(conn => {
            const fromEl = this.canvasInner.querySelector(`[data-id="${conn.fromNode}"] .port-out[data-port="${conn.fromPort}"]`);
            const toEl = this.canvasInner.querySelector(`[data-id="${conn.toNode}"] .port-in`);
            if (fromEl) fromEl.classList.add('connected');
            if (toEl) toEl.classList.add('connected');
        });
    }

    _bezier(x1, y1, x2, y2) {
        const dx = Math.abs(x2 - x1);
        const cp = Math.max(80, dx * 0.5);
        return `M${x1},${y1} C${x1 + cp},${y1} ${x2 - cp},${y2} ${x2},${y2}`;
    }

    // ========== Temp Connection Line ==========
    _drawTempLine(e) {
        this._removeTempLine();
        const fromPos = this._getPortPos(this.connectFromNode, 'out', this.connectFromPort);
        if (!fromPos) return;

        const cInnerRect = this.canvasInner.getBoundingClientRect();
        const toX = (e.clientX - cInnerRect.left) / this.zoom;
        const toY = (e.clientY - cInnerRect.top) / this.zoom;

        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', this._bezier(fromPos.x, fromPos.y, toX, toY));
        path.setAttribute('class', 'conn-path temp-line');
        this.svg.appendChild(path);
        this.tempLine = path;
    }

    _removeTempLine() {
        if (this.tempLine) { this.tempLine.remove(); this.tempLine = null; }
        this.svg.querySelectorAll('.temp-line').forEach(p => p.remove());
    }

    _finishConnect(e) {
        this.isConnecting = false;
        this.connectFromNode = null;
        this.connectFromPort = null;
        this._removeTempLine();
    }

    // ========== Sync Node Data from DOM ==========
    _syncNodeData(id) {
        const el = this.canvasInner.querySelector(`[data-id="${id}"]`);
        const node = this.nodes[id];
        if (!el || !node) return;
        el.querySelectorAll('[data-field]').forEach(inp => {
            node.data[inp.dataset.field] = inp.value;
        });
        el.querySelectorAll('[data-btn-idx]').forEach(inp => {
            const i = parseInt(inp.dataset.btnIdx);
            if (node.data.buttons && node.data.buttons[i] !== undefined) {
                node.data.buttons[i] = inp.value;
            }
        });
    }

    // ========== Keyword helpers ==========
    addKeyword(nodeId, inputEl) {
        const kw = inputEl.value.trim();
        if (!kw) return;
        if (!this.nodes[nodeId].data.keywords) this.nodes[nodeId].data.keywords = [];
        this.nodes[nodeId].data.keywords.push(kw);
        inputEl.value = '';
        this._refreshNode(nodeId);
    }

    removeKeyword(nodeId, idx) {
        this.nodes[nodeId].data.keywords.splice(idx, 1);
        this._refreshNode(nodeId);
    }

    // ========== Button helpers ==========
    addButton(nodeId) {
        if (!this.nodes[nodeId].data.buttons) this.nodes[nodeId].data.buttons = [];
        if (this.nodes[nodeId].data.buttons.length >= 3) {
            this._toast('Maximum 3 buttons allowed', 'error');
            return;
        }
        this.nodes[nodeId].data.buttons.push('Button ' + (this.nodes[nodeId].data.buttons.length + 1));
        this._refreshNode(nodeId);
    }

    removeButton(nodeId, idx) {
        this.nodes[nodeId].data.buttons.splice(idx, 1);
        this._refreshNode(nodeId);
    }

    // ========== Node HTML ==========
    _nodeHTML(node) {
        const labels = {
            start: 'Start Bot Flow', 
            text: 'Text', 
            image: 'Image',
            audio: 'Audio', 
            video: 'Video', 
            file: 'File',
            button: 'Button', 
            condition: 'Condition', 
            delay: 'Delay',
            interactive: 'Interactive'
        };
        const icons = {
            start: 'bi-lightning-charge-fill', 
            text: 'bi-chat-left-dots-fill', 
            image: 'bi-image-fill',
            audio: 'bi-volume-up-fill', 
            video: 'bi-camera-video-fill', 
            file: 'bi-file-earmark-fill',
            button: 'bi-hand-index-thumb-fill', 
            condition: 'bi-signpost-split-fill', 
            delay: 'bi-clock-fill',
            interactive: 'bi-chat-quote-fill'
        };
        let h = '';

        // Input port (not on start)
        if (node.type !== 'start') {
            h += `<div class="port port-in" data-port="in" data-node="${node.id}"></div>`;
        }

        // Header
        h += `<div class="node-header">
            <div class="node-icon"><i class="bi ${icons[node.type]}"></i></div>
            <span>${labels[node.type]}</span>
            ${node.type !== 'start' ? `<div class="node-delete" onclick="event.stopPropagation();builder.deleteNode('${node.id}')"><i class="bi bi-x-lg"></i></div>` : ''}
        </div>`;

        // Stats
        h += `<div class="node-stats">
            <div class="stat-item">
                <i class="bi bi-send-fill" style="color:#64748b"></i>
                <div class="stat-num">${node.stats.sent}</div>
                <div class="stat-lbl">${node.type === 'button' ? 'Click' : 'Sent'}</div>
            </div>
            <div class="stat-item">
                <i class="bi bi-person-check-fill" style="color:#3b82f6"></i>
                <div class="stat-num">${node.stats.delivered}</div>
                <div class="stat-lbl">${node.type === 'button' ? 'Subscribers' : 'Delivered'}</div>
            </div>
            <div class="stat-item">
                <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i>
                <div class="stat-num">${node.stats.subscribers}</div>
                <div class="stat-lbl">${node.type === 'button' ? 'Errors' : 'Subscribers'}</div>
            </div>
        </div>`;

        // Body
        h += `<div class="node-body">${this._bodyHTML(node)}</div>`;

        // Footer (output ports)
        h += `<div class="node-footer">`;
        if (node.type === 'condition') {
            h += `<div class="node-port-row"><span class="port-label">✅ True</span><div class="port port-out" data-port="true" data-node="${node.id}"></div></div>`;
            h += `<div class="node-port-row"><span class="port-label">❌ False</span><div class="port port-out" data-port="false" data-node="${node.id}"></div></div>`;
        } else if (node.type === 'button' || node.type === 'interactive') {
            h += `<div class="node-port-row"><span class="port-label">Next</span><div class="port port-out" data-port="out" data-node="${node.id}"></div></div>`;
        } else {
            h += `<div class="node-port-row"><span class="port-label">Compose Next Message</span><div class="port port-out" data-port="out" data-node="${node.id}"></div></div>`;
        }

        if (node.type === 'interactive') {
             (node.data.buttons || []).forEach((b, i) => {
                h += `<div class="node-port-row"><span class="port-label">${this._esc(b)}</span><div class="port port-out" data-port="btn_${i}" data-node="${node.id}"></div></div>`;
             });
        }

        if (node.type === 'button') {
            h += `<div class="node-port-row text-muted-row"><span class="port-label">Next</span><div class="port port-out" data-port="next" data-node="${node.id}"></div></div>`;
            h += `<div class="node-port-row text-muted-row"><span class="port-label">Subscribe to Sequence</span><div class="port port-out" data-port="seq" data-node="${node.id}"></div></div>`;
        }
        h += `</div>`;
        return h;
    }

    _bodyHTML(node) {
        const id = node.id;
        switch (node.type) {
            case 'start': {
                let s = '<div class="node-field"><div class="field-label">Trigger Keywords</div><div class="keywords-wrap">';
                (node.data.keywords || []).forEach((kw, i) => {
                    s += `<span class="keyword-tag">${this._esc(kw)}<span class="remove-kw" onclick="event.stopPropagation();builder.removeKeyword('${id}',${i})">×</span></span>`;
                });
                s += `</div><div class="kw-input-row"><input type="text" class="kw-input" placeholder="Add keyword..." onkeydown="if(event.key==='Enter'){event.preventDefault();builder.addKeyword('${id}',this);}"><button type="button" class="kw-add-btn" onclick="builder.addKeyword('${id}',this.previousElementSibling)">+</button></div></div>`;
                s += `<div class="node-field"><div class="field-label">Match Type</div>
                    <select data-field="matchType" onmousedown="event.stopPropagation()">
                        <option value="contains" ${node.data.matchType==='contains'?'selected':''}>Contains</option>
                        <option value="exact" ${node.data.matchType==='exact'?'selected':''}>Exact Match</option>
                        <option value="starts_with" ${node.data.matchType==='starts_with'?'selected':''}>Starts With</option>
                    </select></div>`;
                return s;
            }
            case 'text':
                return `${this._delayField(node)}
                    <div class="node-field"><div class="field-label">Message</div>
                    <textarea data-field="message" rows="3" placeholder="Type your message..." onmousedown="event.stopPropagation()">${this._esc(node.data.message||'')}</textarea></div>`;

            case 'image':
                return `${this._delayField(node)}
                    <div class="node-field">
                        <div class="media-preview">${node.data.url ? `<img src="${this._esc(node.data.url)}" alt="Preview" onerror="this.style.display='none'">` : '<i class="bi bi-image" style="font-size:2rem"></i>'}</div>
                        <div class="field-label">Resource URL</div>
                        <input type="text" data-field="url" value="${this._esc(node.data.url||'')}" placeholder="https://example.com/image.jpg">
                    </div>
                    <div class="node-field"><div class="field-label">Caption</div>
                    <input type="text" data-field="caption" value="${this._esc(node.data.caption||'')}" placeholder="Optional caption"></div>`;

            case 'audio':
                return `${this._delayField(node)}
                    <div class="node-field">
                        <div class="media-preview"><i class="bi bi-volume-up-fill" style="font-size:2rem;color:var(--audio-node)"></i></div>
                        <div class="field-label">Resource URL</div>
                        <input type="text" data-field="url" value="${this._esc(node.data.url||'')}" placeholder="https://example.com/audio.mp3">
                    </div>`;

            case 'video':
                return `${this._delayField(node)}
                    <div class="node-field">
                        <div class="media-preview">${node.data.url ? `<video controls style="width:100%;height:100%;object-fit:cover"><source src="${this._esc(node.data.url)}"></video>` : '<i class="bi bi-camera-video-fill" style="font-size:2rem;color:var(--video-node)"></i>'}</div>
                        <div class="field-label">Resource URL</div>
                        <input type="text" data-field="url" value="${this._esc(node.data.url||'')}" placeholder="https://example.com/video.mp4">
                    </div>
                    <div class="node-field"><div class="field-label">Caption</div>
                    <input type="text" data-field="caption" value="${this._esc(node.data.caption||'')}" placeholder="Optional caption"></div>`;

            case 'file':
                return `${this._delayField(node)}
                    <div class="node-field">
                        <div class="media-preview"><div style="text-align:center"><i class="bi bi-file-earmark-fill" style="font-size:2.5rem;color:var(--file-node)"></i><div style="font-size:.75rem;margin-top:4px">${this._esc(node.data.filename||'document.pdf')}</div></div></div>
                        <div class="field-label">Resource URL</div>
                        <input type="text" data-field="url" value="${this._esc(node.data.url||'')}" placeholder="https://example.com/file.pdf">
                    </div>
                    <div class="node-field"><div class="field-label">Filename</div>
                    <input type="text" data-field="filename" value="${this._esc(node.data.filename||'')}" placeholder="document.pdf"></div>`;

            case 'interactive':
                return `${this._delayField(node)}
                    <div class="node-field">
                        <div class="field-label" style="color:#2563eb;font-weight:700">Visit Our Site</div>
                        <div style="font-size:0.75rem;color:#64748b;margin-bottom:8px">if you are interested to visit our site</div>
                    </div>`;

            case 'button':
                return `<div class="node-field text-center py-2">
                    <i class="bi bi-hand-index-thumb" style="font-size:2rem;color:#3b82f6;opacity:0.6"></i>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">${this._esc(node.data.buttons[0] || 'Button')}</div>
                </div>`;

            case 'condition':
                return `<div class="node-field"><div class="field-label">Variable</div>
                    <input type="text" data-field="variable" value="${this._esc(node.data.variable||'')}" placeholder="e.g. user_message"></div>
                    <div class="node-field"><div class="field-label">Operator</div>
                    <select data-field="operator" onmousedown="event.stopPropagation()">
                        <option value="equals" ${node.data.operator==='equals'?'selected':''}>Equals</option>
                        <option value="contains" ${node.data.operator==='contains'?'selected':''}>Contains</option>
                        <option value="starts_with" ${node.data.operator==='starts_with'?'selected':''}>Starts With</option>
                    </select></div>
                    <div class="node-field"><div class="field-label">Value</div>
                    <input type="text" data-field="value" value="${this._esc(node.data.value||'')}" placeholder="Expected value"></div>`;

            case 'delay':
                return `<div class="node-field"><div class="field-label">Wait Duration</div>
                    <div class="delay-row">
                        <input type="number" data-field="duration" value="${node.data.duration||5}" min="1">
                        <select data-field="unit" onmousedown="event.stopPropagation()">
                            <option value="Sec" ${node.data.unit==='Sec'?'selected':''}>Seconds</option>
                            <option value="Min" ${node.data.unit==='Min'?'selected':''}>Minutes</option>
                            <option value="Hour" ${node.data.unit==='Hour'?'selected':''}>Hours</option>
                        </select>
                    </div></div>`;

            default: return '';
        }
    }

    _delayField(node) {
        return `<div class="node-field"><div class="delay-row">
            <span class="field-label" style="margin:0;white-space:nowrap">Delay</span>
            <input type="number" data-field="delay" value="${node.data.delay||0}" min="0" style="width:55px">
            <select data-field="delayUnit" onmousedown="event.stopPropagation()" style="width:70px">
                <option value="Sec" ${node.data.delayUnit==='Sec'?'selected':''}>Sec</option>
                <option value="Min" ${node.data.delayUnit==='Min'?'selected':''}>Min</option>
            </select>
        </div></div>`;
    }

    _esc(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ========== Save ==========
    saveFlow() {
        // Sync all data from DOM
        this.canvasInner.querySelectorAll('.flow-node').forEach(el => {
            this._syncNodeData(el.dataset.id);
        });

        const flowData = {
            nodes: this.nodes,
            connections: this.connections,
            meta: { name: document.getElementById('flowName').value, zoom: this.zoom, pan: this.pan, savedAt: new Date().toISOString() }
        };

        const fd = new FormData();
        fd.append('action', 'save_flow');
        fd.append('flow_id', document.getElementById('flowId').value || '0');
        fd.append('flow_name', document.getElementById('flowName').value);
        fd.append('flow_data', JSON.stringify(flowData));
        fd.append('csrf_token', document.getElementById('csrfToken').value);

        fetch('chatbot.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this._toast('✅ Flow saved successfully!');
                    if (data.flow_id) document.getElementById('flowId').value = data.flow_id;
                } else {
                    this._toast(data.message || 'Save failed', 'error');
                }
            })
            .catch(() => this._toast('Network error while saving', 'error'));
    }

    // ========== Load ==========
    loadFlow(flowData) {
        try {
            const d = typeof flowData === 'string' ? JSON.parse(flowData) : flowData;
            this.nodes = d.nodes || {};
            this.connections = d.connections || [];
            this.zoom = d.meta?.zoom || 1;
            this.pan = d.meta?.pan || { x: 0, y: 0 };

            // Clear
            this.canvasInner.querySelectorAll('.flow-node').forEach(n => n.remove());
            this.svg.querySelectorAll('.conn-path').forEach(p => p.remove());

            // Find highest nodeCounter
            Object.keys(this.nodes).forEach(id => {
                const m = id.match(/^node_(\d+)_/);
                if (m) this.nodeCounter = Math.max(this.nodeCounter, parseInt(m[1]));
            });

            // Render
            Object.keys(this.nodes).forEach(id => this._renderNode(id));
            this._applyTransform();
            this._updateZoomLabel();
            setTimeout(() => this._drawConnections(), 150);
        } catch (e) {
            console.error('Flow load error:', e);
        }
    }

    // ========== Export ==========
    exportFlow() {
        this.canvasInner.querySelectorAll('.flow-node').forEach(el => this._syncNodeData(el.dataset.id));
        const json = JSON.stringify({ nodes: this.nodes, connections: this.connections, meta: { name: document.getElementById('flowName').value } }, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = (document.getElementById('flowName').value || 'flow') + '.json';
        a.click();
        URL.revokeObjectURL(a.href);
    }

    // ========== Toast ==========
    _toast(msg, type = 'success') {
        let t = document.getElementById('builderToast');
        if (!t) {
            t = document.createElement('div');
            t.id = 'builderToast';
            t.className = 'builder-toast';
            document.body.appendChild(t);
        }
        t.textContent = msg;
        t.className = 'builder-toast' + (type === 'error' ? ' error' : '');
        requestAnimationFrame(() => t.classList.add('show'));
        clearTimeout(t._tid);
        t._tid = setTimeout(() => t.classList.remove('show'), 3000);
    }
}

// Global
let builder;
