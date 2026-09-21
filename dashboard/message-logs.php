<?php
/**
 * WAPI SaaS - Message Logs
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireActivePlan();

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
                    <thead><tr><th>Direction</th><th>To / From</th><th>Type</th><th>Content</th><th>Status</th><th>Time</th><th>Action</th></tr></thead>
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
                            <td style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.875rem;">
                                <?php
                                    $rowOtp = ($msg['direction'] === 'inbound') ? extractOtpFromMessage($msg['content'], $msg['error_message'] ?? '') : null;
                                    if ($rowOtp): ?>
                                    <span class="badge rounded-pill bg-success px-2 py-1 me-1 shadow-sm"><i class="bi bi-shield-lock-fill"></i> OTP: <?= e($rowOtp); ?></span>
                                <?php endif; ?>
                                <?php if ($msg['status'] === 'failed' && !empty($msg['error_message']) && strpos($msg['error_message'], '131047') !== false): ?>
                                    <span class="badge rounded-pill bg-danger-subtle text-danger px-2 py-1 me-1 border border-danger-subtle" title="Customer has not replied in 24 hours. Meta requires an approved WhatsApp Template message."><i class="bi bi-clock-history me-1"></i>24h Window Expired</span>
                                <?php endif; ?>
                                <?= e(substr($msg['content'], 0, 60)); ?><?= strlen($msg['content']) > 60 ? '...' : ''; ?>
                            </td>
                            <td><span class="status-badge status-<?= $msg['status']; ?>"><?= ucfirst($msg['status']); ?></span></td>
                            <td style="font-size: 0.8125rem; color: var(--text-muted); white-space: nowrap;"><?= timeAgo($msg['created_at']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-light-primary" onclick="viewMessage(<?= htmlspecialchars(json_encode([
                                    'id'          => $msg['id'],
                                    'direction'   => $msg['direction'],
                                    'to'          => $msg['to_number'],
                                    'name'        => $msg['contact_name'] ?? $msg['to_number'],
                                    'type'        => ucfirst($msg['type']),
                                    'status'      => ucfirst($msg['status']),
                                    'time'        => date('d M Y, H:i:s', strtotime($msg['created_at'])),
                                    'content'     => $msg['content'],
                                    'error'       => $msg['error_message'] ?? '',
                                    'media_url'   => $msg['media_url'] ?? '',
                                    'row_otp'     => $rowOtp
                                ])); ?>)" title="View Details">
                                    <i class="bi bi-eye"></i>
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

<!-- Message Detail Modal -->
<div class="modal fade" id="messageDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color: var(--primary);"><i class="bi bi-chat-text-fill me-2"></i>Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Highlighted OTP Banner -->
                <div id="modalOtpSection" class="p-3 rounded-4 mb-4 shadow-sm" style="display:none; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; font-weight: 700;">
                                <i class="bi bi-shield-lock-fill me-1"></i> Verification Code / OTP
                            </div>
                            <div id="modalOtpCode" class="fw-bold my-1" style="font-size: 2.2rem; letter-spacing: 3px; font-family: monospace;"></div>
                            <div style="font-size: 0.75rem; opacity: 0.85;">Received from Facebook, Meta or Authentication Service</div>
                        </div>
                        <button type="button" class="btn btn-light fw-bold px-3 py-2 rounded-pill shadow-sm" onclick="copyModalOtp()">
                            <i class="bi bi-clipboard-check me-1"></i> Copy Code
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-4 bg-light">
                    <div id="modalIcon" class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.25rem;"></div>
                    <div>
                        <div id="modalTarget" class="fw-bold mb-0"></div>
                        <div id="modalTime" class="text-muted small"></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted small fw-bold text-uppercase mb-2 d-block" style="letter-spacing: 0.5px;">Message Content</label>
                    <div id="modalContent" class="p-3 rounded-3 bg-white border" style="font-size: 0.95rem; white-space: pre-wrap; line-height: 1.6;"></div>
                </div>

                <div class="mb-3" id="modalMediaSection" style="display:none;">
                    <label class="text-muted small fw-bold text-uppercase mb-2 d-block" style="letter-spacing: 0.5px;">Media URL</label>
                    <a id="modalMediaUrl" href="#" target="_blank" class="d-block text-break small p-2 bg-light rounded border" style="word-break: break-all;"></a>
                </div>

                <div class="mb-3" id="modalErrorSection" style="display:none;">
                    <label class="text-muted small fw-bold text-uppercase mb-2 d-block" style="letter-spacing: 0.5px; color: var(--danger);">❌ System / API Error</label>
                    <div id="modalError" class="p-3 rounded-3 border" style="font-size: 0.85rem; white-space: pre-wrap; line-height: 1.6; background: rgba(239,68,68,0.06); border-color: rgba(239,68,68,0.3) !important; color: #b91c1c;"></div>
                </div>

                <div class="row g-3 my-2">
                    <div class="col-6">
                        <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Type</label>
                        <span id="modalType" class="badge-custom" style="background: var(--primary-bg); color: var(--primary);"></span>
                    </div>
                    <div class="col-6">
                        <label class="text-muted small fw-bold text-uppercase mb-1 d-block">Status</label>
                        <span id="modalStatus" class="status-badge"></span>
                    </div>
                </div>

                <!-- Raw Webhook / Technical Payload Accordion -->
                <div class="accordion mt-3" id="modalRawAccordion">
                    <div class="accordion-item border rounded-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2 px-3 bg-light fw-semibold" style="font-size: 0.85rem;" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRawPayload">
                                <i class="bi bi-code-square me-2 text-primary"></i> View Raw Webhook & Technical Payload
                            </button>
                        </h2>
                        <div id="collapseRawPayload" class="accordion-collapse collapse" data-bs-parent="#modalRawAccordion">
                            <div class="accordion-body p-3 bg-dark">
                                <div class="d-flex justify-content-between align-items-center pb-2 border-bottom border-secondary mb-2">
                                    <span class="text-secondary small">Raw Payload Received from Meta</span>
                                    <button type="button" class="btn btn-sm btn-outline-light py-0 px-2" style="font-size: 0.75rem;" onclick="copyModalRawJson()">
                                        <i class="bi bi-clipboard me-1"></i> Copy JSON
                                    </button>
                                </div>
                                <pre id="modalRawPayload" class="m-0 text-success" style="font-family: monospace; font-size: 0.75rem; max-height: 250px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;">Loading payload details...</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light w-100" style="border-radius: 10px;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-light-primary {
        background: rgba(108, 99, 255, 0.1);
        color: var(--primary);
    }
    .btn-light-primary:hover {
        background: var(--primary);
        color: #fff;
    }
</style>

<script>
    let currentModalOtp = '';
    let currentRawJson = '';

    function copyModalOtp() {
        if (!currentModalOtp) return;
        navigator.clipboard.writeText(currentModalOtp).then(() => {
            alert('OTP Copied to clipboard: ' + currentModalOtp);
        });
    }

    function copyModalRawJson() {
        if (!currentRawJson) return;
        navigator.clipboard.writeText(currentRawJson).then(() => {
            alert('Raw JSON copied to clipboard.');
        });
    }

    function viewMessage(data) {
        const modal = new bootstrap.Modal(document.getElementById('messageDetailModal'));
        
        currentModalOtp = data.row_otp || '';
        currentRawJson = '';

        // Reset and populate modal data
        document.getElementById('modalTarget').innerText  = data.name;
        document.getElementById('modalTime').innerText    = data.time;
        document.getElementById('modalContent').innerText = data.content || '-';
        document.getElementById('modalType').innerText    = data.type;
        
        const statusEl = document.getElementById('modalStatus');
        statusEl.innerText  = data.status;
        statusEl.className  = 'status-badge status-' + data.status.toLowerCase();
        
        // Initial OTP check from row
        const otpSection = document.getElementById('modalOtpSection');
        const otpCodeEl  = document.getElementById('modalOtpCode');
        if (currentModalOtp) {
            otpCodeEl.innerText = currentModalOtp;
            otpSection.style.display = 'block';
        } else {
            otpSection.style.display = 'none';
        }

        // Media URL
        const mediaSection = document.getElementById('modalMediaSection');
        const mediaLink    = document.getElementById('modalMediaUrl');
        if (data.media_url) {
            mediaLink.href        = data.media_url;
            mediaLink.innerText   = data.media_url;
            mediaSection.style.display = 'block';
        } else {
            mediaSection.style.display = 'none';
        }

        // Error message
        const errorSection = document.getElementById('modalErrorSection');
        const errorBox     = document.getElementById('modalError');
        if (data.error && (data.status.toLowerCase() === 'failed' || data.type.toLowerCase() === 'unsupported')) {
            const err = String(data.error);
            if (err.includes('131047') || err.toLowerCase().includes('re-engagement') || err.toLowerCase().includes('24 hours')) {
                errorBox.innerHTML = '<div class="fw-bold mb-1" style="font-size: 0.95rem;">⚠️ Meta Policy: 24-Hour Window Expired (Error #131047)</div>' +
                    '<div class="mb-2">Meta WhatsApp policy ke mutabiq aap customer ko 24 ghante ke baad direct <strong>Text message</strong> nahi bhej sakte jab tak customer samne se message na kare.</div>' +
                    '<div class="p-2 bg-white rounded border border-danger-subtle mb-2 text-dark">' +
                    '<strong>💡 Solution:</strong> Bulk Messages bhejte waqt Type me <strong>"Template"</strong> select karein aur Meta dwara Approved Template use karein. Template message 24-hour window ke baad bhi deliver ho jate hain.' +
                    '</div>' +
                    '<div class="text-muted small" style="font-size: 0.72rem; word-break: break-all;">Meta Raw Error: ' + err.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</div>';
            } else {
                errorBox.innerText = data.error;
            }
            errorSection.style.display = 'block';
        } else {
            errorSection.style.display = 'none';
        }
        
        const iconEl = document.getElementById('modalIcon');
        if (data.direction === 'inbound') {
            iconEl.innerHTML = '<i class="bi bi-arrow-down-left"></i>';
            iconEl.style.background = 'rgba(16, 185, 129, 0.1)';
            iconEl.style.color = 'var(--success)';
        } else {
            iconEl.innerHTML = '<i class="bi bi-arrow-up-right"></i>';
            iconEl.style.background = 'rgba(59, 130, 246, 0.1)';
            iconEl.style.color = 'var(--info)';
        }

        // Raw payload
        const rawPre = document.getElementById('modalRawPayload');
        rawPre.innerText = 'Fetching technical payload...';

        modal.show();

        // Async fetch full technical details and extract OTP
        fetch('<?= baseUrl('api/message-details.php?id='); ?>' + data.id)
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (res.content && res.content !== data.content) {
                        document.getElementById('modalContent').innerText = res.content;
                    }
                    if (res.detected_otp && data.direction === 'inbound') {
                        currentModalOtp = res.detected_otp;
                        otpCodeEl.innerText = res.detected_otp;
                        otpSection.style.display = 'block';
                    } else {
                        otpSection.style.display = 'none';
                    }
                    if (res.raw_payload) {
                        currentRawJson = JSON.stringify(res.raw_payload, null, 2);
                        rawPre.innerText = currentRawJson;
                    } else if (res.error_message) {
                        currentRawJson = res.error_message;
                        rawPre.innerText = currentRawJson;
                    } else {
                        rawPre.innerText = 'No extended raw payload stored for this message.';
                    }
                } else {
                    rawPre.innerText = data.error || 'No raw payload available.';
                }
            })
            .catch(() => {
                rawPre.innerText = data.error || 'Unable to load extended payload.';
            });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
