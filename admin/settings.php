<?php
/**
 * WAPI SaaS - Admin Settings Page
 * General, theme, SEO, and landing page settings
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireAdmin();

$db = Database::getInstance();
$settings = new Settings();

$hideNav = true; // Prevents landing page nav from appearing in admin

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validateToken()) {
    $group = sanitize($_POST['group'] ?? 'general');
    
    // Handle file uploads (logo, favicon)
    foreach (['site_logo', 'site_favicon'] as $fileField) {
        if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
            $result = uploadFile($_FILES[$fileField], 'settings', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
            if ($result['success']) {
                $settings->set($fileField, $result['path']);
            }
        }
    }

    // Save text settings
    $textFields = $_POST['settings'] ?? [];
    foreach ($textFields as $key => $value) {
        $settings->set(sanitize($key), $value);
    }

    setFlash('success', 'Settings saved successfully!');
    redirect('admin/settings.php?tab=' . $group);
}

$activeTab = sanitize($_GET['tab'] ?? 'general');
$allSettings = $settings->getAll();

$pageTitle = 'Settings';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Settings</h1>
                <div class="dash-breadcrumb">
                    <a href="<?= baseUrl('admin/'); ?>">Admin</a><i class="bi bi-chevron-right"></i><span>Settings</span>
                </div>
            </div>
            <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?> fade-in"><i class="bi bi-check-circle-fill"></i> <?= e($flash['message']); ?></div>
        <?php endif; ?>

        <!-- Settings Tabs -->
        <ul class="nav nav-pills mb-4 flex-wrap gap-2">
            <li><a class="nav-link <?= $activeTab === 'general' ? 'active' : ''; ?> btn-sm" href="?tab=general" style="border-radius: 8px;">General</a></li>
            <li><a class="nav-link <?= $activeTab === 'theme' ? 'active' : ''; ?> btn-sm" href="?tab=theme" style="border-radius: 8px;">Theme</a></li>
            <li><a class="nav-link <?= $activeTab === 'landing' ? 'active' : ''; ?> btn-sm" href="?tab=landing" style="border-radius: 8px;">Landing Page</a></li>
            <li><a class="nav-link <?= $activeTab === 'seo' ? 'active' : ''; ?> btn-sm" href="?tab=seo" style="border-radius: 8px;">SEO</a></li>
            <li><a class="nav-link <?= $activeTab === 'payment' ? 'active' : ''; ?> btn-sm" href="?tab=payment" style="border-radius: 8px;">Payment</a></li>
            <li><a class="nav-link <?= $activeTab === 'email' ? 'active' : ''; ?> btn-sm" href="?tab=email" style="border-radius: 8px;">Email / SMTP</a></li>
            <li><a class="nav-link <?= $activeTab === 'security' ? 'active' : ''; ?> btn-sm" href="?tab=security" style="border-radius: 8px;">Security</a></li>
            <li><a class="nav-link <?= $activeTab === 'widget' ? 'active' : ''; ?> btn-sm" href="?tab=widget" style="border-radius: 8px;">Chat Widget</a></li>
        </ul>

        <form method="POST" enctype="multipart/form-data">
            <?= CSRF::tokenField(); ?>
            <input type="hidden" name="group" value="<?= e($activeTab); ?>">

            <div class="card" style="border-radius: var(--border-radius);">
                <div class="card-body p-4">

                <?php if ($activeTab === 'general'): ?>
                    <h5 class="fw-bold mb-4">General Settings</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="settings[site_name]" class="form-control" value="<?= e($allSettings['site_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tagline</label>
                            <input type="text" name="settings[site_tagline]" class="form-control" value="<?= e($allSettings['site_tagline'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Site Description</label>
                            <textarea name="settings[site_description]" class="form-control" rows="3"><?= e($allSettings['site_description'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp Channel URL</label>
                            <input type="url" name="settings[whatsapp_channel_url]" class="form-control" value="<?= e($allSettings['whatsapp_channel_url'] ?? ''); ?>" placeholder="https://whatsapp.com/channel/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="settings[contact_email]" class="form-control" value="<?= e($allSettings['contact_email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="settings[contact_phone]" class="form-control" value="<?= e($allSettings['contact_phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Site Logo</label>
                            <input type="file" name="site_logo" class="form-control" accept="image/*">
                            <?php if (!empty($allSettings['site_logo'])): ?>
                            <small class="text-muted">Current: <?= e($allSettings['site_logo']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Favicon</label>
                            <input type="file" name="site_favicon" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Footer Text</label>
                            <input type="text" name="settings[footer_text]" class="form-control" value="<?= e($allSettings['footer_text'] ?? ''); ?>">
                        </div>
                    </div>

                <?php elseif ($activeTab === 'theme'): ?>
                    <h5 class="fw-bold mb-4">Theme Customization</h5>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label">Primary Color</label>
                            <input type="color" name="settings[primary_color]" class="form-control form-control-color" value="<?= e($allSettings['primary_color'] ?? '#6c63ff'); ?>" style="height: 48px;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Secondary Color</label>
                            <input type="color" name="settings[secondary_color]" class="form-control form-control-color" value="<?= e($allSettings['secondary_color'] ?? '#3f3d56'); ?>" style="height: 48px;">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Accent Color</label>
                            <input type="color" name="settings[accent_color]" class="form-control form-control-color" value="<?= e($allSettings['accent_color'] ?? '#00d2ff'); ?>" style="height: 48px;">
                        </div>
                    </div>

                <?php elseif ($activeTab === 'landing'): ?>
                    <h5 class="fw-bold mb-4">Landing Page Content</h5>
                    <div class="row g-4">
                        <div class="col-12"><label class="form-label">Hero Title</label><input type="text" name="settings[hero_title]" class="form-control" value="<?= e($allSettings['hero_title'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Hero Subtitle</label><textarea name="settings[hero_subtitle]" class="form-control" rows="2"><?= e($allSettings['hero_subtitle'] ?? ''); ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">Hero Button Text</label><input type="text" name="settings[hero_button_text]" class="form-control" value="<?= e($allSettings['hero_button_text'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Hero Button Link</label><input type="text" name="settings[hero_button_link]" class="form-control" value="<?= e($allSettings['hero_button_link'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Features Section Title</label><input type="text" name="settings[features_title]" class="form-control" value="<?= e($allSettings['features_title'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Features Subtitle</label><textarea name="settings[features_subtitle]" class="form-control" rows="2"><?= e($allSettings['features_subtitle'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Pricing Title</label><input type="text" name="settings[pricing_title]" class="form-control" value="<?= e($allSettings['pricing_title'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Testimonials Title</label><input type="text" name="settings[testimonials_title]" class="form-control" value="<?= e($allSettings['testimonials_title'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">FAQ Title</label><input type="text" name="settings[faq_title]" class="form-control" value="<?= e($allSettings['faq_title'] ?? ''); ?>"></div>
                    </div>

                <?php elseif ($activeTab === 'payment'): ?>
                    <h5 class="fw-bold mb-4">Razorpay Settings</h5>
                    <div class="row g-4">
                        <div class="col-md-6"><label class="form-label">Razorpay Key ID</label><input type="text" name="settings[razorpay_key_id]" class="form-control" value="<?= e($allSettings['razorpay_key_id'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Razorpay Key Secret</label><input type="password" name="settings[razorpay_key_secret]" class="form-control" value="<?= e($allSettings['razorpay_key_secret'] ?? ''); ?>"></div>
                        <div class="col-md-6">
                            <label class="form-label">Test Mode</label>
                            <select name="settings[razorpay_test_mode]" class="form-control">
                                <option value="1" <?= ($allSettings['razorpay_test_mode'] ?? '1') === '1' ? 'selected' : ''; ?>>Yes (Test Mode)</option>
                                <option value="0" <?= ($allSettings['razorpay_test_mode'] ?? '1') === '0' ? 'selected' : ''; ?>>No (Live Mode)</option>
                            </select>
                        </div>
                    </div>

                <?php elseif ($activeTab === 'email'): ?>
                    <h5 class="fw-bold mb-4">Email Configuration</h5>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">Mail Driver</label>
                            <select name="settings[email_driver]" class="form-control">
                                <option value="mail" <?= ($allSettings['email_driver'] ?? 'mail') === 'mail' ? 'selected' : ''; ?>>PHP Mail (Previous)</option>
                                <option value="smtp" <?= ($allSettings['email_driver'] ?? '') === 'smtp' ? 'selected' : ''; ?>>SMTP (Gmail/Other)</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">SMTP Host</label><input type="text" name="settings[smtp_host]" class="form-control" value="<?= e($allSettings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com"></div>
                        <div class="col-md-3"><label class="form-label">Port</label><input type="number" name="settings[smtp_port]" class="form-control" value="<?= e($allSettings['smtp_port'] ?? '587'); ?>"></div>
                        <div class="col-md-3"><label class="form-label">Encryption</label>
                            <select name="settings[smtp_encryption]" class="form-control">
                                <option value="tls" <?= ($allSettings['smtp_encryption'] ?? '') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?= ($allSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Username</label><input type="text" name="settings[smtp_username]" class="form-control" value="<?= e($allSettings['smtp_username'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Password</label><input type="password" name="settings[smtp_password]" class="form-control" value="<?= e($allSettings['smtp_password'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">From Name</label><input type="text" name="settings[smtp_from_name]" class="form-control" value="<?= e($allSettings['smtp_from_name'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">From Email</label><input type="email" name="settings[smtp_from_email]" class="form-control" value="<?= e($allSettings['smtp_from_email'] ?? ''); ?>"></div>
                    </div>

                <?php elseif ($activeTab === 'security'): ?>
                    <h5 class="fw-bold mb-4">Security Settings</h5>
                    <div class="row g-4">
                        <div class="col-md-6"><label class="form-label">reCAPTCHA Site Key</label><input type="text" name="settings[recaptcha_site_key]" class="form-control" value="<?= e($allSettings['recaptcha_site_key'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">reCAPTCHA Secret Key</label><input type="text" name="settings[recaptcha_secret_key]" class="form-control" value="<?= e($allSettings['recaptcha_secret_key'] ?? ''); ?>"></div>
                    </div>

                <?php elseif ($activeTab === 'widget'): ?>
                    <h5 class="fw-bold mb-4">WhatsApp Chat Widget</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Enable Widget</label>
                            <select name="settings[chat_widget_enabled]" class="form-control">
                                <option value="1" <?= ($allSettings['chat_widget_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?= ($allSettings['chat_widget_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">WhatsApp Number</label><input type="text" name="settings[chat_widget_number]" class="form-control" value="<?= e($allSettings['chat_widget_number'] ?? ''); ?>" placeholder="+919876543210"></div>
                        <div class="col-12"><label class="form-label">Default Message</label><input type="text" name="settings[chat_widget_message]" class="form-control" value="<?= e($allSettings['chat_widget_message'] ?? ''); ?>"></div>
                    </div>

                <?php elseif ($activeTab === 'seo'): ?>
                    <h5 class="fw-bold mb-4">SEO Settings</h5>
                    <div class="row g-4">
                        <div class="col-12"><label class="form-label">Meta Keywords</label><textarea name="settings[meta_keywords]" class="form-control" rows="3"><?= e($allSettings['meta_keywords'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Google Analytics ID</label><input type="text" name="settings[google_analytics_id]" class="form-control" value="<?= e($allSettings['google_analytics_id'] ?? ''); ?>" placeholder="G-XXXXXXXXXX"></div>
                    </div>
                <?php endif; ?>

                </div>
                <div class="card-footer bg-transparent border-top p-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Settings</button>
                </div>
            </div>
        </form>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
