<?php
/**
 * WAPI SaaS - Admin Templates Management
 * Complete management: View WhatsApp Preview, Edit, Create, Delete, and Sync from Meta API
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireAdmin();

$db = Database::getInstance();
$settings = new Settings();
$wa = new WhatsApp();

$hideNav = true; // Prevents landing page nav from appearing in admin

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validateToken()) {
    $action = $_POST['action'] ?? '';

    // 1. Sync from Meta API
    if ($action === 'sync') {
        $syncUserId = $_POST['sync_user_id'] ?? 'all';
        $totalSynced = 0;
        $errors = [];

        if ($syncUserId === 'all') {
            $accounts = $db->fetchAll("SELECT DISTINCT user_id FROM whatsapp_accounts WHERE waba_id IS NOT NULL AND waba_id != '' AND access_token IS NOT NULL AND access_token != ''");
            if (empty($accounts)) {
                setFlash('warning', 'No connected WhatsApp accounts found with valid WABA ID and Access Token.');
            } else {
                foreach ($accounts as $acc) {
                    $res = $wa->syncTemplates($acc['user_id']);
                    if ($res['success']) {
                        $totalSynced += ($res['count'] ?? 0);
                    } else {
                        $userObj = $db->fetch("SELECT name FROM users WHERE id = ?", [$acc['user_id']]);
                        $uName = $userObj ? $userObj['name'] : "User #{$acc['user_id']}";
                        $errors[] = "$uName: {$res['message']}";
                    }
                }
                if ($totalSynced > 0) {
                    $msg = "Successfully synced $totalSynced templates from Meta across all accounts.";
                    if (!empty($errors)) {
                        $msg .= " (Some accounts had errors: " . implode('; ', $errors) . ")";
                    }
                    setFlash('success', $msg);
                } else {
                    setFlash('danger', 'Failed to sync templates: ' . (implode('; ', $errors) ?: 'Unknown error.'));
                }
            }
        } else {
            $targetId = sanitizeInt($syncUserId);
            $res = $wa->syncTemplates($targetId);
            if ($res['success']) {
                setFlash('success', $res['message']);
            } else {
                setFlash('danger', $res['message']);
            }
        }
        redirect('admin/templates.php');
    }

    // 2. Save / Edit Template
    elseif ($action === 'save') {
        $templateId = sanitizeInt($_POST['template_id'] ?? 0);
        $targetUserId = sanitizeInt($_POST['user_id'] ?? 0);

        if ($targetUserId <= 0) {
            setFlash('danger', 'Please select a valid user for this template.');
            redirect('admin/templates.php');
        }

        $buttonsRaw = trim($_POST['buttons'] ?? '');
        $buttonsVal = null;
        if (!empty($buttonsRaw)) {
            $decoded = json_decode($buttonsRaw, true);
            if (is_array($decoded)) {
                $buttonsVal = $buttonsRaw;
            } else {
                $buttonsVal = json_encode(array_values(array_filter(array_map('trim', explode(',', $buttonsRaw)))));
            }
        }

        $data = [
            'user_id' => $targetUserId,
            'name' => sanitize($_POST['name'] ?? ''),
            'category' => sanitize(strtolower($_POST['category'] ?? 'utility')),
            'language' => sanitize($_POST['language'] ?? 'en'),
            'header_type' => sanitize(strtolower($_POST['header_type'] ?? 'none')),
            'header_content' => sanitize($_POST['header_content'] ?? ''),
            'body' => sanitize($_POST['body_content'] ?? ''),
            'variables' => trim($_POST['variables'] ?? '') === '' ? null : sanitize($_POST['variables']),
            'buttons' => $buttonsVal,
            'footer' => sanitize($_POST['footer_content'] ?? ''),
            'status' => sanitize(strtolower($_POST['status'] ?? 'pending'))
        ];

        if ($templateId > 0) {
            $db->update('templates', $data, 'id = ?', [$templateId]);
            setFlash('success', 'Template updated successfully.');
        } else {
            $db->insert('templates', $data);
            setFlash('success', 'Template created successfully.');
        }
        redirect('admin/templates.php');
    }

    // 3. Quick Status Update
    elseif ($action === 'update_status') {
        $templateId = sanitizeInt($_POST['template_id'] ?? 0);
        $newStatus = sanitize(strtolower($_POST['status'] ?? ''));
        if ($templateId > 0 && in_array($newStatus, ['approved', 'pending', 'rejected'])) {
            $db->update('templates', ['status' => $newStatus], 'id = ?', [$templateId]);
            setFlash('success', "Template status changed to " . ucfirst($newStatus) . ".");
        }
        redirect('admin/templates.php');
    }

    // 4. Delete Template
    elseif ($action === 'delete') {
        $templateId = sanitizeInt($_POST['template_id'] ?? 0);
        if ($templateId > 0) {
            $db->delete('templates', 'id = ?', [$templateId]);
            setFlash('success', 'Template deleted successfully.');
        }
        redirect('admin/templates.php');
    }
}

// Fetch all users for assignment dropdown
$allUsers = $db->fetchAll("SELECT id, name, email FROM users ORDER BY name ASC");

// Fetch users with connected WhatsApp accounts (for Meta sync)
$connectedUsers = $db->fetchAll("SELECT DISTINCT u.id, u.name, u.email, w.phone_number, w.business_name, w.waba_id 
    FROM users u 
    JOIN whatsapp_accounts w ON u.id = w.user_id 
    WHERE w.waba_id IS NOT NULL AND w.waba_id != '' AND w.access_token IS NOT NULL AND w.access_token != ''
    ORDER BY u.name ASC");

// Filters & Search
$search = trim($_GET['search'] ?? '');
$filterUser = sanitizeInt($_GET['user_id'] ?? 0);
$filterCategory = sanitize($_GET['category'] ?? '');
$filterStatus = sanitize($_GET['status'] ?? '');

$whereClauses = ['1=1'];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(t.name LIKE ? OR t.body LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filterUser > 0) {
    $whereClauses[] = "t.user_id = ?";
    $params[] = $filterUser;
}

if (!empty($filterCategory)) {
    $whereClauses[] = "t.category = ?";
    $params[] = $filterCategory;
}

if (!empty($filterStatus)) {
    $whereClauses[] = "t.status = ?";
    $params[] = $filterStatus;
}

$whereSql = implode(' AND ', $whereClauses);

$templates = $db->fetchAll(
    "SELECT t.*, u.name as user_name, u.email as user_email 
     FROM templates t 
     JOIN users u ON t.user_id = u.id 
     WHERE $whereSql 
     ORDER BY t.created_at DESC", 
    $params
);

// Statistics
$totalCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM templates") ?: 0;
$approvedCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM templates WHERE status = 'approved'") ?: 0;
$pendingCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM templates WHERE status = 'pending'") ?: 0;
$rejectedCount = (int)$db->fetchColumn("SELECT COUNT(*) FROM templates WHERE status = 'rejected'") ?: 0;

$pageTitle = 'Message Templates';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<style>
/* WhatsApp Live Preview Styles */
.wa-preview-screen {
    background: #e5ddd5 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" opacity="0.06"><text x="10" y="30" font-size="20">💬</text></svg>') repeat;
    border-radius: 16px;
    padding: 24px 16px;
    min-height: 380px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.08);
}
.wa-message-bubble {
    background: #ffffff;
    border-radius: 8px 18px 18px 18px;
    padding: 12px 14px 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    max-width: 90%;
    margin-bottom: 12px;
    position: relative;
    word-break: break-word;
}
.wa-bubble-header {
    font-weight: 700;
    font-size: 0.95rem;
    color: #111b21;
    margin-bottom: 6px;
}
.wa-bubble-media {
    background: #f0f2f5;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
    color: #667781;
    margin-bottom: 8px;
    font-size: 0.85rem;
}
.wa-bubble-body {
    font-size: 0.9rem;
    line-height: 1.45;
    color: #111b21;
    white-space: pre-wrap;
}
.wa-bubble-body .var-highlight {
    background: #e7f8e8;
    color: #0f5132;
    padding: 1px 4px;
    border-radius: 4px;
    font-weight: 600;
}
.wa-bubble-footer {
    font-size: 0.75rem;
    color: #667781;
    margin-top: 6px;
}
.wa-bubble-time {
    font-size: 0.6875rem;
    color: #667781;
    text-align: right;
    margin-top: 2px;
}
.wa-bubble-buttons {
    margin-top: 8px;
    border-top: 1px solid #f0f2f5;
    padding-top: 6px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.wa-bubble-btn {
    background: #ffffff;
    border: 1px solid #e9edef;
    color: #00a884;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.stat-pill {
    padding: 8px 16px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--card-bg, #fff);
    border: 1px solid var(--border-color, #e2e8f0);
}
</style>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="main-content">
        <!-- Page Header -->
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Message Templates</h1>
                <div class="dash-breadcrumb">
                    <a href="<?= baseUrl('admin/'); ?>">Admin</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Templates</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
                <a href="<?= baseUrl('dashboard/templates.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Go to User Dashboard Templates">
                    <i class="bi bi-box-arrow-up-right me-1"></i> User Dashboard
                </a>
                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#syncModal">
                    <i class="bi bi-arrow-repeat me-1"></i> Sync from Meta
                </button>
                <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-1"></i> Create Template
                </button>
            </div>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-pill">
                    <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-file-earmark-code fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold"><?= number_format($totalCount); ?></div>
                        <div class="text-muted small">Total Templates</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-pill">
                    <div class="rounded-circle p-2 bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold"><?= number_format($approvedCount); ?></div>
                        <div class="text-muted small">Approved</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-pill">
                    <div class="rounded-circle p-2 bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-clock-history fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold"><?= number_format($pendingCount); ?></div>
                        <div class="text-muted small">Pending</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-pill">
                    <div class="rounded-circle p-2 bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-x-circle fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold"><?= number_format($rejectedCount); ?></div>
                        <div class="text-muted small">Rejected</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card mb-4" style="border-radius: var(--border-radius); border: 1px solid var(--border-color);">
            <div class="card-body p-3">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3 col-sm-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search template name or body..." value="<?= e($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">All Users</option>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id']; ?>" <?= $filterUser === (int)$u['id'] ? 'selected' : ''; ?>>
                                    <?= e($u['name']); ?> (<?= e($u['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <select name="category" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            <option value="marketing" <?= $filterCategory === 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="utility" <?= $filterCategory === 'utility' ? 'selected' : ''; ?>>Utility</option>
                            <option value="authentication" <?= $filterCategory === 'authentication' ? 'selected' : ''; ?>>Authentication</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4">
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-sm-4 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                        <?php if (!empty($search) || $filterUser > 0 || !empty($filterCategory) || !empty($filterStatus)): ?>
                            <a href="<?= baseUrl('admin/templates.php'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filters"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="data-table">
            <div class="data-table-header d-flex justify-content-between align-items-center">
                <h5 class="data-table-title mb-0">Templates List (<?= count($templates); ?>)</h5>
                <span class="text-muted small">Manage, preview, edit, or sync from WhatsApp Cloud API</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>User</th>
                            <th>Category</th>
                            <th>Language</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($templates)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-file-earmark-code fs-1 d-block mb-2 text-muted"></i>
                                <h6>No templates found matching your criteria</h6>
                                <p class="small text-muted mb-3">Sync approved templates from Meta or create a new template.</p>
                                <button class="btn btn-outline-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#syncModal">
                                    <i class="bi bi-arrow-repeat"></i> Sync from Meta
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                                    <i class="bi bi-plus-lg"></i> Create Template
                                </button>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($templates as $t): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($t['name']); ?></div>
                                <div class="text-muted small text-truncate" style="max-width: 250px;">
                                    <?= e(mb_substr($t['body'] ?? '', 0, 70)); ?><?= mb_strlen($t['body'] ?? '') > 70 ? '...' : ''; ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold" style="font-size: 0.875rem;"><?= e($t['user_name']); ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= e($t['user_email']); ?></div>
                            </td>
                            <td>
                                <span class="badge-custom" style="background: var(--primary-bg); color: var(--primary); text-transform: capitalize;">
                                    <?= ucfirst(e($t['category'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= strtoupper(e($t['language'])); ?></span>
                            </td>
                            <td>
                                <div class="dropdown d-inline-block">
                                    <button class="status-badge status-<?= $t['status'] === 'approved' ? 'active' : ($t['status'] === 'rejected' ? 'failed' : 'pending'); ?> dropdown-toggle border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Click to change status">
                                        <?= ucfirst(e($t['status'])); ?>
                                    </button>
                                    <ul class="dropdown-menu shadow-sm">
                                        <li><h6 class="dropdown-header">Change Status</h6></li>
                                        <li>
                                            <form method="POST" style="margin:0;">
                                                <?= CSRF::tokenField(); ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="template_id" value="<?= $t['id']; ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="dropdown-item text-success"><i class="bi bi-check-circle me-2"></i> Mark Approved</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" style="margin:0;">
                                                <?= CSRF::tokenField(); ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="template_id" value="<?= $t['id']; ?>">
                                                <input type="hidden" name="status" value="pending">
                                                <button type="submit" class="dropdown-item text-warning"><i class="bi bi-clock-history me-2"></i> Mark Pending</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" style="margin:0;">
                                                <?= CSRF::tokenField(); ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="template_id" value="<?= $t['id']; ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i> Mark Rejected</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td style="font-size: 0.8125rem; color: var(--text-muted); white-space: nowrap;">
                                <?= formatDate($t['created_at']); ?>
                            </td>
                            <td class="text-end" style="white-space: nowrap;">
                                <!-- View / Preview -->
                                <button type="button" class="btn btn-sm btn-outline-info me-1" title="WhatsApp Preview" onclick="openPreviewModal(<?= htmlspecialchars(json_encode($t)); ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <!-- Edit -->
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" title="Edit Template" onclick="openEditModal(<?= htmlspecialchars(json_encode($t)); ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <!-- Delete -->
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete template \'<?= addslashes(e($t['name'])); ?>\'?');">
                                    <?= CSRF::tokenField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="template_id" value="<?= $t['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Template">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ========================================== -->
<!-- 1. Sync from Meta Modal                   -->
<!-- ========================================== -->
<div class="modal fade" id="syncModal" tabindex="-1" aria-labelledby="syncModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?= CSRF::tokenField(); ?>
                <input type="hidden" name="action" value="sync">
                <div class="modal-header">
                    <h5 class="modal-title" id="syncModalLabel"><i class="bi bi-arrow-repeat text-success me-2"></i>Sync Templates from Meta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        This tool connects to Meta Graph API using the connected WhatsApp Business Account (WABA ID & Access Token) and syncs all approved, pending, and rejected templates directly into the system.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Account to Sync</label>
                        <select name="sync_user_id" class="form-select" required>
                            <option value="all">⚡ Sync for All Connected Users (<?= count($connectedUsers); ?> Accounts)</option>
                            <?php if (!empty($connectedUsers)): ?>
                                <optgroup label="Individual Connected Users">
                                    <?php foreach ($connectedUsers as $cu): ?>
                                        <option value="<?= $cu['id']; ?>">
                                            <?= e($cu['name']); ?> (<?= e($cu['business_name'] ?: $cu['phone_number'] ?: 'WABA: ' . substr($cu['waba_id'], 0, 8) . '...'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>

                    <?php if (empty($connectedUsers)): ?>
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> No users currently have a connected WhatsApp account with valid WABA ID and Access Token. Setup your WhatsApp credentials first in WhatsApp Setup.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle-fill me-1"></i> Existing templates with matching names and languages will be updated with latest Meta status and components. New templates will be added automatically.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" <?= empty($connectedUsers) ? 'disabled' : ''; ?>>
                        <i class="bi bi-arrow-repeat me-1"></i> Start Sync
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. Create / Edit Template Modal           -->
<!-- ========================================== -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="tplModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <?= CSRF::tokenField(); ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="template_id" id="tplId" value="0">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="tplModalTitle">Create Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Assign to User *</label>
                            <select name="user_id" id="tplUserId" class="form-select" required>
                                <option value="">-- Select User --</option>
                                <?php foreach ($allUsers as $u): ?>
                                    <option value="<?= $u['id']; ?>"><?= e($u['name']); ?> (<?= e($u['email']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Template Name *</label>
                            <input type="text" name="name" id="tplName" class="form-control" required placeholder="e.g. order_confirmation_2026" pattern="[a-z0-9_]+" title="Only lowercase letters, numbers, and underscores allowed">
                            <small class="text-muted">Lowercase letters, numbers, and underscores only</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" id="tplCategory" class="form-select">
                                <option value="marketing">Marketing</option>
                                <option value="utility">Utility</option>
                                <option value="authentication">Authentication</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Language</label>
                            <select name="language" id="tplLang" class="form-select">
                                <option value="en">English (en)</option>
                                <option value="en_US">English US (en_US)</option>
                                <option value="hi">Hindi (hi)</option>
                                <option value="es">Spanish (es)</option>
                                <option value="ar">Arabic (ar)</option>
                                <option value="pt_BR">Portuguese BR (pt_BR)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" id="tplStatus" class="form-select">
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Header Type (Optional)</label>
                            <select name="header_type" id="tplHeaderType" class="form-select" onchange="toggleHeaderContent()">
                                <option value="none">None</option>
                                <option value="text">Text</option>
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                                <option value="document">Document</option>
                            </select>
                        </div>
                        <div class="col-md-8" id="headerContentGroup" style="display:none;">
                            <label class="form-label fw-bold">Header Text / Content</label>
                            <input type="text" name="header_content" id="tplHeader" class="form-control" placeholder="Header text or description">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Body Content *</label>
                            <textarea name="body_content" id="tplBody" class="form-control" rows="5" required placeholder="Hello {{1}}, thank you for your order {{2}}! Your package will be delivered soon."></textarea>
                            <div class="d-flex justify-content-between text-muted small mt-1">
                                <span>Use <code>{{1}}</code>, <code>{{2}}</code>, etc. for dynamic customer variables</span>
                                <span id="bodyCharCount">0 chars</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Footer (Optional)</label>
                            <input type="text" name="footer_content" id="tplFooter" class="form-control" placeholder="e.g. Reply STOP to unsubscribe">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Variables Description (Optional)</label>
                            <input type="text" name="variables" id="tplVariables" class="form-control" placeholder="e.g. customer_name, order_id (comma separated)">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Interactive Buttons (Optional)</label>
                            <input type="text" name="buttons" id="tplButtons" class="form-control" placeholder="e.g. Track Order, Contact Support (comma separated)">
                            <small class="text-muted">Separate multiple buttons with commas or paste JSON format</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. WhatsApp Live Preview Modal            -->
<!-- ========================================== -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header py-3" style="background: #008069; color: white;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-whatsapp fs-5"></i>
                    <div>
                        <h6 class="modal-title mb-0 text-white" id="previewModalTitle">WhatsApp Message Preview</h6>
                        <small class="text-white-50" id="previewUserSubtitle">Assigned User</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 bg-light">
                <div class="wa-preview-screen">
                    <div class="wa-message-bubble">
                        <!-- Media Header -->
                        <div id="waPreviewMediaHeader" style="display:none;" class="wa-bubble-media">
                            <i class="bi bi-card-image fs-3 d-block mb-1"></i>
                            <span id="waPreviewMediaText">Media Header</span>
                        </div>
                        <!-- Text Header -->
                        <div id="waPreviewHeader" class="wa-bubble-header" style="display:none;"></div>
                        <!-- Body -->
                        <div id="waPreviewBody" class="wa-bubble-body"></div>
                        <!-- Footer -->
                        <div id="waPreviewFooter" class="wa-bubble-footer" style="display:none;"></div>
                        <!-- Time / Ticks -->
                        <div class="wa-bubble-time">
                            <span><?= date('h:i A'); ?></span>
                            <i class="bi bi-check2-all text-primary ms-1"></i>
                        </div>
                        <!-- Buttons -->
                        <div id="waPreviewButtons" class="wa-bubble-buttons" style="display:none;"></div>
                    </div>
                </div>

                <!-- Template Details Card -->
                <div class="card mt-3 border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-2 text-center" style="font-size: 0.8125rem;">
                            <div class="col-4 border-end">
                                <div class="text-muted">Category</div>
                                <div class="fw-bold" id="previewCategory">-</div>
                            </div>
                            <div class="col-4 border-end">
                                <div class="text-muted">Language</div>
                                <div class="fw-bold" id="previewLanguage">-</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted">Status</div>
                                <div id="previewStatus">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Open Create Modal
function openCreateModal() {
    document.getElementById('tplId').value = 0;
    document.getElementById('tplModalTitle').textContent = 'Create Template';
    document.getElementById('tplUserId').value = '';
    document.getElementById('tplName').value = '';
    document.getElementById('tplCategory').value = 'marketing';
    document.getElementById('tplLang').value = 'en';
    document.getElementById('tplStatus').value = 'approved';
    document.getElementById('tplHeaderType').value = 'none';
    document.getElementById('tplHeader').value = '';
    document.getElementById('tplBody').value = '';
    document.getElementById('tplVariables').value = '';
    document.getElementById('tplButtons').value = '';
    document.getElementById('tplFooter').value = '';
    toggleHeaderContent();
    new bootstrap.Modal(document.getElementById('templateModal')).show();
}

// Open Edit Modal
function openEditModal(t) {
    document.getElementById('tplId').value = t.id;
    document.getElementById('tplModalTitle').textContent = 'Edit Template: ' + t.name;
    document.getElementById('tplUserId').value = t.user_id || '';
    document.getElementById('tplName').value = t.name || '';
    document.getElementById('tplCategory').value = t.category || 'marketing';
    document.getElementById('tplLang').value = t.language || 'en';
    document.getElementById('tplStatus').value = t.status || 'pending';
    document.getElementById('tplHeaderType').value = t.header_type || 'none';
    document.getElementById('tplHeader').value = t.header_content || '';
    document.getElementById('tplBody').value = t.body || '';
    
    // Variables
    let varStr = '';
    if (t.variables) {
        try {
            let parsed = JSON.parse(t.variables);
            varStr = Array.isArray(parsed) ? parsed.join(', ') : t.variables;
        } catch(e) {
            varStr = t.variables.replace(/[\[\]"]/g, '');
        }
    }
    document.getElementById('tplVariables').value = varStr;

    // Buttons
    let btnStr = '';
    if (t.buttons) {
        try {
            let parsed = JSON.parse(t.buttons);
            if (Array.isArray(parsed)) {
                if (parsed.length > 0 && typeof parsed[0] === 'object') {
                    btnStr = parsed.map(b => b.text || b.url || JSON.stringify(b)).join(', ');
                } else {
                    btnStr = parsed.join(', ');
                }
            } else {
                btnStr = t.buttons;
            }
        } catch(e) {
            btnStr = t.buttons;
        }
    }
    document.getElementById('tplButtons').value = btnStr;
    document.getElementById('tplFooter').value = t.footer || '';

    toggleHeaderContent();
    updateBodyCharCount();
    new bootstrap.Modal(document.getElementById('templateModal')).show();
}

// Toggle header text box based on header type
function toggleHeaderContent() {
    const type = document.getElementById('tplHeaderType').value;
    const group = document.getElementById('headerContentGroup');
    if (type !== 'none') {
        group.style.display = 'block';
    } else {
        group.style.display = 'none';
    }
}

// Body character count listener
document.getElementById('tplBody').addEventListener('input', updateBodyCharCount);
function updateBodyCharCount() {
    const val = document.getElementById('tplBody').value || '';
    document.getElementById('bodyCharCount').textContent = val.length + ' chars';
}

// Open Preview Modal with authentic WhatsApp layout
function openPreviewModal(t) {
    document.getElementById('previewModalTitle').textContent = t.name;
    document.getElementById('previewUserSubtitle').textContent = 'User: ' + (t.user_name || 'N/A');
    document.getElementById('previewCategory').textContent = (t.category || '').toUpperCase();
    document.getElementById('previewLanguage').textContent = (t.language || '').toUpperCase();

    // Status badge
    const statusEl = document.getElementById('previewStatus');
    const badgeClass = t.status === 'approved' ? 'bg-success' : (t.status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
    statusEl.innerHTML = `<span class="badge ${badgeClass}">${(t.status || 'Pending').toUpperCase()}</span>`;

    // Header Handling
    const textHeader = document.getElementById('waPreviewHeader');
    const mediaHeader = document.getElementById('waPreviewMediaHeader');
    const mediaText = document.getElementById('waPreviewMediaText');

    textHeader.style.display = 'none';
    mediaHeader.style.display = 'none';

    if (t.header_type === 'text' && t.header_content) {
        textHeader.textContent = t.header_content;
        textHeader.style.display = 'block';
    } else if (['image', 'video', 'document'].includes(t.header_type)) {
        mediaText.textContent = (t.header_type.toUpperCase()) + (t.header_content ? ': ' + t.header_content : ' HEADER');
        mediaHeader.style.display = 'block';
    }

    // Body with highlighted {{1}}, {{2}} variables
    let bodyText = t.body || '';
    let formattedBody = bodyText.replace(/\{\{(\d+)\}\}/g, '<span class="var-highlight">{{$1}}</span>');
    document.getElementById('waPreviewBody').innerHTML = formattedBody;

    // Footer
    const footerEl = document.getElementById('waPreviewFooter');
    if (t.footer && t.footer.trim() !== '') {
        footerEl.textContent = t.footer;
        footerEl.style.display = 'block';
    } else {
        footerEl.style.display = 'none';
    }

    // Buttons
    const btnContainer = document.getElementById('waPreviewButtons');
    btnContainer.innerHTML = '';
    btnContainer.style.display = 'none';

    if (t.buttons) {
        let btnList = [];
        try {
            let parsed = JSON.parse(t.buttons);
            if (Array.isArray(parsed)) {
                btnList = parsed;
            } else if (typeof parsed === 'string') {
                btnList = parsed.split(',');
            }
        } catch(e) {
            btnList = (t.buttons || '').split(',');
        }

        if (btnList.length > 0) {
            btnContainer.style.display = 'flex';
            btnList.forEach(btn => {
                const btnTitle = typeof btn === 'object' ? (btn.text || btn.url || 'Button') : btn.trim();
                if (btnTitle) {
                    const btnDiv = document.createElement('div');
                    btnDiv.className = 'wa-bubble-btn';
                    btnDiv.innerHTML = `<i class="bi bi-box-arrow-up-right me-1" style="font-size: 0.75rem;"></i> ${btnTitle}`;
                    btnContainer.appendChild(btnDiv);
                }
            });
        }
    }

    new bootstrap.Modal(document.getElementById('previewModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
