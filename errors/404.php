<?php
/**
 * WAPI SaaS - 404 Not Found Page
 */
http_response_code(404);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

$pageTitle = 'Page Not Found - 404';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: radial-gradient(circle at center, rgba(37,211,102,0.05) 0%, rgba(248,249,250,1) 70%);">
    <div class="container text-center py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="mb-4">
                    <span class="display-1 fw-bolder text-primary" style="letter-spacing: -2px; font-size: 6.5rem;">404</span>
                </div>
                <div class="mb-4">
                    <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2 fs-6 fw-semibold">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Page Not Found
                    </span>
                </div>
                <h2 class="fw-bold mb-3">Oops! Looks like you took a wrong turn.</h2>
                <p class="text-secondary lead mb-4 fs-6">
                    The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="<?= baseUrl(); ?>" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-house-door-fill me-1"></i> Back to Home
                    </a>
                    <?php if (Auth::check()): ?>
                    <a href="<?= baseUrl('dashboard/'); ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                        <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard
                    </a>
                    <?php else: ?>
                    <a href="<?= baseUrl('contact.php'); ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                        <i class="bi bi-envelope-fill me-1"></i> Contact Support
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
