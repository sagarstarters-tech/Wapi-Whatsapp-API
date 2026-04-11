<?php
/**
 * WAPI SaaS - Send Message Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$settings = new Settings();
$userId = $_SESSION['user_id'];

$hideNav = true; // Prevents landing page nav from appearing in dashboard

// Get user's WhatsApp account
$waAccount = $db->fetch("SELECT * FROM whatsapp_accounts WHERE user_id = ? AND status = 'active' LIMIT 1", [$userId]);

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken()) {
        if (isAjax()) {
            jsonResponse(['success' => false, 'message' => 'Security token mismatch. Please refresh the page.']);
        }
        setFlash('danger', 'Security token mismatch.');
        redirect('dashboard/messages.php');
    }

    if (!$waAccount) {
        if (isAjax()) {
            jsonResponse(['success' => false, 'message' => 'Please configure your WhatsApp API settings first.']);
        }
        setFlash('danger', 'Please configure your WhatsApp API settings first.');
        redirect('dashboard/whatsapp.php');
    }
    
    $to = sanitize($_POST['to'] ?? '');
    $type = sanitize($_POST['message_type'] ?? 'text');
    $content = $_POST['content'] ?? '';
    $mediaUrl = sanitize($_POST['media_url'] ?? '');

    // Safely handle image upload if provided
    if ($type === 'image' && isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['image_file'], 'messages', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($upload['success']) {
            $mediaUrl = baseUrl($upload['path']);
        } else {
            if (isAjax()) jsonResponse(['success' => false, 'message' => 'Image upload failed: ' . $upload['message']]);
            setFlash('danger', 'Image upload failed: ' . $upload['message']);
            redirect('dashboard/messages.php');
        }
    }

    if (isAjax()) {
        try {
            $wa = new WhatsApp();
            $result = null;

            switch ($type) {
                case 'text': $result = $wa->sendText($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $to, $content); break;
                case 'image': $result = $wa->sendImage($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $to, $mediaUrl, $content); break;
                case 'video': $result = $wa->sendVideo($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $to, $mediaUrl, $content); break;
                case 'document': $result = $wa->sendDocument($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $to, $mediaUrl, sanitize($_POST['filename'] ?? ''), $content); break;
                case 'template':
                    $templateId = sanitizeInt($_POST['template_id'] ?? 0);
                    $tpl = $db->fetch("SELECT name, language FROM templates WHERE id = ? AND user_id = ?", [$templateId, $userId]);
                    if ($tpl) {
                        $result = $wa->sendTemplate($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $to, $tpl['name'], $tpl['language']);
                    } else {
                        $result = ['success' => false, 'message' => 'Invalid template selected.'];
                    }
                    break;
            }

            jsonResponse($result);
        } catch (\Exception $e) {
            jsonResponse(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
        }
    }
}

// Get contacts for autocomplete
$contacts = $db->fetchAll("SELECT id, name, phone FROM contacts WHERE user_id = ? AND is_active = 1 ORDER BY name ASC LIMIT 100", [$userId]);
$templates = $db->fetchAll("SELECT id, name, language, body FROM templates WHERE user_id = ? AND status = 'approved' ORDER BY name ASC", [$userId]);

$pageTitle = 'Send Message';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Send Message</h1>
                <div class="dash-breadcrumb"><a href="<?= baseUrl('dashboard/'); ?>">Dashboard</a><i class="bi bi-chevron-right"></i><span>Send Message</span></div>
            </div>
            <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
        </div>

        <?php if (!$waAccount): ?>
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> Please <a href="<?= baseUrl('dashboard/whatsapp.php'); ?>" class="fw-bold">configure your WhatsApp API</a> to start sending messages.</div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card" style="border-radius: var(--border-radius);">
                    <div class="card-body p-4">
                        <div id="alertContainer"></div>
                        
                        <form id="sendMessageForm" method="POST" enctype="multipart/form-data">
                            <?= CSRF::tokenField(); ?>
                            
                            <div class="form-group">
                                <label class="form-label">Recipient Phone Number</label>
                                <div class="input-group">
                                    <i class="bi bi-phone input-icon"></i>
                                    <input type="text" name="to" id="msgTo" class="form-control" placeholder="+91 9876543210" required list="contactsList">
                                </div>
                                <datalist id="contactsList">
                                    <?php foreach ($contacts as $c): ?>
                                    <option value="<?= e($c['phone']); ?>"><?= e($c['name']); ?> (<?= e($c['phone']); ?>)</option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Message Type</label>
                                <select name="message_type" id="msgType" class="form-control" onchange="toggleMediaField()">
                                    <option value="text">📝 Text Message</option>
                                    <option value="image">🖼️ Image</option>
                                    <option value="video">🎬 Video</option>
                                    <option value="document">📄 Document</option>
                                    <option value="template">📋 Template Message</option>
                                </select>
                            </div>

                            <div class="form-group" id="templateGroup" style="display: none;">
                                <label class="form-label">Select Template</label>
                                <select name="template_id" id="templateId" class="form-control" onchange="updateTemplatePreview()">
                                    <option value="">-- Choose Template --</option>
                                    <?php foreach ($templates as $tpl): ?>
                                    <option value="<?= $tpl['id']; ?>" data-body="<?= e($tpl['body']); ?>"><?= e($tpl['name']); ?> (<?= e($tpl['language']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" id="mediaUrlGroup" style="display: none;">
                                <label class="form-label">Media URL</label>
                                <input type="url" name="media_url" id="mediaUrl" class="form-control" placeholder="https://example.com/image.jpg">
                                <div id="imageUploadGroup" style="display: none; margin-top: 10px;">
                                    <label class="form-label small text-muted">Or Upload Image Instead</label>
                                    <input type="file" name="image_file" id="imageFile" class="form-control" accept="image/*">
                                </div>
                            </div>

                            <div class="form-group" id="filenameGroup" style="display: none;">
                                <label class="form-label">Filename</label>
                                <input type="text" name="filename" class="form-control" placeholder="document.pdf">
                            </div>

                            <div class="form-group" id="contentGroup">
                                <label class="form-label">Message / Caption</label>
                                <textarea name="content" id="msgContent" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
                                <small class="text-muted"><span id="charCount">0</span>/4096 characters</small>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100" id="sendBtn" <?= !$waAccount ? 'disabled' : ''; ?>>
                                <i class="bi bi-send-fill"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="col-lg-5">
                <div class="whatsapp-phone">
                    <div class="whatsapp-screen">
                        <div class="wa-header">
                            <div class="wa-avatar"><i class="bi bi-person-fill"></i></div>
                            <div>
                                <div class="wa-name" id="previewName">Recipient</div>
                                <div class="wa-status">WhatsApp</div>
                            </div>
                        </div>
                        <div class="wa-messages" id="previewMessages" style="min-height: 300px;">
                            <div class="text-center text-muted py-5" id="previewPlaceholder" style="font-size: 0.875rem;">
                                <i class="bi bi-chat-dots" style="font-size: 2rem;"></i><br>
                                Your message preview will appear here
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Toggle media field based on message type
function toggleMediaField() {
    const type = document.getElementById('msgType').value;
    document.getElementById('mediaUrlGroup').style.display = ['image','video','document'].includes(type) ? 'block' : 'none';
    document.getElementById('imageUploadGroup').style.display = type === 'image' ? 'block' : 'none';
    document.getElementById('filenameGroup').style.display = type === 'document' ? 'block' : 'none';
    document.getElementById('templateGroup').style.display = type === 'template' ? 'block' : 'none';
    document.getElementById('contentGroup').style.display = type === 'template' ? 'none' : 'block';
    
    // Auto-update preview visibility
    document.getElementById('msgContent').toggleAttribute('required', type !== 'template');
    document.getElementById('templateId').toggleAttribute('required', type === 'template');
    updatePreview();
}

function updateTemplatePreview() {
    const select = document.getElementById('templateId');
    const option = select.options[select.selectedIndex];
    if (option && option.value) {
        const body = option.getAttribute('data-body');
        document.getElementById('msgContent').value = body;
        updatePreview();
    }
}

// Character counter
document.getElementById('msgContent').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
    updatePreview();
});

document.getElementById('msgTo').addEventListener('input', function() {
    document.getElementById('previewName').textContent = this.value || 'Recipient';
});

function updatePreview() {
    const content = document.getElementById('msgContent').value;
    const preview = document.getElementById('previewMessages');
    const placeholder = document.getElementById('previewPlaceholder');
    
    if (content.trim()) {
        placeholder.style.display = 'none';
        let existing = preview.querySelector('.wa-bubble.sent');
        if (!existing) {
            existing = document.createElement('div');
            existing.className = 'wa-bubble sent';
            preview.appendChild(existing);
        }
        const now = new Date();
        existing.innerHTML = '<div>' + escapeHtml(content) + '</div><div class="wa-time">' + now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0') + ' ✓✓</div>';
    } else {
        placeholder.style.display = '';
        const existing = preview.querySelector('.wa-bubble.sent');
        if (existing) existing.remove();
    }
}

// Send message via AJAX
document.getElementById('sendMessageForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('sendBtn');
    btn.innerHTML = '<span class="spinner" style="width:20px;height:20px;border-width:2px;display:inline-block;"></span> Sending...';
    btn.disabled = true;

    const formData = new FormData(this);
    try {
        const res = await fetch('', { method: 'POST', body: formData, headers: {'X-Requested-With': 'XMLHttpRequest'} });
        const text = await res.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch(e) {
            console.error('Failed to parse JSON:', text);
            showAlert('#alertContainer', 'danger', 'Server returned invalid response. Check console.');
            btn.innerHTML = '<i class="bi bi-send-fill"></i> Send Message';
            btn.disabled = false;
            return;
        }

        showAlert('#alertContainer', result.success ? 'success' : 'danger', result.message);
        if (result.success) {
            document.getElementById('msgContent').value = '';
            document.getElementById('charCount').textContent = '0';
            updatePreview();
        }
    } catch(err) {
        console.error('Fetch Error:', err);
        showAlert('#alertContainer', 'danger', 'Network error. Please try again.');
    }
    btn.innerHTML = '<i class="bi bi-send-fill"></i> Send Message';
    btn.disabled = false;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
