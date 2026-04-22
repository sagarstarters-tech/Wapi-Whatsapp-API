<?php
/**
 * WAPI SaaS - Bulk Messages Page
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$settings = new Settings();
$userId = $_SESSION['user_id'];

$hideNav = true; // Prevents landing page nav from appearing in dashboard

$waAccount = $db->fetch("SELECT * FROM whatsapp_accounts WHERE user_id = ? AND status = 'active' LIMIT 1", [$userId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validateToken()) {
    // Handle AJAX Sync Templates
    if (isAjax() && ($_POST['action'] ?? '') === 'sync_templates') {
        try {
            $wa = new WhatsApp();
            jsonResponse($wa->syncTemplates($userId));
        } catch (\Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Sync error: ' . $e->getMessage()]);
        }
    }

    if (!$waAccount) {
        setFlash('danger', 'Configure your WhatsApp API first.');
        redirect('dashboard/whatsapp.php');
    }

    $type = sanitize($_POST['message_type'] ?? 'text');
    $content = $_POST['content'] ?? '';
    $mediaUrl = sanitize($_POST['media_url'] ?? '');
    
    if ($type === 'template') {
        $templateId = sanitizeInt($_POST['template_id'] ?? 0);
        $tpl = $db->fetch("SELECT name FROM templates WHERE id = ? AND user_id = ?", [$templateId, $userId]);
        if ($tpl) {
            $content = $tpl['name']; // WhatsApp::sendBulk expects template name in content for template type
        }
    }
    $target = sanitize($_POST['target'] ?? 'all');

    // Get contacts
    $contacts = [];
    if ($target === 'all') {
        $contacts = $db->fetchAll("SELECT phone FROM contacts WHERE user_id = ? AND is_active = 1", [$userId]);
    } elseif ($target === 'tag') {
        $tag = sanitize($_POST['tag'] ?? '');
        $contacts = $db->fetchAll("SELECT phone FROM contacts WHERE user_id = ? AND is_active = 1 AND tags LIKE ?", [$userId, "%{$tag}%"]);
    } elseif ($target === 'custom') {
        $numbers = array_filter(array_map('trim', explode("\n", $_POST['numbers'] ?? '')));
        foreach ($numbers as $num) {
            $contacts[] = ['phone' => $num];
        }
    }

    if (empty($contacts)) {
        setFlash('danger', 'No contacts found for the selected target.');
        redirect('dashboard/bulk-messages.php');
    }

    $wa = new WhatsApp();
    $result = $wa->sendBulk($userId, $waAccount['phone_number_id'], $waAccount['access_token'], $contacts, $type, $content, $mediaUrl);

    setFlash('success', "Bulk send complete: {$result['success']} sent, {$result['failed']} failed.");
    redirect('dashboard/bulk-messages.php');
}

$totalContacts = $db->count('contacts', 'user_id = ? AND is_active = 1', [$userId]);
$templates = $db->fetchAll("SELECT id, name, language, body FROM templates WHERE user_id = ? AND status = 'approved' ORDER BY name ASC", [$userId]);
$tags = $db->fetchAll("SELECT DISTINCT tags FROM contacts WHERE user_id = ? AND tags != ''", [$userId]);
$allTags = [];
foreach ($tags as $t) {
    foreach (explode(',', $t['tags']) as $tag) {
        $tag = trim($tag);
        if ($tag && !in_array($tag, $allTags)) $allTags[] = $tag;
    }
}

$pageTitle = 'Bulk Messages';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Bulk Messages</h1>
                <div class="dash-breadcrumb"><a href="<?= baseUrl('dashboard/'); ?>">Dashboard</a><i class="bi bi-chevron-right"></i><span>Bulk Messages</span></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
                <button type="button" class="btn btn-outline-success btn-sm" id="syncTemplatesBtn" onclick="syncTemplates()">
                    <i class="bi bi-arrow-repeat"></i> Sync from Meta
                </button>
            </div>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?>"><?= e($flash['message']); ?></div>
        <?php endif; ?>

        <?php if (!$waAccount): ?>
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> <a href="<?= baseUrl('dashboard/whatsapp.php'); ?>" class="fw-bold">Configure WhatsApp API</a> first.</div>
        <?php endif; ?>

        <div class="card" style="border-radius: var(--border-radius);">
            <div class="card-body p-4">
                <form method="POST" onsubmit="return confirm('Send messages to all selected contacts?')">
                    <?= CSRF::tokenField(); ?>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Target Audience</label>
                            <select name="target" class="form-control" id="bulkTarget" onchange="toggleBulkTarget()">
                                <option value="all">All Contacts (<?= $totalContacts; ?>)</option>
                                <option value="tag">By Tag</option>
                                <option value="custom">Custom Numbers</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="tagGroup" style="display:none;">
                            <label class="form-label fw-bold">Select Tag</label>
                            <select name="tag" class="form-control">
                                <?php foreach ($allTags as $tag): ?>
                                <option value="<?= e($tag); ?>"><?= e($tag); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12" id="numbersGroup" style="display:none;">
                            <label class="form-label fw-bold">Phone Numbers (one per line)</label>
                            <textarea name="numbers" class="form-control" rows="5" placeholder="+919876543210&#10;+919876543211&#10;+919876543212"></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Message Type</label>
                            <select name="message_type" id="msgType" class="form-control" onchange="toggleMessageType()">
                                <option value="text">📝 Text</option>
                                <option value="image">🖼️ Image</option>
                                <option value="template">📋 Template</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="mediaGroup" style="display:none;">
                            <label class="form-label fw-bold">Media URL</label>
                            <input type="url" name="media_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-md-6" id="templateGroup" style="display:none;">
                            <label class="form-label fw-bold">Select Template</label>
                            <select name="template_id" id="templateId" class="form-control" onchange="updateTemplatePreview()">
                                <option value="">-- Choose Template --</option>
                                <?php foreach ($templates as $tpl): ?>
                                <option value="<?= $tpl['id']; ?>" data-body="<?= e($tpl['body']); ?>"><?= e($tpl['name']); ?> (<?= e($tpl['language']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12" id="templatePreviewGroup" style="display:none;">
                            <label class="form-label fw-bold">Template Content</label>
                            <div id="templatePreviewBox" style="
                                background: #f0fdf4;
                                border: 1px solid #86efac;
                                border-left: 4px solid #22c55e;
                                border-radius: 8px;
                                padding: 12px 16px;
                                font-size: 0.9rem;
                                color: #166534;
                                min-height: 60px;
                                white-space: pre-wrap;
                                line-height: 1.6;
                            ">
                                <span class="text-muted fst-italic">Select a template to see its content...</span>
                            </div>
                        </div>
                        <div class="col-12" id="contentGroup">
                            <label class="form-label fw-bold">Message Content</label>
                            <textarea name="content" id="msgContent" class="form-control" rows="5" placeholder="Type your message here..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg mt-4" <?= !$waAccount ? 'disabled' : ''; ?>>
                        <i class="bi bi-megaphone-fill"></i> Send Bulk Messages
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function toggleBulkTarget() {
    const target = document.getElementById('bulkTarget').value;
    document.getElementById('tagGroup').style.display = target === 'tag' ? 'block' : 'none';
    document.getElementById('numbersGroup').style.display = target === 'custom' ? 'block' : 'none';
}

function toggleMessageType() {
    const type = document.getElementById('msgType').value;
    document.getElementById('mediaGroup').style.display = (type === 'image') ? 'block' : 'none';
    document.getElementById('templateGroup').style.display = (type === 'template') ? 'block' : 'none';
    document.getElementById('templatePreviewGroup').style.display = (type === 'template') ? 'block' : 'none';
    document.getElementById('contentGroup').style.display = (type === 'template') ? 'none' : 'block';
    document.getElementById('msgContent').toggleAttribute('required', type !== 'template');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleMessageType();
});

function updateTemplatePreview() {
    const select = document.getElementById('templateId');
    const option = select.options[select.selectedIndex];
    const previewBox = document.getElementById('templatePreviewBox');
    if (option && option.value) {
        const body = option.getAttribute('data-body');
        document.getElementById('msgContent').value = body;
        previewBox.textContent = body || 'No content available for this template.';
    } else {
        previewBox.innerHTML = '<span class="text-muted fst-italic">Select a template to see its content...</span>';
    }
}

async function syncTemplates() {
    const btn = document.getElementById('syncTemplatesBtn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Syncing...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'sync_templates');
    formData.append('_csrf_token', '<?= CSRF::generateToken(); ?>');

    try {
        const res = await fetch('', { method: 'POST', body: formData, headers: {'X-Requested-With': 'XMLHttpRequest'} });
        const result = await res.json();
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.message);
        }
    } catch(err) {
        alert('Network error during sync.');
    }
    btn.innerHTML = originalHtml;
    btn.disabled = false;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
