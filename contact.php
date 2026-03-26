<?php
/**
 * WAPI SaaS - Contact Us
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

$pageTitle = 'Contact Us';
include __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-5">
                <h1 class="display-4 fw-bold mb-4">Chat With Us</h1>
                <p class="lead text-secondary mb-5">
                    Have questions or feedback? We'd love to hear from you. 
                </p>
                
                <div class="d-flex flex-column gap-4">
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="feature-icon" style="width: 48px; height: 48px;"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1">Our Headquarters</h6>
                                <p class="mb-0 text-secondary">Mumbai, India</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="feature-icon" style="width: 48px; height: 48px;"><i class="bi bi-envelope-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1">Email Support</h6>
                                <p class="mb-0 text-secondary"><?= e($settings->get('contact_email', 'support@wapi.com')); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php if ($settings->get('contact_phone')): ?>
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="feature-icon" style="width: 48px; height: 48px;"><i class="bi bi-telephone-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1">Call Us</h6>
                                <p class="mb-0 text-secondary"><?= e($settings->get('contact_phone')); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="bg-white p-5 rounded-4 shadow-sm">
                    <h3 class="fw-bold mb-4">Send Message</h3>
                    <form action="#" method="POST" id="contactForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" name="message" rows="5" required></textarea>
                            </div>
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary btn-lg px-5">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
