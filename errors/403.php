<?php
/**
 * WAPI SaaS - 403 Forbidden Page
 */
http_response_code(403);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

$pageTitle = 'Access Forbidden - 403';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background: radial-gradient(circle at center, rgba(239,68,68,0.06) 0%, rgba(248,249,250,1) 70%);">
    <div class="container text-center py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="mb-4">
                    <span class="display-1 fw-bolder text-danger" style="letter-spacing: -2px; font-size: 6.5rem;">403</span>
                </div>
                <div class="mb-4">
                    <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2 fs-6 fw-semibold">
                        <i class="bi bi-shield-lock-fill me-1"></i> Access Denied
                    </span>
                </div>
                <h2 class="fw-bold mb-3">Forbidden Resource</h2>
                <p class="text-secondary lead mb-4 fs-6">
                    You do not have permission to view or access this resource on our server.
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="<?= baseUrl(); ?>" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-house-door-fill me-1"></i> Return Home
                    </a>
                    <?php if (Auth::check()): ?>
                    <a href="<?= baseUrl('dashboard/'); ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                        <i class="bi bi-grid-1x2-fill me-1"></i> Dashboard
                    </a>
                    <?php else: ?>
                    <a href="<?= baseUrl('auth/login.php'); ?>" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Log In
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
