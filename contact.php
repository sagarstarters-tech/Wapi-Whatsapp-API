<?php
/**
 * WAPI SaaS - Contact Us
 * Dynamically customized via Super Admin Pages Customizer
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';

$pageData = PageManager::getPage('contact');
$extra = $pageData['extra_data'] ?? [];

$pageTitle = !empty($pageData['meta_title']) ? $pageData['meta_title'] : (!empty($pageData['title']) ? $pageData['title'] : 'Contact Us');
$metaDescription = $pageData['meta_description'] ?? '';

include __DIR__ . '/includes/header.php';
?>

<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-5">
                <h1 class="display-4 fw-bold mb-4"><?= e($extra['headline'] ?? 'Chat With Us'); ?></h1>
                <?php if (!empty($pageData['subtitle'])): ?>
                <p class="lead text-secondary mb-5">
                    <?= nl2br(e($pageData['subtitle'])); ?>
                </p>
                <?php endif; ?>
                
                <div class="d-flex flex-column gap-4">
                    <?php if (!empty($extra['hq_address'])): ?>
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="feature-icon" style="width: 48px; height: 48px;"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1"><?= e($extra['hq_title'] ?? 'Our Headquarters'); ?></h6>
                                <p class="mb-0 text-secondary"><?= e($extra['hq_address']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php 
                    $contactDisplayEmail = !empty($extra['email']) ? $extra['email'] : $settings->get('contact_email', 'wapiwhatsappapi@gmail.com');
                    if (!empty($contactDisplayEmail)): 
                    ?>
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="feature-icon" style="width: 48px; height: 48px;"><i class="bi bi-envelope-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1"><?= e($extra['email_title'] ?? 'Email Support'); ?></h6>
                                <p class="mb-0 text-secondary"><a href="mailto:<?= e($contactDisplayEmail); ?>" class="text-decoration-none text-secondary"><?= e($contactDisplayEmail); ?></a></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php 
                    $contactDisplayPhone = !empty($extra['phone']) ? $extra['phone'] : $settings->get('contact_phone', '');
                    if (!empty($contactDisplayPhone)): 
                    ?>
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="feature-icon" style="width: 48px; height: 48px;"><i class="bi bi-telephone-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1"><?= e($extra['phone_title'] ?? 'Call Us'); ?></h6>
                                <p class="mb-0 text-secondary"><a href="tel:<?= e($contactDisplayPhone); ?>" class="text-decoration-none text-secondary"><?= e($contactDisplayPhone); ?></a></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($extra['hours_enabled']) && !empty($extra['hours'])): ?>
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="feature-icon" style="width: 48px; height: 48px;"><i class="bi bi-clock-fill"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1"><?= e($extra['hours_title'] ?? 'Business Hours'); ?></h6>
                                <p class="mb-0 text-secondary"><?= e($extra['hours']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($extra['whatsapp_enabled']) && !empty($extra['whatsapp_number'])): ?>
                    <div class="bg-white p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="feature-icon" style="width: 48px; height: 48px; background: rgba(37,211,102,0.1); color: #25D366;"><i class="bi bi-whatsapp"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1"><?= e($extra['whatsapp_title'] ?? 'Quick WhatsApp Chat'); ?></h6>
                                <p class="mb-0"><a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $extra['whatsapp_number']); ?>?text=<?= urlencode('Hello WAPI Support, I have a question:'); ?>" target="_blank" class="text-decoration-none text-success fw-semibold">Chat on WhatsApp &rarr;</a></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="bg-white p-5 rounded-4 shadow-sm">
                    <h3 class="fw-bold mb-2"><?= e($extra['form_title'] ?? 'Send Message'); ?></h3>
                    <?php if (!empty($extra['form_subtitle'])): ?>
                    <p class="text-secondary mb-4"><?= e($extra['form_subtitle']); ?></p>
                    <?php else: ?>
                    <div class="mb-4"></div>
                    <?php endif; ?>

                    <!-- Alert placeholder -->
                    <div id="contactAlert" class="d-none"></div>
                    <form action="api/contact.php" method="POST" id="contactForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select name="country_code" class="form-select" style="max-width: 140px;" required>
                                        <option value="+91" selected>🇮🇳 +91</option>
                                        <option value="+1">🇺🇸 +1</option>
                                        <option value="+44">🇬🇧 +44</option>
                                        <option value="+971">🇦🇪 +971</option>
                                        <option value="+966">🇸🇦 +966</option>
                                        <option value="+61">🇦🇺 +61</option>
                                        <option value="+81">🇯🇵 +81</option>
                                        <option value="+49">🇩🇪 +49</option>
                                        <option value="+33">🇫🇷 +33</option>
                                        <option value="+86">🇨🇳 +86</option>
                                        <option value="+55">🇧🇷 +55</option>
                                        <option value="+7">🇷🇺 +7</option>
                                        <option value="+27">🇿🇦 +27</option>
                                        <option value="+234">🇳🇬 +234</option>
                                        <option value="+92">🇵🇰 +92</option>
                                        <option value="+880">🇧🇩 +880</option>
                                        <option value="+94">🇱🇰 +94</option>
                                        <option value="+977">🇳🇵 +977</option>
                                        <option value="+60">🇲🇾 +60</option>
                                        <option value="+65">🇸🇬 +65</option>
                                        <option value="+63">🇵🇭 +63</option>
                                        <option value="+82">🇰🇷 +82</option>
                                        <option value="+39">🇮🇹 +39</option>
                                        <option value="+34">🇪🇸 +34</option>
                                        <option value="+52">🇲🇽 +52</option>
                                        <option value="+62">🇮🇩 +62</option>
                                        <option value="+90">🇹🇷 +90</option>
                                        <option value="+20">🇪🇬 +20</option>
                                        <option value="+254">🇰🇪 +254</option>
                                        <option value="+64">🇳🇿 +64</option>
                                    </select>
                                    <input type="tel" class="form-control" name="phone" placeholder="Enter mobile number" pattern="[0-9]{6,15}" title="Enter a valid phone number (6-15 digits)" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="message" rows="5" required></textarea>
                            </div>
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn btn-primary btn-lg px-5" id="contactSubmitBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="contactSpinner" role="status"></span>
                                    Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if (!empty($extra['map_enabled']) && !empty($extra['map_embed_url'])): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="bg-white p-3 rounded-4 shadow-sm">
                    <iframe src="<?= e($extra['map_embed_url']); ?>" width="100%" height="360" style="border:0; border-radius: 12px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form      = document.getElementById('contactForm');
    const alertBox  = document.getElementById('contactAlert');
    const submitBtn = document.getElementById('contactSubmitBtn');
    const spinner   = document.getElementById('contactSpinner');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Disable button & show spinner
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        alertBox.className = 'd-none';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alertBox.className = 'alert alert-success';
                alertBox.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + data.message;
                form.reset();
            } else {
                alertBox.className = 'alert alert-danger';
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + data.message;
            }
        })
        .catch(() => {
            alertBox.className = 'alert alert-danger';
            alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>Network error. Please try again.';
        })
        .finally(() => {
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
        });
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
