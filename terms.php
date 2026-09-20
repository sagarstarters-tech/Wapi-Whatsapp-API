<?php
/**
 * WAPI SaaS - Terms of Service
 * Dynamically customized via Super Admin Pages Customizer
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

$pageData = PageManager::getPage('terms');
$extra = $pageData['extra_data'] ?? [];

$pageTitle = !empty($pageData['meta_title']) ? $pageData['meta_title'] : (!empty($pageData['title']) ? $pageData['title'] : 'Terms of Service');
$metaDescription = $pageData['meta_description'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="bg-white p-5 rounded-4 shadow-sm">
                    <h1 class="fw-bold mb-2 text-center"><?= e($pageData['title']); ?></h1>
                    <p class="text-secondary text-center mb-5">Last modified: <?= e($extra['last_modified'] ?? 'October 2026'); ?></p>
                    
                    <?php if (!empty($extra['intro_notice'])): ?>
                    <div class="card bg-light border-0 mb-5 text-center p-4 rounded-3">
                        <p class="mb-0 fw-medium text-dark"><?= e($extra['intro_notice']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="terms-content" style="line-height: 1.8;">
                        <?= $pageData['content']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
