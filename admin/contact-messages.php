<?php
/**
 * WAPI SaaS - Admin Contact Messages
 * View and manage messages submitted via the Contact Us form.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireAdmin();

$db = Database::getInstance();
$settings = new Settings();

$hideNav = true;

// ---- Handle actions (AJAX) --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!CSRF::validateToken()) {
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh the page.']);
        exit;
    }

    $id = sanitizeInt($_POST['id'] ?? 0);

    if ($_POST['action'] === 'update_status') {
        $newStatus = sanitize($_POST['status'] ?? '');
        if (!in_array($newStatus, ['unread', 'read', 'replied'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit;
        }
        $db->update('contact_messages', ['status' => $newStatus], 'id = ?', [$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $db->delete('contact_messages', 'id = ?', [$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($_POST['action'] === 'resend_email') {
        $msg = $db->fetch("SELECT * FROM contact_messages WHERE id = ?", [$id]);
        if (!$msg) {
            echo json_encode(['success' => false, 'message' => 'Message not found.']);
            exit;
        }

        $adminEmail = trim((string)$settings->get('contact_email', ''));
        if (empty($adminEmail) || stripos($adminEmail, 'support@wapi.com') !== false) {
            if (defined('SMTP_USER') && !empty(SMTP_USER) && stripos(SMTP_USER, '@wapi.com') === false) {
                $adminEmail = SMTP_USER;
            } elseif ($realAdmin = $db->fetch("SELECT email FROM users WHERE role = 'admin' AND email NOT LIKE '%@wapi.com' ORDER BY id ASC LIMIT 1")) {
                $adminEmail = $realAdmin['email'];
            }
        }

        if (empty($adminEmail)) {
            echo json_encode(['success' => false, 'message' => 'No admin contact email configured in settings.']);
            exit;
        }

        $siteName    = $settings->get('site_name', 'WAPI');
        $senderName  = htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name'], ENT_QUOTES, 'UTF-8');
        $senderEmail = htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8');
        $senderPhone = htmlspecialchars($msg['phone'] ?? '', ENT_QUOTES, 'UTF-8');
        $subjectSafe = htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8');
        $messageSafe = nl2br(htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8'));
        $adminUrl    = function_exists('baseUrl') ? baseUrl('admin/contact-messages.php') : (rtrim(APP_URL, '/') . '/admin/contact-messages.php');
        $dateTime    = date('d M Y, h:i A', strtotime($msg['created_at']));

        $emailSubject = "[{$siteName}] (Resent) Contact Message: {$msg['subject']}";
        $emailBody = "
        <div style='font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
            <div style='background: linear-gradient(135deg, #6c63ff, #3b82f6); padding: 24px 30px;'>
                <h2 style='margin: 0; color: #ffffff; font-size: 20px;'>📩 Contact Form Message (Notification)</h2>
                <p style='margin: 6px 0 0; color: rgba(255,255,255,0.85); font-size: 13px;'>{$siteName} &middot; {$dateTime}</p>
            </div>
            <div style='padding: 28px 30px;'>
                <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                    <tr><td style='padding: 10px 0; color: #666; width: 110px;'><strong>From:</strong></td><td>{$senderName}</td></tr>
                    <tr><td style='padding: 10px 0; color: #666;'><strong>Email:</strong></td><td><a href='mailto:{$senderEmail}'>{$senderEmail}</a></td></tr>
                    <tr><td style='padding: 10px 0; color: #666;'><strong>Phone:</strong></td><td><a href='tel:{$senderPhone}'>{$senderPhone}</a></td></tr>
                    <tr><td style='padding: 10px 0; color: #666;'><strong>Subject:</strong></td><td>{$subjectSafe}</td></tr>
                </table>
                <div style='margin-top: 18px; padding: 18px; background: #f8f9fa; border-left: 4px solid #6c63ff; border-radius: 4px; font-size: 14px; line-height: 1.7;'>
                    {$messageSafe}
                </div>
                <div style='margin-top: 24px; text-align: center;'>
                    <a href='{$adminUrl}' style='display: inline-block; background: #6c63ff; color: #fff; padding: 10px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>View in Admin Panel</a>
                </div>
            </div>
        </div>";

        $mailResult = Mail::send($adminEmail, $emailSubject, $emailBody, null, null, $msg['email'], $msg['first_name'] . ' ' . $msg['last_name']);
        
        $emailSent  = $mailResult['success'] ? 1 : 0;
        $emailError = $mailResult['success'] ? null : substr($mailResult['message'], 0, 250);

        $db->update('contact_messages', [
            'email_sent'  => $emailSent,
            'email_error' => $emailError
        ], 'id = ?', [$id]);

        if ($mailResult['success']) {
            echo json_encode(['success' => true, 'message' => "Email sent successfully to {$adminEmail}!"]);
        } else {
            echo json_encode(['success' => false, 'message' => "Email delivery failed: " . $mailResult['message']]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ---- Ensure table exists with all columns ----------------------------------
$db->query("CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread','read','replied') DEFAULT 'unread',
    `email_sent` TINYINT(1) DEFAULT 0,
    `email_error` VARCHAR(255) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Add phone column if it doesn't exist (for existing tables)
try {
    $db->query("ALTER TABLE `contact_messages` ADD COLUMN `phone` VARCHAR(30) DEFAULT NULL AFTER `email`");
} catch (Exception $e) {}

// Add email_sent & email_error columns if they don't exist
try {
    $db->query("ALTER TABLE `contact_messages` ADD COLUMN `email_sent` TINYINT(1) DEFAULT 0 AFTER `status`");
} catch (Exception $e) {}
try {
    $db->query("ALTER TABLE `contact_messages` ADD COLUMN `email_error` VARCHAR(255) DEFAULT NULL AFTER `email_sent`");
} catch (Exception $e) {}

// ---- Filters & Pagination --------------------------------------------------
$search       = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$page         = max(1, sanitizeInt($_GET['page'] ?? 1));

$where  = '1';
$params = [];

if ($search) {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ?)";
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
}
if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$totalMessages = $db->fetchColumn("SELECT COUNT(*) FROM contact_messages WHERE {$where}", $params);
$pagination    = paginate($totalMessages, $page, 20);
$messages      = $db->fetchAll(
    "SELECT * FROM contact_messages WHERE {$where} ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

$unreadCount = $db->fetchColumn("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");

$pageTitle = 'Contact Messages';
$extraCss  = [asset('assets/css/dashboard.css')];
$extraJs   = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Contact Messages</h1>
                <div class="dash-breadcrumb">
                    <a href="<?= baseUrl('admin/'); ?>">Admin</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Contact Messages</span>
                </div>
            </div>
            <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
        </div>

        <div class="data-table">
            <div class="data-table-header">
                <h5 class="data-table-title mb-0">
                    All Messages (<?= $totalMessages; ?>)
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge bg-danger ms-2"><?= $unreadCount; ?> unread</span>
                    <?php endif; ?>
                </h5>
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <div class="search-box"><i class="bi bi-search"></i><input name="search" class="form-control" placeholder="Search..." value="<?= e($search); ?>"></div>
                    <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="unread" <?= $statusFilter === 'unread' ? 'selected' : ''; ?>>Unread</option>
                        <option value="read" <?= $statusFilter === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?= $statusFilter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Email Delivery</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No contact messages found</td></tr>
                        <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                        <tr id="row-<?= $msg['id']; ?>" class="<?= $msg['status'] === 'unread' ? 'table-warning' : ''; ?>">
                            <td>
                                <div class="fw-semibold" style="font-size: 0.875rem;"><?= e($msg['first_name'] . ' ' . $msg['last_name']); ?></div>
                            </td>
                            <td style="font-size: 0.8125rem;">
                                <div><a href="mailto:<?= e($msg['email']); ?>" class="text-decoration-none"><?= e($msg['email']); ?></a></div>
                                <?php if (!empty($msg['phone'])): ?>
                                    <div class="text-muted"><i class="bi bi-telephone text-secondary me-1"></i><?= e($msg['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.875rem; font-weight: 500;"><?= e($msg['subject']); ?></td>
                            <td style="max-width: 200px; font-size: 0.8125rem;" class="text-truncate" title="<?= e($msg['message']); ?>"><?= e(substr($msg['message'], 0, 50)); ?></td>
                            <td id="delivery-cell-<?= $msg['id']; ?>">
                                <?php if (!empty($msg['email_sent'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" title="Delivered to admin email inbox"><i class="bi bi-check-circle-fill me-1"></i>Delivered</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" title="<?= e($msg['email_error'] ?? 'Pending or SMTP not authenticated'); ?>"><i class="bi bi-exclamation-triangle-fill me-1"></i>Not Delivered</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="form-select form-select-sm status-select" data-id="<?= $msg['id']; ?>" style="width: 105px;">
                                    <option value="unread" <?= $msg['status'] === 'unread' ? 'selected' : ''; ?>>Unread</option>
                                    <option value="read" <?= $msg['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                    <option value="replied" <?= $msg['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                </select>
                            </td>
                            <td style="font-size: 0.8125rem; color: var(--text-muted); white-space: nowrap;"><?= timeAgo($msg['created_at']); ?></td>
                            <td style="white-space: nowrap;">
                                <button class="btn btn-sm btn-outline-primary me-1 view-msg-btn"
                                    data-id="<?= $msg['id']; ?>"
                                    data-name="<?= e($msg['first_name'] . ' ' . $msg['last_name']); ?>"
                                    data-email="<?= e($msg['email']); ?>"
                                    data-phone="<?= e($msg['phone'] ?? ''); ?>"
                                    data-subject="<?= e($msg['subject']); ?>"
                                    data-message="<?= e($msg['message']); ?>"
                                    data-emailsent="<?= (int)($msg['email_sent'] ?? 0); ?>"
                                    data-emailerror="<?= e($msg['email_error'] ?? ''); ?>"
                                    data-time="<?= e($msg['created_at']); ?>"
                                    data-ip="<?= e($msg['ip_address']); ?>"
                                    title="View Message">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-msg-btn" data-id="<?= $msg['id']; ?>" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3"><?= renderPagination($pagination, '?search=' . urlencode($search) . '&status=' . urlencode($statusFilter) . '&page=%d'); ?></div>
        </div>
    </main>
</div>

<!-- View Message Modal -->
<div class="modal fade" id="viewMsgModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-envelope-open me-2 text-primary"></i>Message Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3" style="font-size: 0.875rem;">
            <div class="col-6"><strong>Name:</strong> <span id="mdName"></span></div>
            <div class="col-6"><strong>Email:</strong> <span id="mdEmail"></span></div>
            <div class="col-6"><strong>Phone:</strong> <span id="mdPhone"></span></div>
            <div class="col-6"><strong>Sent at:</strong> <span id="mdTime"></span></div>
            <div class="col-12"><strong>Subject:</strong> <span id="mdSubject" class="fw-semibold"></span></div>
            <div class="col-12">
                <strong>Admin Email Delivery:</strong> 
                <span id="mdEmailStatus"></span>
                <div id="mdEmailError" class="text-danger small mt-1 d-none"></div>
            </div>
        </div>
        <hr>
        <label class="fw-bold mb-2 small text-muted text-uppercase">Customer Message</label>
        <div id="mdMessage" class="p-3 bg-light rounded border" style="white-space: pre-wrap; font-size: 0.875rem; line-height: 1.6;"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-success btn-sm" id="mdResendBtn">
            <i class="bi bi-send me-1"></i> Send to Admin Email
        </button>
        <div>
            <a href="#" id="mdReplyLink" class="btn btn-primary btn-sm"><i class="bi bi-reply me-1"></i>Reply via Email</a>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentUrl = window.location.pathname + window.location.search;
    const csrfToken = '<?= CSRF::getToken(); ?>';
    let currentMsgId = 0;

    // ---- View message modal ----
    document.querySelectorAll('.view-msg-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentMsgId = btn.dataset.id;
            document.getElementById('mdName').textContent    = btn.dataset.name;
            document.getElementById('mdEmail').textContent   = btn.dataset.email;
            document.getElementById('mdPhone').textContent   = btn.dataset.phone || '—';
            document.getElementById('mdSubject').textContent = btn.dataset.subject;
            document.getElementById('mdMessage').textContent = btn.dataset.message;
            document.getElementById('mdTime').textContent    = btn.dataset.time;
            
            const isSent = btn.dataset.emailsent === '1';
            const err = btn.dataset.emailerror || '';
            const statusEl = document.getElementById('mdEmailStatus');
            const errEl = document.getElementById('mdEmailError');

            if (isSent) {
                statusEl.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Delivered to Admin</span>';
                errEl.classList.add('d-none');
                errEl.textContent = '';
            } else {
                statusEl.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Not Delivered</span>';
                if (err) {
                    errEl.classList.remove('d-none');
                    errEl.textContent = 'Reason: ' + err;
                } else {
                    errEl.classList.add('d-none');
                }
            }

            document.getElementById('mdReplyLink').href = 'mailto:' + btn.dataset.email + '?subject=Re: ' + encodeURIComponent(btn.dataset.subject);
            new bootstrap.Modal(document.getElementById('viewMsgModal')).show();
        });
    });

    // ---- Resend to Admin Email ----
    document.getElementById('mdResendBtn').addEventListener('click', function() {
        if (!currentMsgId) return;
        const origText = this.innerHTML;
        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
        this.disabled = true;

        const fd = new FormData();
        fd.append('action', 'resend_email');
        fd.append('id', currentMsgId);
        fd.append('csrf_token', csrfToken);

        fetch(currentUrl, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) {
                    const statusEl = document.getElementById('mdEmailStatus');
                    statusEl.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Delivered to Admin</span>';
                    document.getElementById('mdEmailError').classList.add('d-none');
                    const deliveryCell = document.getElementById('delivery-cell-' + currentMsgId);
                    if (deliveryCell) {
                        deliveryCell.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Delivered</span>';
                    }
                }
            })
            .catch(() => alert('Network error occurred while sending email.'))
            .finally(() => {
                this.innerHTML = origText;
                this.disabled = false;
            });
    });

    // ---- Status change (inline) ----
    document.querySelectorAll('.status-select').forEach(sel => {
        sel.addEventListener('change', function() {
            const id     = this.dataset.id;
            const status = this.value;
            const fd     = new FormData();
            fd.append('action', 'update_status');
            fd.append('id', id);
            fd.append('status', status);
            fd.append('csrf_token', csrfToken);

            fetch(currentUrl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        const row = document.getElementById('row-' + id);
                        row.classList.toggle('table-warning', status === 'unread');
                    }
                });
        });
    });

    // ---- Delete ----
    document.querySelectorAll('.delete-msg-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this message?')) return;
            const id = this.dataset.id;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', id);
            fd.append('csrf_token', csrfToken);

            fetch(currentUrl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        document.getElementById('row-' + id).remove();
                    }
                });
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
