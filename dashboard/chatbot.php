<?php
/**
 * WAPI SaaS - Chatbot Flows List
 * Manage and create new chatbot automation flows
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Fetch all flows for this user
$flows = $db->fetchAll("SELECT * FROM chatbot_flows WHERE user_id = ? ORDER BY created_at DESC", [$userId]);

$pageTitle = 'Chatbot Automation';
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header mb-4">
            <div>
                <h1 class="dash-title">Chatbot Automation 🤖</h1>
                <div class="dash-breadcrumb">
                    <span>Automate your WhatsApp conversations with ease</span>
                </div>
            </div>
            <a href="<?= baseUrl('dashboard/chatbot-builder.php'); ?>" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-lg"></i> Create New Flow
            </a>
        </div>

        <div class="row g-4">
            <?php if (empty($flows)): ?>
                <div class="col-12 text-center py-5 bg-white rounded-4 shadow-sm">
                    <div class="mb-4">
                        <i class="bi bi-robot" style="font-size: 4rem; color: #e2e8f0;"></i>
                    </div>
                    <h4 class="fw-bold">No Chatbot Flows Yet</h4>
                    <p class="text-muted mb-4">Create your first automated conversation flow to start saving time.</p>
                    <a href="<?= baseUrl('dashboard/chatbot-builder.php'); ?>" class="btn btn-primary btn-lg px-5">Get Started</a>
                </div>
            <?php else: ?>
                <?php foreach ($flows as $flow): ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden transition-hover">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="node-icon" style="background: var(--primary); color: white; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-diagram-2-fill fs-5"></i>
                                    </div>
                                    <div class="form-check form-switch p-0 m-0">
                                        <input class="form-check-input ms-0 toggle-flow" type="checkbox" data-id="<?= $flow['id']; ?>" <?= $flow['is_active'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-1"><?= e($flow['name']); ?></h5>
                                <p class="text-muted small mb-3"><?= e($flow['description'] ?: 'No description provided'); ?></p>
                                
                                <div class="d-flex flex-wrap gap-1 mb-4">
                                    <?php 
                                    $keywords = json_decode($flow['trigger_keywords'], true) ?: [];
                                    if (empty($keywords)): ?>
                                        <span class="badge bg-light text-muted fw-normal">No triggers</span>
                                    <?php else: ?>
                                        <?php foreach (array_slice($keywords, 0, 3) as $kw): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-medium"><?= e($kw); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($keywords) > 3): ?>
                                            <span class="badge bg-light text-muted">+<?= count($keywords) - 3; ?></span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                                    <div class="text-muted smaller"><i class="bi bi-clock me-1"></i> <?= timeAgo($flow['created_at']); ?></div>
                                    <div class="d-flex gap-2">
                                        <a href="<?= baseUrl('dashboard/chatbot-builder.php?id=' . $flow['id']); ?>" class="btn btn-light btn-sm px-3 shadow-none border">Edit Flow</a>
                                        <button class="btn btn-outline-danger btn-sm px-2 delete-flow" data-id="<?= $flow['id']; ?>"><i class="bi bi-trash3"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
.transition-hover {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.transition-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
}
.smaller { font-size: 0.75rem; }
.bg-primary-subtle { background-color: #e7f3ff; }
.text-primary { color: #0084ff !important; }
.border-primary-subtle { border-color: #cce5ff !important; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
