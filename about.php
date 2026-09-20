<?php
/**
 * WAPI SaaS - About Us
 * Dynamically customized via Super Admin Pages Customizer
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

$pageData = PageManager::getPage('about');
$extra = $pageData['extra_data'] ?? [];

$pageTitle = !empty($pageData['meta_title']) ? $pageData['meta_title'] : (!empty($pageData['title']) ? $pageData['title'] : 'About Us');
$metaDescription = $pageData['meta_description'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row align-items-center mb-5">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4"><?= e($extra['headline'] ?? $pageData['title']); ?></h1>
                <?php if (!empty($pageData['content'])): ?>
                    <div class="about-content" style="line-height: 1.8;">
                        <?= $pageData['content']; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <div class="p-4 bg-white shadow-sm rounded-4 text-center">
                    <?php 
                    $aboutImg = !empty($extra['image_url']) ? $extra['image_url'] : 'assets/img/hero-image.png';
                    $aboutImgSrc = (strpos($aboutImg, 'http') === 0 || strpos($aboutImg, '/') === 0) ? $aboutImg : asset($aboutImg);
                    ?>
                    <img src="<?= e($aboutImgSrc); ?>" alt="<?= e($pageData['title']); ?>" class="img-fluid rounded" onerror="this.src='https://placehold.co/600x400/6366f1/white?text=WAPI+Team'">
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <!-- Vision -->
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                    <div class="feature-icon mb-3 mx-auto" style="width: 60px; height: 60px;">
                        <i class="bi <?= e($extra['vision_icon'] ?? 'bi-eye-fill'); ?>"></i>
                    </div>
                    <h4 class="fw-bold"><?= e($extra['vision_title'] ?? 'Our Vision'); ?></h4>
                    <p class="text-secondary mb-0"><?= e($extra['vision_desc'] ?? 'To become the global standard for business-to-customer messaging and engagement.'); ?></p>
                </div>
            </div>
            <!-- Mission -->
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                    <div class="feature-icon mb-3 mx-auto" style="width: 60px; height: 60px;">
                        <i class="bi <?= e($extra['mission_icon'] ?? 'bi-bullseye'); ?>"></i>
                    </div>
                    <h4 class="fw-bold"><?= e($extra['mission_title'] ?? 'Our Mission'); ?></h4>
                    <p class="text-secondary mb-0"><?= e($extra['mission_desc'] ?? 'To provide powerful, easy-to-use tools that bridge the gap between businesses and their customers.'); ?></p>
                </div>
            </div>
            <!-- Values -->
            <div class="col-md-4">
                <div class="p-4 bg-white rounded-4 shadow-sm h-100 text-center">
                    <div class="feature-icon mb-3 mx-auto" style="width: 60px; height: 60px;">
                        <i class="bi <?= e($extra['values_icon'] ?? 'bi-heart-fill'); ?>"></i>
                    </div>
                    <h4 class="fw-bold"><?= e($extra['values_title'] ?? 'Our Values'); ?></h4>
                    <p class="text-secondary mb-0"><?= e($extra['values_desc'] ?? 'Transparency, innovation, and customer-first thinking in everything we build.'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
