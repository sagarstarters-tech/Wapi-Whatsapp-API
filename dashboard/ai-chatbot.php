<?php
/**
 * WAPI SaaS - AI ChatBot Builder
 * Lists all user's AI bots with management actions
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$settings = new Settings();
$userId = $_SESSION['user_id'];
$hideNav = true;

// Fetch user's AI bots
$bots = $db->fetchAll("SELECT b.*, 
    (SELECT COUNT(*) FROM ai_conversations WHERE bot_id = b.id) as total_conversations,
    wa.phone_number as wa_phone, wa.business_name as wa_business
    FROM ai_bots b 
    LEFT JOIN whatsapp_accounts wa ON b.whatsapp_account_id = wa.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC", [$userId]);

// Plan limit for AI bots
$subscription = $db->fetch("SELECT s.*, p.ai_bots_limit FROM subscriptions s 
    JOIN plans p ON s.plan_id = p.id 
    WHERE s.user_id = ? AND s.status = 'active' 
    ORDER BY s.created_at DESC LIMIT 1", [$userId]);
$botsLimit = $subscription['ai_bots_limit'] ?? 0;
$botsUsed = count($bots);

// Fetch WA accounts for display
$waAccounts = $db->fetchAll('SELECT id, phone_number, business_name FROM whatsapp_accounts WHERE user_id = ?', [$userId]);

$pageTitle = 'AI ChatBot Builder';
$extraCss = [asset('assets/css/ai-chatbot.css')];
$extraJs = ['https://cdn.jsdelivr.net/npm/sweetalert2@11', asset('assets/js/ai-chatbot.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">🤖 AI ChatBot Builder</h1>
                <div class="dash-breadcrumb">
                    <a href="<?= baseUrl('dashboard/'); ?>">Dashboard</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>AI ChatBot Builder</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
                <a href="<?= baseUrl('dashboard/ai-chatbot-editor.php'); ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Create New Bot
                </a>
            </div>
        </div>

        <!-- Plan Usage Indicator -->
        <div class="card mb-4" style="border-radius: var(--border-radius);">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <i class="bi bi-robot text-primary" style="font-size: 1.25rem;"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span style="font-size: 0.875rem; font-weight: 600;"><?= $botsUsed; ?> / <?= $botsLimit > 0 ? $botsLimit : '∞'; ?> AI Bots used</span>
                            <?php if ($botsLimit > 0): ?>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?= $botsLimit > 0 ? round(($botsUsed / $botsLimit) * 100) : 0; ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div style="background: var(--border-color); border-radius: 4px; height: 6px; overflow: hidden;">
                            <div style="background: linear-gradient(90deg, var(--primary), #00d2ff); height: 100%; width: <?= $botsLimit > 0 ? min(100, ($botsUsed / $botsLimit) * 100) : 0; ?>%; border-radius: 4px; transition: width 0.3s;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?> fade-in"><i class="bi bi-check-circle-fill"></i> <?= e($flash['message']); ?></div>
        <?php endif; ?>

        <?php if (!empty($bots)): ?>
        <!-- Bot Cards Grid -->
        <div class="row g-4">
            <?php foreach ($bots as $bot): ?>
            <div class="col-lg-4 col-md-6" id="bot-card-<?= $bot['id']; ?>">
                <div class="card h-100" style="border-radius: var(--border-radius); border-top: 3px solid transparent; border-image: linear-gradient(90deg, var(--primary), #00d2ff) 1; overflow: visible;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background: <?php
                                    if ($bot['status'] === 'active') echo '#28a745';
                                    elseif ($bot['status'] === 'suspended') echo '#dc3545';
                                    else echo '#6c757d';
                                ?>;"></span>
                                <span class="status-badge status-<?= $bot['status'] === 'active' ? 'active' : ($bot['status'] === 'suspended' ? 'inactive' : 'inactive'); ?>">
                                    <?= ucfirst(e($bot['status'])); ?>
                                </span>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" style="border-radius: 8px; padding: 4px 8px;">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="border-radius: 10px;">
                                    <li><a class="dropdown-item" href="<?= baseUrl('dashboard/ai-chatbot-editor.php?id=' . $bot['id']); ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="cloneBot(<?= $bot['id']; ?>, '<?= e(addslashes($bot['name'])); ?>')"><i class="bi bi-copy me-2"></i>Clone</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="toggleBotStatus(<?= $bot['id']; ?>, '<?= $bot['status']; ?>')">
                                            <i class="bi bi-<?= $bot['status'] === 'active' ? 'pause-circle' : 'play-circle'; ?> me-2"></i>
                                            <?= $bot['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                    </li>
                                    <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteBot(<?= $bot['id']; ?>, '<?= e(addslashes($bot['name'])); ?>')"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                </ul>
                            </div>
                        </div>

                        <h5 class="fw-bold mb-2" style="font-size: 1.0625rem;"><?= e($bot['name']); ?></h5>
                        <p class="text-muted mb-3" style="font-size: 0.8125rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= e($bot['description'] ?? 'No description'); ?>
                        </p>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php if (!empty($bot['wa_phone'])): ?>
                            <span class="badge-custom" style="background: rgba(21,163,98,0.1); color: #15a362; font-size: 0.75rem;">
                                <i class="bi bi-whatsapp me-1"></i><?= e($bot['wa_phone']); ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($bot['model'])): ?>
                            <span class="badge-custom" style="background: var(--primary-bg); color: var(--primary); font-size: 0.75rem;">
                                <i class="bi bi-cpu me-1"></i><?= e(strtoupper($bot['model'])); ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3" style="border-top: 1px solid var(--border-color);">
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <i class="bi bi-chat-dots me-1"></i><?= formatNumber($bot['total_conversations']); ?> conversations
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <i class="bi bi-calendar3 me-1"></i><?= formatDate($bot['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top p-3">
                        <a href="<?= baseUrl('dashboard/ai-chatbot-editor.php?id=' . $bot['id']); ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="bi bi-pencil-square me-1"></i> Edit Bot
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Empty State -->
        <div class="card" style="border-radius: var(--border-radius);">
            <div class="card-body text-center py-5">
                <div style="width: 100px; height: 100px; background: var(--primary-bg); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                    <i class="bi bi-robot" style="font-size: 3rem; color: var(--primary);"></i>
                </div>
                <h4 class="fw-bold mb-2">No AI Bots Yet</h4>
                <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                    Create your first AI-powered chatbot to automate WhatsApp conversations, handle customer queries, and generate leads 24/7.
                </p>
                <a href="<?= baseUrl('dashboard/ai-chatbot-editor.php'); ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Create Your First AI Bot
                </a>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<script>
// Delete Bot
function deleteBot(botId, botName) {
    Swal.fire({
        title: 'Delete Bot?',
        html: `Are you sure you want to delete <strong>${botName}</strong>? This will remove all conversations and training data.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= baseUrl('api/ai-bot/delete.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= CSRF::token(); ?>' },
                body: JSON.stringify({ bot_id: botId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('bot-card-' + botId)?.remove();
                    Swal.fire('Deleted!', 'Bot has been deleted.', 'success');
                    if (!document.querySelector('[id^="bot-card-"]')) location.reload();
                } else {
                    Swal.fire('Error', data.message || 'Failed to delete bot.', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Network error occurred.', 'error'));
        }
    });
}

// Toggle Bot Status
function toggleBotStatus(botId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    fetch('<?= baseUrl('api/ai-bot/toggle-status.php'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= CSRF::token(); ?>' },
        body: JSON.stringify({ bot_id: botId, status: newStatus })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Status Updated', text: `Bot is now ${newStatus}.`, timer: 1500, showConfirmButton: false });
            setTimeout(() => location.reload(), 1500);
        } else {
            Swal.fire('Error', data.message || 'Failed to update status.', 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Network error occurred.', 'error'));
}

// Clone Bot
function cloneBot(botId, botName) {
    Swal.fire({
        title: 'Clone Bot?',
        html: `Create a copy of <strong>${botName}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Clone',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= baseUrl('api/ai-bot/clone.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= CSRF::token(); ?>' },
                body: JSON.stringify({ bot_id: botId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Cloned!', 'Bot has been cloned successfully.', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Failed to clone bot.', 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Network error occurred.', 'error'));
        }
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
