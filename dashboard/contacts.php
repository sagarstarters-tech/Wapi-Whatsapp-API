<?php
/**
 * WAPI SaaS - Contacts Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$settings = new Settings();
$userId = $_SESSION['user_id'];

$hideNav = true; // Prevents landing page nav from appearing in dashboard

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validateToken()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $data = [
            'user_id' => $userId,
            'name' => sanitize($_POST['name']),
            'phone' => sanitize($_POST['phone']),
            'email' => sanitizeEmail($_POST['email'] ?? ''),
            'company' => sanitize($_POST['company'] ?? ''),
            'tags' => sanitize($_POST['tags'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? '')
        ];

        if ($action === 'edit' && !empty($_POST['contact_id'])) {
            $db->update('contacts', $data, 'id = ? AND user_id = ?', [sanitizeInt($_POST['contact_id']), $userId]);
            setFlash('success', 'Contact updated.');
        } else {
            $db->insert('contacts', $data);
            setFlash('success', 'Contact added.');
        }
    } elseif ($action === 'delete') {
        $db->delete('contacts', 'id = ? AND user_id = ?', [sanitizeInt($_POST['contact_id']), $userId]);
        setFlash('success', 'Contact deleted.');
    }
    redirect('dashboard/contacts.php');
}

// Pagination & search
$search = sanitize($_GET['search'] ?? '');
$page = max(1, sanitizeInt($_GET['page'] ?? 1));
$where = 'user_id = ?';
$params = [$userId];

if ($search) {
    $where .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
}

$totalContacts = $db->count('contacts', $where, $params);
$pagination = paginate($totalContacts, $page);
$contacts = $db->fetchAll("SELECT * FROM contacts WHERE {$where} ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}", $params);

$pageTitle = 'Contacts';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Contacts</h1>
                <div class="dash-breadcrumb"><a href="<?= baseUrl('dashboard/'); ?>">Dashboard</a><i class="bi bi-chevron-right"></i><span>Contacts</span></div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#contactModal" onclick="resetContactForm()"><i class="bi bi-plus-lg"></i> Add Contact</button>
            </div>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?> fade-in"><i class="bi bi-check-circle-fill"></i> <?= e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="data-table">
            <div class="data-table-header">
                <h5 class="data-table-title mb-0">All Contacts (<?= $totalContacts; ?>)</h5>
                <form method="GET" class="d-flex gap-2">
                    <div class="search-box"><i class="bi bi-search"></i><input type="text" name="search" class="form-control" placeholder="Search contacts..." value="<?= e($search); ?>"></div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Tags</th><th>Added</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-people" style="font-size: 2rem;"></i><br>No contacts yet</td></tr>
                        <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><div class="user-info"><div class="user-avatar"><?= strtoupper(substr($contact['name'], 0, 1)); ?></div><div class="fw-bold"><?= e($contact['name']); ?></div></div></td>
                            <td><?= e($contact['phone']); ?></td>
                            <td style="font-size: 0.875rem;"><?= e($contact['email'] ?: '-'); ?></td>
                            <td><?php if ($contact['tags']): foreach (explode(',', $contact['tags']) as $tag): ?>
                                <span class="badge-custom" style="background: var(--primary-bg); color: var(--primary); margin: 1px;"><?= e(trim($tag)); ?></span>
                            <?php endforeach; endif; ?></td>
                            <td style="font-size: 0.8125rem; color: var(--text-muted);"><?= timeAgo($contact['created_at']); ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= baseUrl('dashboard/messages.php?to=' . urlencode($contact['phone'])); ?>" class="btn btn-icon btn-sm" style="background: rgba(37,211,102,0.1); color: var(--whatsapp); border: 1px solid rgba(37,211,102,0.2);" title="Send Message"><i class="bi bi-send"></i></a>
                                    <button class="btn btn-icon btn-sm" style="background: var(--bg-secondary); border: 1px solid var(--border-color);" onclick="editContact(<?= htmlspecialchars(json_encode($contact)); ?>)" title="Edit"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this contact?')">
                                        <?= CSRF::tokenField(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="contact_id" value="<?= $contact['id']; ?>">
                                        <button class="btn btn-icon btn-sm" style="background: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.2);" title="Delete"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-3"><?= renderPagination($pagination, '?search=' . urlencode($search) . '&page=%d'); ?></div>
        </div>
    </main>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="contactForm">
                <?= CSRF::tokenField(); ?>
                <input type="hidden" name="action" id="contactAction" value="add">
                <input type="hidden" name="contact_id" id="contactId" value="">
                <div class="modal-header"><h5 class="modal-title" id="contactModalTitle">Add Contact</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Name *</label><input type="text" name="name" id="contactName" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Phone *</label><input type="text" name="phone" id="contactPhone" class="form-control" placeholder="+919876543210" required></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" id="contactEmail" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Company</label><input type="text" name="company" id="contactCompany" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Tags</label><input type="text" name="tags" id="contactTags" class="form-control" placeholder="vip, customer, lead"></div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" id="contactNotes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function resetContactForm() {
    document.getElementById('contactAction').value = 'add';
    document.getElementById('contactId').value = '';
    document.getElementById('contactModalTitle').textContent = 'Add Contact';
    document.getElementById('contactForm').reset();
}

function editContact(c) {
    document.getElementById('contactAction').value = 'edit';
    document.getElementById('contactId').value = c.id;
    document.getElementById('contactModalTitle').textContent = 'Edit Contact';
    document.getElementById('contactName').value = c.name;
    document.getElementById('contactPhone').value = c.phone;
    document.getElementById('contactEmail').value = c.email || '';
    document.getElementById('contactCompany').value = c.company || '';
    document.getElementById('contactTags').value = c.tags || '';
    document.getElementById('contactNotes').value = c.notes || '';
    new bootstrap.Modal(document.getElementById('contactModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
