<?php
/**
 * WAPI SaaS - Data Deletion Instructions
 * Dynamically customized via Super Admin Pages Customizer
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

$pageData = PageManager::getPage('data-deletion');
$extra = $pageData['extra_data'] ?? [];

$pageTitle = !empty($pageData['meta_title']) ? $pageData['meta_title'] : (!empty($pageData['title']) ? $pageData['title'] : 'Data Deletion Instructions');
$pageDescription = $pageData['meta_description'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="bg-white p-5 rounded-4 shadow-sm">
                    <div class="text-center mb-5">
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle p-3 mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-trash3-fill fs-1"></i>
                        </div>
                        <h1 class="fw-bold"><?= e($pageData['title'] ?? 'Data Deletion Instructions'); ?></h1>
                        <?php if (!empty($pageData['subtitle'])): ?>
                        <p class="text-secondary"><?= e($pageData['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="policy-content" style="line-height: 1.8;">
                        <?= $pageData['content'] ?? ''; ?>
                    </div>

                    <?php if (!empty($extra['last_updated'])): ?>
                    <div class="pt-4 border-top mt-5">
                        <p class="text-secondary small mb-0 font-monospace">
                            Last Updated: <?= e($extra['last_updated']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
