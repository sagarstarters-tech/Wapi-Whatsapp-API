<?php
/**
 * WAPI SaaS - Live Chat Page
 * Shows incoming and outgoing messages in real-time
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$hideNav = true;

// Handle Clear Chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_chat') {
    if (!CSRF::validateToken()) {
        if (isAjax()) jsonResponse(['success' => false, 'message' => 'Invalid security token.']);
    } else {
        $chatPhone = sanitize($_POST['phone'] ?? '');
        if ($chatPhone) {
            $db->query("DELETE FROM messages WHERE user_id = ? AND to_number = ?", [$userId, $chatPhone]);
            if (isAjax()) jsonResponse(['success' => true]);
        }
    }
    if (isAjax()) jsonResponse(['success' => false, 'message' => 'Failed to clear chat.']);
}

// Get active WhatsApp account
$waAccount = $db->fetch("SELECT * FROM whatsapp_accounts WHERE user_id = ? AND status = 'active' LIMIT 1", [$userId]);

// Fetch recent active conversations (grouped by phone number)
$conversations = $db->fetchAll("
    SELECT m.*, c.name as contact_name 
    FROM messages m 
    LEFT JOIN contacts c ON m.contact_id = c.id 
    WHERE m.user_id = ? 
    AND m.id IN (
        SELECT MAX(id) FROM messages WHERE user_id = ? GROUP BY to_number
    )
    ORDER BY m.created_at DESC
", [$userId, $userId]);

$pageTitle = 'Live Chat';
$extraCss = [asset('assets/css/dashboard.css')];
include __DIR__ . '/../includes/header.php';
?>

<style>
    .chat-container {
        display: flex;
        height: calc(100vh - 160px);
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #eee;
    }
    .chat-sidebar {
        width: 320px;
        min-width: 320px;
        border-right: 1px solid #eee;
        display: flex;
        flex-direction: column;
        transition: transform 0.3s ease, opacity 0.3s ease;
    }
    .chat-list {
        flex: 1;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .chat-item {
        padding: 15px;
        border-bottom: 1px solid #f8f9fa;
        cursor: pointer;
        transition: all 0.2s;
    }
    .chat-item:hover { background: #f1f5f9; }
    .chat-item.active { background: #e2e8f0; border-left: 4px solid var(--primary); }
    
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f8fafc;
        min-width: 0;
    }
    .chat-header {
        padding: 15px 20px;
        background: #fff;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .chat-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    .chat-back-btn {
        display: none;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 50%;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: var(--primary, #6C63FF);
        cursor: pointer;
        transition: all 0.2s;
        font-size: 1.1rem;
    }
    .chat-back-btn:hover {
        background: var(--primary, #6C63FF);
        color: #fff;
        border-color: var(--primary, #6C63FF);
    }
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .msg-bubble {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 12px;
        font-size: 0.9rem;
        position: relative;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    .msg-in {
        align-self: flex-start;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .msg-out {
        align-self: flex-end;
        background: var(--primary);
        color: #fff;
    }
    .chat-footer {
        padding: 20px;
        background: #fff;
        border-top: 1px solid #eee;
    }
    .chat-input-row {
        display: flex;
        gap: 10px;
    }
    #activeContactName {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* ===== Mobile Responsive (≤768px) ===== */
    @media (max-width: 768px) {
        .chat-container {
            height: calc(100vh - 120px);
            border-radius: 8px;
            position: relative;
        }
        .chat-sidebar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            min-width: 100%;
            height: 100%;
            z-index: 10;
            background: #fff;
            border-right: none;
            transform: translateX(0);
        }
        /* When a chat is open, hide sidebar */
        .chat-container.chat-open .chat-sidebar {
            transform: translateX(-100%);
            pointer-events: none;
            opacity: 0;
        }
        .chat-main {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 5;
            transform: translateX(100%);
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }
        /* When a chat is open, show main area */
        .chat-container.chat-open .chat-main {
            transform: translateX(0);
            opacity: 1;
            z-index: 15;
        }
        .chat-back-btn {
            display: flex;
        }
        .chat-header {
            padding: 12px 15px;
        }
        .chat-messages {
            padding: 15px;
            gap: 10px;
        }
        .chat-footer {
            padding: 12px 15px;
        }
        .msg-bubble {
            max-width: 85%;
            font-size: 0.85rem;
        }
        .chat-item {
            padding: 12px 15px;
        }
        #clearChatBtn {
            font-size: 0.75rem;
            padding: 4px 8px !important;
            white-space: nowrap;
        }
    }

    /* ===== Small Mobile (≤480px) ===== */
    @media (max-width: 480px) {
        .chat-container {
            height: calc(100vh - 100px);
            border-radius: 6px;
        }
        .msg-bubble {
            max-width: 90%;
            padding: 8px 12px;
            font-size: 0.82rem;
        }
        .chat-header {
            padding: 10px 12px;
        }
        .chat-messages {
            padding: 10px;
            gap: 8px;
        }
        .chat-footer {
            padding: 10px 12px;
        }
        .chat-input-row .btn {
            padding: 6px 12px;
        }
        #activeContactName {
            font-size: 0.9rem;
        }
    }
