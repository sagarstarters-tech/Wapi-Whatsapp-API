/**
 * WAPI SaaS - Chatbot Builder JS Engine
 * Handles Visual Flow Logic, Connections, and UI interactions
 */

class FlowBuilder {
    constructor(canvasId, containerId) {
        this.canvas = document.getElementById(canvasId);
        this.container = document.getElementById(containerId);
        this.svg = document.getElementById('svgContainer');
        this.nodes = [];
        this.connections = [];
        this.zoom = 1.0;
        this.pan = { x: 0, y: 0 };
        this.selectedNode = null;
        this.isDraggingCanvas = false;
        this.isDraggingPort = false;
        this.tempLine = null;
        this.startPort = null;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupInteractJS();
        this.loadFlowData();
    }

    setupEventListeners() {
        // Canvas Dragging (Pan)
        this.container.addEventListener('mousedown', (e) => {
            if (e.target === this.container || e.target === this.canvas) {
                this.isDraggingCanvas = true;
                this.container.style.cursor = 'grabbing';
            }
        });

        window.addEventListener('mousemove', (e) => {
            if (this.isDraggingCanvas) {
                this.pan.x += e.movementX;
                this.pan.y += e.movementY;
                this.updateCanvasTransform();
            }
            if (this.isDraggingPort && this.tempLine) {
                this.updateTempLine(e);
            }
        });

        window.addEventListener('mouseup', () => {
            this.isDraggingCanvas = false;
            this.container.style.cursor = 'grab';
            
            if (this.isDraggingPort) {
                this.isDraggingPort = false;
                if (this.tempLine) {
                    this.tempLine.remove();
                    this.tempLine = null;
                }
            }
        });

        // Zoom Controls
        document.getElementById('btnZoomIn').addEventListener('click', () => this.setZoom(this.zoom + 0.1));
        document.getElementById('btnZoomOut').addEventListener('click', () => this.setZoom(this.zoom - 0.1));

        // Node Selection
        this.canvas.addEventListener('click', (e) => {
            const nodeEl = e.target.closest('.flow-node');
            if (nodeEl) {
                this.selectNode(nodeEl.dataset.uuid);
            } else if (e.target === this.container || e.target === this.canvas) {
                this.deselectAll();
            }
        });

        // Save Button
        document.getElementById('btnSaveDraft').addEventListener('click', () => this.saveFlow(false));
        document.getElementById('btnPublish').addEventListener('click', () => this.saveFlow(true));

        // Drag node from library
        const libraryItems = document.querySelectorAll('.node-library-item');
        libraryItems.forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', item.dataset.type);
            });
        });

        this.container.addEventListener('dragover', (e) => e.preventDefault());
        this.container.addEventListener('drop', (e) => {
            e.preventDefault();
            const type = e.dataTransfer.getData('nodeType');
            const rect = this.canvas.getBoundingClientRect();
            const x = (e.clientX - rect.left) / this.zoom;
            const y = (e.clientY - rect.top) / this.zoom;
            this.addNode(type, x, y);
        });

        // Delete Node
        document.getElementById('btnDeleteNode').addEventListener('click', () => {
            if (this.selectedNode) {
                this.deleteNode(this.selectedNode.node_uuid);
            }
        });
    }

    setupInteractJS() {
        interact('.flow-node').draggable({
            listeners: {
                move: (event) => {
                    const target = event.target;
                    const uuid = target.dataset.uuid;
                    const node = this.nodes.find(n => n.node_uuid === uuid);
                    
                    if (node) {
                        node.x += event.dx / this.zoom;
                        node.y += event.dy / this.zoom;
                        target.style.transform = `translate(${node.x}px, ${node.y}px)`;
                        this.updateConnectionsForNode(uuid);
                    }
                }
            }
        });
    }

    updateCanvasTransform() {
        this.canvas.style.transform = `translate(${this.pan.x}px, ${this.pan.y}px) scale(${this.zoom})`;
        document.getElementById('zoomLevel').innerText = `${Math.round(this.zoom * 100)}%`;
    }

    setZoom(value) {
        this.zoom = Math.max(0.1, Math.min(2.0, value));
        this.updateCanvasTransform();
    }

    addNode(type, x, y, data = {}) {
        const uuid = 'node_' + Math.random().toString(36).substr(2, 9);
        const node = {
            node_uuid: uuid,
            type: type,
            x: x,
            y: y,
            data: data || this.getDefaultData(type)
        };

        this.nodes.push(node);
        this.renderNode(node);
        this.selectNode(uuid);
        return node;
    }

    getDefaultData(type) {
        const defaults = {
            text: { message: 'Hello! How can we help you?' },
            image: { url: '', caption: '' },
            buttons: { message: 'Please select an option:', buttons: ['Option 1', 'Option 2'] },
            list: { title: 'Select one', items: ['Item 1', 'Item 2'] },
            condition: { variable: 'last_message', operator: 'contains', value: '' },
            delay: { seconds: 5 },
            api: { url: '', method: 'POST', headers: {} }
        };
        return defaults[type] || {};
    }

    renderNode(node) {
        const nodeEl = document.createElement('div');
        nodeEl.className = 'flow-node fade-in';
        nodeEl.dataset.uuid = node.node_uuid;
        nodeEl.style.transform = `translate(${node.x}px, ${node.y}px)`;
        
        const iconClass = this.getIconForType(node.type);
        const headerTitle = this.getTitleForType(node.type);

        nodeEl.innerHTML = `
            <div class="node-header">
                <div class="node-icon small" style="background: ${this.getColorForType(node.type)}">
                    <i class="bi ${iconClass}"></i>
                </div>
                <span>${headerTitle}</span>
            </div>
            <div class="node-body">
                <div class="node-content-preview" id="preview_${node.node_uuid}">
                    ${this.getPreviewText(node)}
                </div>
            </div>
            <div class="node-port port-in" data-port-type="in"></div>
            <div class="node-port port-out" data-port-type="out">
                <span class="port-label">Next Step</span>
            </div>
        `;

        this.canvas.appendChild(nodeEl);
        this.setupPortListeners(nodeEl);
    }

    getIconForType(type) {
        const icons = { text: 'bi-chat-left-text', image: 'bi-image', buttons: 'bi-menu-button-wide', list: 'bi-list-ul', condition: 'bi-diagram-3', delay: 'bi-hourglass-split', api: 'bi-box-arrow-up-right' };
        return icons[type] || 'bi-gear';
    }

    getColorForType(type) {
        const colors = { text: '#25D366', image: '#3b82f6', buttons: '#f59e0b', list: '#8b5cf6', condition: '#ef4444', delay: '#64748b', api: '#ec4899' };
        return colors[type] || '#25D366';
    }

    getTitleForType(type) {
        return type.charAt(0).toUpperCase() + type.slice(1);
    }

    getPreviewText(node) {
        if (node.type === 'text') return node.data.message || 'No message set';
        if (node.type === 'image') return node.data.caption || 'Image content';
        if (node.type === 'buttons') return node.data.message || 'Button menu';
        return 'Configuration...';
    }

    setupPortListeners(nodeEl) {
        const ports = nodeEl.querySelectorAll('.node-port');
        ports.forEach(port => {
            port.addEventListener('mousedown', (e) => {
                e.stopPropagation();
                if (port.dataset.portType === 'out') {
                    this.isDraggingPort = true;
                    this.startPort = {
                        uuid: nodeEl.dataset.uuid,
                        type: 'out',
                        el: port
                    };
                    this.createTempLine(e);
                }
            });

            port.addEventListener('mouseup', (e) => {
                e.stopPropagation();
                if (this.isDraggingPort && port.dataset.portType === 'in') {
                    const fromUuid = this.startPort.uuid;
                    const toUuid = nodeEl.dataset.uuid;
                    if (fromUuid !== toUuid) {
                        this.addConnection(fromUuid, 'out', toUuid);
                    }
                }
            });
        });
    }

    createTempLine(e) {
        const rect = this.startPort.el.getBoundingClientRect();
        const canvasRect = this.canvas.getBoundingClientRect();
        
        const x1 = (rect.left + rect.width / 2 - canvasRect.left) / this.zoom;
        const y1 = (rect.top + rect.height / 2 - canvasRect.top) / this.zoom;

        this.tempLine = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        this.tempLine.setAttribute('class', 'flow-connection');
        this.tempLine.setAttribute('d', `M ${x1} ${y1} L ${x1} ${y1}`);
        this.svg.appendChild(this.tempLine);
    }

    updateTempLine(e) {
        const canvasRect = this.canvas.getBoundingClientRect();
        const rect = this.startPort.el.getBoundingClientRect();
        
        const x1 = (rect.left + rect.width / 2 - canvasRect.left) / this.zoom;
        const y1 = (rect.top + rect.height / 2 - canvasRect.top) / this.zoom;
        const x2 = (e.clientX - canvasRect.left) / this.zoom;
        const y2 = (e.clientY - canvasRect.top) / this.zoom;

        const d = this.calculateBezier(x1, y1, x2, y2);
        this.tempLine.setAttribute('d', d);
    }

    calculateBezier(x1, y1, x2, y2) {
        const dx = Math.abs(x2 - x1) * 0.5;
        return `M ${x1} ${y1} C ${x1 + dx} ${y1}, ${x2 - dx} ${y2}, ${x2} ${y2}`;
    }

    addConnection(fromNode, fromPort, toNode) {
        // Remove existing connections from this port (one output per port)
        this.connections = this.connections.filter(c => !(c.fromNode === fromNode && c.fromPort === fromPort));
        
        const conn = { fromNode, fromPort, toNode };
        this.connections.push(conn);
        this.renderConnections();
    }

    renderConnections() {
        // Clear all except background if needed
        while (this.svg.firstChild) this.svg.removeChild(this.svg.firstChild);

        this.connections.forEach(conn => {
            const fromEl = document.querySelector(`.flow-node[data-uuid="${conn.fromNode}"] .port-out`);
            const toEl = document.querySelector(`.flow-node[data-uuid="${conn.toNode}"] .port-in`);
            
            if (fromEl && toEl) {
                const canvasRect = this.canvas.getBoundingClientRect();
                const fromRect = fromEl.getBoundingClientRect();
                const toRect = toEl.getBoundingClientRect();

                const x1 = (fromRect.left + fromRect.width / 2 - canvasRect.left) / this.zoom;
                const y1 = (fromRect.top + fromRect.height / 2 - canvasRect.top) / this.zoom;
                const x2 = (toRect.left + toRect.width / 2 - canvasRect.left) / this.zoom;
                const y2 = (toRect.top + toRect.height / 2 - canvasRect.top) / this.zoom;

                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('class', 'flow-connection');
                path.setAttribute('d', this.calculateBezier(x1, y1, x2, y2));
                
                path.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.connections = this.connections.filter(c => c !== conn);
                    this.renderConnections();
                });

                this.svg.appendChild(path);
            }
        });
    }

    updateConnectionsForNode(uuid) {
        this.renderConnections();
    }

    selectNode(uuid) {
        this.deselectAll();
        const nodeEl = document.querySelector(`.flow-node[data-uuid="${uuid}"]`);
        if (nodeEl) {
            nodeEl.classList.add('active');
            this.selectedNode = this.nodes.find(n => n.node_uuid === uuid);
            this.showProperties(this.selectedNode);
        }
    }

    deselectAll() {
        document.querySelectorAll('.flow-node').forEach(n => n.classList.remove('active'));
        this.selectedNode = null;
        document.getElementById('propContent').classList.add('d-none');
        document.getElementById('propEmptyState').classList.remove('d-none');
    }

    showProperties(node) {
        document.getElementById('propEmptyState').classList.add('d-none');
        document.getElementById('propContent').classList.remove('d-none');
        
        document.getElementById('propTypeTitle').innerText = this.getTitleForType(node.type);
        document.getElementById('propIcon').innerHTML = `<i class="bi ${this.getIconForType(node.type)}"></i>`;
        document.getElementById('propIcon').style.background = this.getColorForType(node.type);

        const fields = document.getElementById('dynamicFields');
        fields.innerHTML = '';

        if (node.type === 'text') {
            fields.innerHTML = `
                <div class="property-group">
                    <label class="property-label">Message Text</label>
                    <textarea class="form-control" rows="4" id="prop_message">${node.data.message || ''}</textarea>
                    <div class="form-text">You can use variables like {{name}}</div>
                </div>
            `;
        } else if (node.type === 'image') {
            fields.innerHTML = `
                <div class="property-group">
                    <label class="property-label">Image URL</label>
                    <input type="text" class="form-control" id="prop_url" value="${node.data.url || ''}">
                </div>
                <div class="property-group">
                    <label class="property-label">Caption</label>
                    <input type="text" class="form-control" id="prop_caption" value="${node.data.caption || ''}">
                </div>
            `;
        }

        // Add event listeners to update node data real-time
        const inputs = fields.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                const key = input.id.replace('prop_', '');
                node.data[key] = input.value;
                document.getElementById(`preview_${node.node_uuid}`).innerText = this.getPreviewText(node);
            });
        });
    }

    deleteNode(uuid) {
        this.nodes = this.nodes.filter(n => n.node_uuid !== uuid);
        this.connections = this.connections.filter(c => c.fromNode !== uuid && c.toNode !== uuid);
        const el = document.querySelector(`.flow-node[data-uuid="${uuid}"]`);
        if (el) el.remove();
        this.renderConnections();
        this.deselectAll();
    }

    async saveFlow(isPublish) {
        const payload = {
            id: INITIAL_FLOW_DATA.id,
            nodes: this.nodes,
            connections: this.connections,
            is_published: isPublish ? 1 : 0
        };

        try {
            const res = await fetch(BASE_URL + '/api/chatbot/save-flow.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('lastSavedTime').innerText = new Date().toLocaleTimeString();
                Swal.fire({ icon: 'success', title: 'Saved!', text: isPublish ? 'Flow published successfully.' : 'Draft saved.', timer: 2000 });
            }
        } catch (e) {
            console.error('Save failed', e);
        }
    }

    loadFlowData() {
        if (INITIAL_FLOW_DATA && INITIAL_FLOW_DATA.nodes) {
            // Logic to load existing nodes and connections
            // (Assuming they are stored in JSON or retrieved via API)
        } else {
            // Add initial Start Node
            this.addNode('text', 100, 100, { message: 'Welcome to our bot!' });
        }
    }
}

// Global initialization
document.addEventListener('DOMContentLoaded', () => {
    window.builder = new FlowBuilder('builderCanvas', 'canvasContainer');
});
