<?php
/**
 * WAPI SaaS - 500 Internal Server Error Page
 */
http_response_code(500);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

$pageTitle = 'Server Error - 500';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: radial-gradient(circle at center, rgba(245,158,11,0.06) 0%, rgba(248,249,250,1) 70%);">
    <div class="container text-center py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="mb-4">
                    <span class="display-1 fw-bolder text-warning" style="letter-spacing: -2px; font-size: 6.5rem;">500</span>
                </div>
                <div class="mb-4">
                    <span class="badge rounded-pill bg-warning-subtle text-warning px-3 py-2 fs-6 fw-semibold">
                        <i class="bi bi-cpu-fill me-1"></i> Internal Server Error
                    </span>
                </div>
                <h2 class="fw-bold mb-3">Something Went Wrong</h2>
                <p class="text-secondary lead mb-4 fs-6">
                    Our servers encountered an unexpected issue while processing your request. Our technical team has been notified.
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="<?= baseUrl(); ?>" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-house-door-fill me-1"></i> Return Home
                    </a>
                    <a href="javascript:location.reload();" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                        <i class="bi bi-arrow-clockwise me-1"></i> Try Again
                    </a>
                    <a href="<?= baseUrl('contact.php'); ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                        <i class="bi bi-headset me-1"></i> Support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
