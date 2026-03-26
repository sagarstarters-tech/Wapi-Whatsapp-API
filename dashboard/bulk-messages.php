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
    if (!$waAccount) {
        setFlash('danger', 'Configure your WhatsApp API first.');
        redirect('dashboard/settings.php?tab=whatsapp');
    }

    $type = sanitize($_POST['message_type'] ?? 'text');
    $content = $_POST['content'] ?? '';
    $mediaUrl = sanitize($_POST['media_url'] ?? '');
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
    redirect('/wapi/dashboard/bulk-messages.php');
}

$totalContacts = $db->count('contacts', 'user_id = ? AND is_active = 1', [$userId]);
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
            <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?>"><?= e($flash['message']); ?></div>
        <?php endif; ?>

        <?php if (!$waAccount): ?>
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle-fill"></i> <a href="<?= baseUrl('dashboard/settings.php?tab=whatsapp'); ?>" class="fw-bold">Configure WhatsApp API</a> first.</div>
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
                            <select name="message_type" class="form-control" onchange="document.getElementById('mediaGroup').style.display = this.value !== 'text' ? 'block' : 'none'">
                                <option value="text">📝 Text</option>
                                <option value="image">🖼️ Image</option>
                                <option value="template">📋 Template</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="mediaGroup" style="display:none;">
                            <label class="form-label fw-bold">Media URL</label>
                            <input type="url" name="media_url" class="form-control" placeholder="https://...">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Message Content</label>
                            <textarea name="content" class="form-control" rows="5" required placeholder="Type your message here..."></textarea>
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
