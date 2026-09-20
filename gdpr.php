<?php
/**
 * WAPI SaaS - GDPR Compliance
 * Dynamically customized via Super Admin Pages Customizer
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

$pageData = PageManager::getPage('gdpr');
$extra = $pageData['extra_data'] ?? [];

$pageTitle = !empty($pageData['meta_title']) ? $pageData['meta_title'] : (!empty($pageData['title']) ? $pageData['title'] : 'GDPR Compliance');
$pageDescription = $pageData['meta_description'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="bg-white p-5 rounded-4 shadow-sm">
                    <h1 class="fw-bold mb-2"><?= e($pageData['title'] ?? 'GDPR Compliance'); ?></h1>
                    <?php if (!empty($extra['last_modified'])): ?>
                    <p class="text-secondary mb-4">Last modified: <?= e($extra['last_modified']); ?></p>
                    <?php endif; ?>
                    
                    <div class="policy-content" style="line-height: 1.8;">
                        <?= $pageData['content'] ?? ''; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