</style>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Live Chat</h1>
                <div class="dash-breadcrumb"><a href="<?= baseUrl('dashboard/'); ?>">Dashboard</a><i class="bi bi-chevron-right"></i><span>Live Chat</span></div>
            </div>
            <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
        </div>

        <?php if (!$waAccount): ?>
            <div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> Please connect your WhatsApp account first.</div>
        <?php else: ?>

        <div class="chat-container">
            <!-- Sidebar: Conversations -->
            <div class="chat-sidebar">
                <div class="p-3 border-bottom">
                    <input type="text" id="chatSearch" class="form-control form-control-sm" placeholder="Search contacts...">
                </div>
                <div class="chat-list">
                    <?php if (empty($conversations)): ?>
                        <div class="p-4 text-center text-muted">No conversations found</div>
                    <?php else: ?>
                        <?php foreach ($conversations as $c): ?>
                            <div class="chat-item" onclick="loadMessages('<?= $c['to_number']; ?>', this)">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold"><?= e($c['contact_name'] ?? $c['to_number']); ?></span>
                                    <small class="text-muted"><?= date('H:i', strtotime($c['created_at'])); ?></small>
                                </div>
                                <div class="text-muted text-truncate mini-msg" style="font-size: 0.75rem;">
                                    <?= $c['direction'] === 'outbound' ? '✓ ' : ''; ?><?= e(substr($c['content'] ?? '', 0, 30)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="chat-main">
                <div class="chat-header">
                    <div class="chat-header-left">
                        <button class="chat-back-btn" onclick="goBackToList()" title="Back to contacts"><i class="bi bi-arrow-left"></i></button>
                        <div style="min-width:0;">
                            <div id="activeContactName" class="fw-bold">Select a conversation</div>
                            <div class="text-success small" id="activeStatus"></div>
                        </div>
                    </div>
                    <button class="btn btn-danger btn-sm" id="clearChatBtn" style="display: none; align-items: center; gap: 5px;" onclick="clearCurrentChat()"><i class="bi bi-trash"></i> Clear Chat</button>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                        <div class="text-center">
                            <i class="bi bi-chat-left-text" style="font-size: 3rem; opacity: 0.2;"></i>
                            <p>Click on a contact to start chatting</p>
                        </div>
                    </div>
                </div>

                <div class="chat-footer" id="chatFooter" style="display: none;">
                    <form id="chatForm">
                        <div class="chat-input-row">
                            <input type="text" id="chatInput" class="form-control" placeholder="Type a message..." required>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send-fill"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
    let currentChat = null;

    function isMobileView() {
        return window.innerWidth <= 768;
    }

    function goBackToList() {
        const container = document.querySelector('.chat-container');
        if (container) {
            container.classList.remove('chat-open');
        }
    }

    function loadMessages(phone, el) {
        currentChat = phone;
        document.querySelectorAll('.chat-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('activeContactName').innerText = phone;
        document.getElementById('chatFooter').style.display = 'block';
        document.getElementById('clearChatBtn').style.display = 'flex';

        // On mobile, slide to chat view
        if (isMobileView()) {
            document.querySelector('.chat-container').classList.add('chat-open');
            history.pushState({ chatOpen: true }, '');
        }
        
        // In a real app, this would be an AJAX call
        document.getElementById('chatMessages').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
        
        fetch('<?= baseUrl('api/chat-history.php'); ?>?phone=' + phone)
            .then(r => r.json())
            .then(data => {
                let html = '';
                data.forEach(m => {
                    html += `<div class="msg-bubble ${m.direction === 'inbound' ? 'msg-in' : 'msg-out'}">
                        ${m.content}
                        <div style="font-size: 0.65rem; opacity: 0.7; margin-top: 4px; text-align: right;">${m.time}</div>
                    </div>`;
                });
                document.getElementById('chatMessages').innerHTML = html;
                scrollToBottom();
            });
    }

    function scrollToBottom() {
        const container = document.getElementById('chatMessages');
        container.scrollTop = container.scrollHeight;
    }

    document.getElementById('chatForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = document.getElementById('chatInput').value;
        if (!msg || !currentChat) return;

        // AJAX to send message
        fetch('<?= baseUrl('dashboard/messages.php'); ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send&to=${currentChat}&message_type=text&content=${encodeURIComponent(msg)}&_csrf_token=<?= CSRF::generateToken(); ?>`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('chatInput').value = '';
                loadMessages(currentChat, document.querySelector('.chat-item.active'));
            }
        });
    });

    function clearCurrentChat() {
        if (!currentChat) return;
        if (confirm('WARNING: This will safely and securely clear ALL messages in this conversation! Are you absolutely sure?')) {
            fetch('<?= baseUrl('dashboard/live-chat.php'); ?>', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=clear_chat&phone=${encodeURIComponent(currentChat)}&_csrf_token=<?= CSRF::generateToken(); ?>`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error clearing chat.');
                }
            });
        }
    }
    // Search contacts filter
    document.getElementById('chatSearch')?.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.chat-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // Handle browser/Android back button on mobile
    window.addEventListener('popstate', function(e) {
        if (isMobileView() && document.querySelector('.chat-container.chat-open')) {
            goBackToList();
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
