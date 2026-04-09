<?php
/**
 * WAPI SaaS - Message Logs
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$settings = new Settings();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    if (!CSRF::validateToken()) {
        setFlash('danger', 'Invalid security token.');
    } else {
        $db->query("DELETE FROM messages WHERE user_id = ?", [$userId]);
        setFlash('success', 'All message logs have been cleared successfully.');
    }
    redirect('dashboard/message-logs.php');
}

$hideNav = true; // Prevents landing page nav from appearing in dashboard

$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$page = max(1, sanitizeInt($_GET['page'] ?? 1));

$where = 'user_id = ?';
$params = [$userId];

if ($search) {
    $where .= " AND (to_number LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$totalMessages = $db->count('messages', $where, $params);
$pagination = paginate($totalMessages, $page, 20);
$messages = $db->fetchAll("SELECT m.*, c.name as contact_name FROM messages m LEFT JOIN contacts c ON m.contact_id = c.id WHERE m.{$where} ORDER BY m.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}", $params);

$pageTitle = 'Message Logs';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Message Logs</h1>
                <div class="dash-breadcrumb"><a href="<?= baseUrl('dashboard/'); ?>">Dashboard</a><i class="bi bi-chevron-right"></i><span>Logs</span></div>
            </div>
            <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?> fade-in"><i class="bi bi-<?= $flash['type'] === 'success' ? 'check' : 'exclamation'; ?>-circle-fill"></i> <?= e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="data-table">
            <div class="data-table-header">
                <h5 class="data-table-title mb-0">All Messages (<?= $totalMessages; ?>)</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <form method="GET" class="d-flex gap-2 flex-wrap m-0">
                        <div class="search-box"><i class="bi bi-search"></i><input name="search" class="form-control" placeholder="Search..." value="<?= e($search); ?>"></div>
                        <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="sent" <?= $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="queued" <?= $statusFilter === 'queued' ? 'selected' : ''; ?>>Queued</option>
                            <option value="read" <?= $statusFilter === 'read' ? 'selected' : ''; ?>>Read</option>
                        </select>
                    </form>
                    <?php if ($totalMessages > 0): ?>
                    <form method="POST" class="m-0" onsubmit="return confirm('WARNING: This will safely securely clear ALL your message logs! Are you absolutely sure?');">
                        <?= CSRF::tokenField(); ?>
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="btn btn-danger" style="display: flex; align-items: center; gap: 5px;"><i class="bi bi-trash"></i> Clear Logs</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Direction</th><th>To / From</th><th>Type</th><th>Content</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-chat-dots" style="font-size: 2rem;"></i><br>No messages found</td></tr>
                        <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td>
                                <?php if ($msg['direction'] === 'inbound'): ?>
                                <span class="badge-custom" style="background: rgba(16,185,129,0.1); color: var(--success);"><i class="bi bi-arrow-down-left"></i> In</span>
                                <?php else: ?>
                                <span class="badge-custom" style="background: rgba(59,130,246,0.1); color: var(--info);"><i class="bi bi-arrow-up-right"></i> Out</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($msg['contact_name'] ?? $msg['to_number']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($msg['to_number']); ?></div>
                            </td>
                            <td><span class="badge-custom" style="background: var(--primary-bg); color: var(--primary);"><?= ucfirst($msg['type']); ?></span></td>
                            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.875rem;"><?= e(substr($msg['content'], 0, 60)); ?><?= strlen($msg['content']) > 60 ? '...' : ''; ?></td>
                            <td><span class="status-badge status-<?= $msg['status']; ?>"><?= ucfirst($msg['status']); ?></span></td>
                            <td style="font-size: 0.8125rem; color: var(--text-muted); white-space: nowrap;"><?= timeAgo($msg['created_at']); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3"><?= renderPagination($pagination, '?search=' . urlencode($search) . '&status=' . urlencode($statusFilter) . '&page=%d'); ?></div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
