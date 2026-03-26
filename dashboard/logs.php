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

        <div class="data-table">
            <div class="data-table-header">
                <h5 class="data-table-title mb-0">All Messages (<?= $totalMessages; ?>)</h5>
                <form method="GET" class="d-flex gap-2 flex-wrap">
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
