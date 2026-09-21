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
    $uploadErrors = [];
    foreach (['site_logo', 'site_favicon'] as $fileField) {
        if (isset($_FILES[$fileField]) && $_FILES[$fileField]['error'] === UPLOAD_ERR_OK) {
            $allowed = ($fileField === 'site_favicon') 
                ? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'] 
                : ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                
            $result = uploadFile($_FILES[$fileField], 'settings', $allowed);
            if ($result['success']) {
                $settings->set($fileField, $result['path']);
            } else {
                $uploadErrors[] = $fileField . ": " . $result['message'];
            }
        }
    }

    if (!empty($uploadErrors)) {
        setFlash('danger', 'Some files failed to upload: ' . implode(', ', $uploadErrors));
    } else {
        setFlash('success', 'Settings saved successfully!');
    }
    
    // Save text settings
    $textFields = $_POST['settings'] ?? [];
    foreach ($textFields as $key => $value) {
        if ($key === 'smtp_password' && empty(trim((string)$value))) {
            continue;
        }
        $settings->set(sanitize($key), $value);
    }

    redirect('admin/settings.php?tab=' . $group);
}

$activeTab = sanitize($_GET['tab'] ?? 'general');
if ($activeTab === 'email') {
    redirect('admin/email-settings.php');
}
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
            <li><a class="nav-link <?= $activeTab === 'automations' ? 'active' : ''; ?> btn-sm" href="?tab=automations" style="border-radius: 8px;"><i class="bi bi-robot me-1"></i>Automations</a></li>
            <li><a class="nav-link <?= $activeTab === 'theme' ? 'active' : ''; ?> btn-sm" href="?tab=theme" style="border-radius: 8px;">Theme</a></li>
            <li><a class="nav-link <?= $activeTab === 'landing' ? 'active' : ''; ?> btn-sm" href="?tab=landing" style="border-radius: 8px;">Landing Page</a></li>
            <li><a class="nav-link <?= $activeTab === 'seo' ? 'active' : ''; ?> btn-sm" href="?tab=seo" style="border-radius: 8px;">SEO</a></li>
            <li><a class="nav-link <?= $activeTab === 'payment' ? 'active' : ''; ?> btn-sm" href="?tab=payment" style="border-radius: 8px;">Payment</a></li>
            <li><a class="nav-link btn-sm" href="<?= baseUrl('admin/email-settings.php'); ?>" style="border-radius: 8px;"><i class="bi bi-envelope-fill me-1"></i>Email / SMTP</a></li>
            <li><a class="nav-link <?= $activeTab === 'security' ? 'active' : ''; ?> btn-sm" href="?tab=security" style="border-radius: 8px;">Security</a></li>
            <li><a class="nav-link <?= $activeTab === 'widget' ? 'active' : ''; ?> btn-sm" href="?tab=widget" style="border-radius: 8px;">Chat Widget</a></li>
            <li><a class="nav-link <?= $activeTab === 'social' ? 'active' : ''; ?> btn-sm" href="?tab=social" style="border-radius: 8px;">Social Links</a></li>
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
                            <input type="file" name="site_logo" id="siteLogoInput" class="form-control" accept="image/*">
                            <?php if (!empty($allSettings['site_logo'])): ?>
                            <small class="text-muted d-block mt-1">Current: <?= e($allSettings['site_logo']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>Logo Size / Height (px)</span>
                                <span class="badge bg-primary px-2 py-1" id="logoHeightBadge"><?= (int)($allSettings['logo_height'] ?? 48); ?>px</span>
                            </label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="range" class="form-range flex-grow-1" id="logoHeightRange" min="20" max="150" step="2" 
                                       value="<?= (int)($allSettings['logo_height'] ?? 48); ?>" 
                                       oninput="syncLogoHeight(this.value)">
                                <div class="input-group" style="width: 105px;">
                                    <input type="number" name="settings[logo_height]" id="logoHeightInput" class="form-control" 
                                           min="20" max="250" value="<?= (int)($allSettings['logo_height'] ?? 48); ?>" 
                                           oninput="syncLogoHeight(this.value)">
                                    <span class="input-group-text px-2 text-muted" style="font-size: 0.8rem;">px</span>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">Navbar aur header logo ki height control karein (Default: 48px, Recommended: 36px - 80px).</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Logo Live Preview</label>
                            <div class="p-3 border rounded-3 d-flex flex-wrap align-items-center gap-3" style="background: var(--card-bg, #f8f9fa);">
                                <?php 
                                    $currLogo = $allSettings['site_logo'] ?? '';
                                    $previewLogoUrl = '';
                                    if (!empty($currLogo)) {
                                        $currLogoPath = str_replace('/wapi/', '', $currLogo);
                                        if (strpos($currLogoPath, 'http') === 0) {
                                            $previewLogoUrl = $currLogoPath;
                                        } else {
                                            $cleanCurrPath = ltrim($currLogoPath, '/');
                                            if (file_exists(APP_ROOT . '/' . $cleanCurrPath)) {
                                                $previewLogoUrl = baseUrl($cleanCurrPath);
                                            }
                                        }
                                    }
                                    if (empty($previewLogoUrl)) {
                                        if (file_exists(APP_ROOT . '/assets/img/logo.png')) {
                                            $previewLogoUrl = baseUrl('assets/img/logo.png');
                                        } elseif (file_exists(APP_ROOT . '/assets/images/logo.png')) {
                                            $previewLogoUrl = baseUrl('assets/images/logo.png');
                                        }
                                    }
                                    $currLogoHeight = (int)($allSettings['logo_height'] ?? 48);
                                    if ($currLogoHeight <= 0) $currLogoHeight = 48;
                                ?>
                                <div class="d-flex align-items-center gap-2 p-2 px-3 rounded border bg-white shadow-sm" id="logoPreviewBox">
                                    <img id="logoPreviewImg" src="<?= e($previewLogoUrl); ?>" alt="Logo Preview" 
                                         style="height: <?= $currLogoHeight; ?>px; max-height: <?= $currLogoHeight; ?>px; width: auto; object-fit: contain; transition: height 0.1s ease;">
                                    <span class="fw-bold fs-5 text-dark" id="logoPreviewBrand"><?= e($allSettings['site_name'] ?? 'WAPI'); ?></span>
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> Slider drag karke ya pixel type karke size adjust karein. Niche <strong>Save Settings</strong> par click karna na bhoolein.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Favicon</label>
                            <input type="file" name="site_favicon" class="form-control" accept="image/*">
                            <?php if (!empty($allSettings['site_favicon'])): ?>
                            <small class="text-muted d-block mt-1">Current: <?= e($allSettings['site_favicon']); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Footer Text</label>
                            <input type="text" name="settings[footer_text]" class="form-control" value="<?= e($allSettings['footer_text'] ?? ''); ?>">
                        </div>
                    </div>

                <?php elseif ($activeTab === 'automations'): ?>
                    <h5 class="fw-bold mb-1"><i class="bi bi-robot me-2 text-primary"></i>Automation Modules Settings</h5>
                    <p class="text-muted small mb-4">Enable or disable Chatbot Builder and AI ChatBot Builder across the platform.</p>

                    <?php 
                    $chatbotEnabled = ($allSettings['enable_chatbot_builder'] ?? '1') === '1';
                    $aiChatbotEnabled = ($allSettings['enable_ai_chatbot_builder'] ?? '1') === '1';
                    ?>

                    <div class="row g-4">
                        <!-- Chatbot Builder Module Card -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-3 h-100 d-flex flex-column justify-content-between shadow-sm" style="background: var(--card-bg, #ffffff); border-color: var(--border-color) !important;">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; font-size: 1.4rem;">
                                                <i class="bi bi-robot"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-0">Chatbot Builder</h6>
                                                <small class="text-muted">Visual Flow Builder</small>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input type="hidden" name="settings[enable_chatbot_builder]" value="0">
                                            <input class="form-check-input" type="checkbox" name="settings[enable_chatbot_builder]" value="1" id="switchChatbotBuilder" <?= $chatbotEnabled ? 'checked' : ''; ?> style="width: 2.8rem; height: 1.5rem; cursor: pointer;" onchange="ajaxToggleModule('chatbot_builder', this.checked)">
                                        </div>
                                    </div>
                                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                                        Visual flowchart rule-based chatbot builder. When enabled, users can build keyword-triggered nodes, buttons, interactive media replies, and automated WhatsApp conversation flows.
                                    </p>
                                </div>
                                <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                                    <span id="badgeChatbotBuilder" class="badge <?= $chatbotEnabled ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'; ?> px-2 py-1">
                                        <i class="bi <?= $chatbotEnabled ? 'bi-check-circle-fill' : 'bi-dash-circle'; ?> me-1"></i><?= $chatbotEnabled ? 'Active & Enabled' : 'Disabled'; ?>
                                    </span>
                                    <a href="<?= baseUrl('dashboard/chatbot-builder.php'); ?>" class="btn btn-sm btn-outline-primary" style="border-radius: 6px;" target="_blank">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Builder
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- AI ChatBot Builder Module Card -->
                        <div class="col-md-6">
                            <div class="p-4 border rounded-3 h-100 d-flex flex-column justify-content-between shadow-sm" style="background: var(--card-bg, #ffffff); border-color: var(--border-color) !important;">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 text-white d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; font-size: 1.4rem; background: linear-gradient(135deg, #667eea, #764ba2);">
                                                <i class="bi bi-stars"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-0">AI ChatBot Builder</h6>
                                                <small class="text-muted">Knowledge Base & AI Engine</small>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input type="hidden" name="settings[enable_ai_chatbot_builder]" value="0">
                                            <input class="form-check-input" type="checkbox" name="settings[enable_ai_chatbot_builder]" value="1" id="switchAIChatbotBuilder" <?= $aiChatbotEnabled ? 'checked' : ''; ?> style="width: 2.8rem; height: 1.5rem; cursor: pointer;" onchange="ajaxToggleModule('ai_chatbot_builder', this.checked)">
                                        </div>
                                    </div>
                                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                                        Generative AI assistant powered by Gemini / OpenAI. When enabled, users can train bots with Documents, Website Auto-Crawl, Q&A pairs, and handle intelligent customer support and conversations.
                                    </p>
                                </div>
                                <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                                    <span id="badgeAIChatbotBuilder" class="badge <?= $aiChatbotEnabled ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'; ?> px-2 py-1">
                                        <i class="bi <?= $aiChatbotEnabled ? 'bi-check-circle-fill' : 'bi-dash-circle'; ?> me-1"></i><?= $aiChatbotEnabled ? 'Active & Enabled' : 'Disabled'; ?>
                                    </span>
                                    <div class="d-flex gap-1">
                                        <a href="<?= baseUrl('admin/ai-settings.php'); ?>" class="btn btn-sm btn-outline-secondary" style="border-radius: 6px;">
                                            <i class="bi bi-cpu me-1"></i>AI Settings
                                        </a>
                                        <a href="<?= baseUrl('dashboard/ai-chatbot.php'); ?>" class="btn btn-sm btn-outline-primary" style="border-radius: 6px;" target="_blank">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Open Builder
                                        </a>
                                    </div>
                                </div>
                            </div>
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
                        <div class="col-12 border-top pt-3"><label class="form-label text-primary fw-bold">Features Section</label></div>
                        <div class="col-12"><label class="form-label">Features Section Title</label><input type="text" name="settings[features_title]" class="form-control" value="<?= e($allSettings['features_title'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Features Subtitle</label><textarea name="settings[features_subtitle]" class="form-control" rows="2"><?= e($allSettings['features_subtitle'] ?? ''); ?></textarea></div>
                        <div class="col-12 border-top pt-3"><label class="form-label text-primary fw-bold">Pricing Section</label></div>
                        <div class="col-12"><label class="form-label">Pricing Title</label><input type="text" name="settings[pricing_title]" class="form-control" value="<?= e($allSettings['pricing_title'] ?? ''); ?>"></div>
                        <div class="col-12 border-top pt-3"><label class="form-label text-primary fw-bold">CTA Section (Bottom)</label></div>
                        <div class="col-12"><label class="form-label">CTA Title</label><input type="text" name="settings[cta_title]" class="form-control" value="<?= e($allSettings['cta_title'] ?? 'Ready to Get Started?'); ?>"></div>
                        <div class="col-12"><label class="form-label">CTA Subtitle</label><textarea name="settings[cta_subtitle]" class="form-control" rows="2"><?= e($allSettings['cta_subtitle'] ?? 'Join thousands of businesses using WAPI to power their WhatsApp communication.'); ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">CTA Button Text</label><input type="text" name="settings[cta_button_text]" class="form-control" value="<?= e($allSettings['cta_button_text'] ?? 'Start 14 Days Free Trial'); ?>"></div>
                        <div class="col-md-6"><label class="form-label">CTA Button Link</label><input type="text" name="settings[cta_button_link]" class="form-control" value="<?= e($allSettings['cta_button_link'] ?? 'auth/register.php?plan=trial'); ?>"></div>
                        <div class="col-12 border-top pt-3 text-secondary">Titles for Other Sections</div>
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

                    <h5 class="fw-bold mt-5 mb-4 border-top pt-4">Manual UPI / QR Gateway (PhonePe/GPay)</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Payment Mode</label>
                            <select name="settings[payment_method_manual_enabled]" class="form-control">
                                <option value="1" <?= ($allSettings['payment_method_manual_enabled'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?= ($allSettings['payment_method_manual_enabled'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Merchant Name</label><input type="text" name="settings[upi_name]" class="form-control" value="<?= e($allSettings['upi_name'] ?? ''); ?>" placeholder="Sagar Starters"></div>
                        <div class="col-md-12"><label class="form-label">Merchant UPI ID</label><input type="text" name="settings[upi_id]" class="form-control" value="<?= e($allSettings['upi_id'] ?? ''); ?>" placeholder="merchant@upi"></div>
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
                <?php elseif ($activeTab === 'social'): ?>
                    <h5 class="fw-bold mb-4">Social Media Links</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-facebook me-2 text-primary"></i>Facebook URL</label>
                            <input type="url" name="settings[social_facebook]" class="form-control" value="<?= e($allSettings['social_facebook'] ?? ''); ?>" placeholder="https://facebook.com/yourpage">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-twitter-x me-2 text-dark"></i>Twitter (X) URL</label>
                            <input type="url" name="settings[social_twitter]" class="form-control" value="<?= e($allSettings['social_twitter'] ?? ''); ?>" placeholder="https://x.com/yourhandle">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-instagram me-2 text-danger"></i>Instagram URL</label>
                            <input type="url" name="settings[social_instagram]" class="form-control" value="<?= e($allSettings['social_instagram'] ?? ''); ?>" placeholder="https://instagram.com/yourhandle">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-linkedin me-2 text-primary"></i>LinkedIn URL</label>
                            <input type="url" name="settings[social_linkedin]" class="form-control" value="<?= e($allSettings['social_linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/company/yourpage">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-github me-2 text-dark"></i>GitHub URL</label>
                            <input type="url" name="settings[social_github]" class="form-control" value="<?= e($allSettings['social_github'] ?? ''); ?>" placeholder="https://github.com/yourhandle">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-youtube me-2 text-danger"></i>YouTube URL</label>
                            <input type="url" name="settings[social_youtube]" class="form-control" value="<?= e($allSettings['social_youtube'] ?? ''); ?>" placeholder="https://youtube.com/@yourchannel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="bi bi-telegram me-2 text-info"></i>Telegram URL</label>
                            <input type="url" name="settings[social_telegram]" class="form-control" value="<?= e($allSettings['social_telegram'] ?? ''); ?>" placeholder="https://t.me/yourchannel">
                        </div>
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

<script>
function syncLogoHeight(val) {
    val = parseInt(val) || 48;
    if (val < 15) val = 15;
    const badge = document.getElementById('logoHeightBadge');
    const range = document.getElementById('logoHeightRange');
    const input = document.getElementById('logoHeightInput');
    const previewImg = document.getElementById('logoPreviewImg');
    
    if (badge) badge.textContent = val + 'px';
    if (range && range.value != val) range.value = val;
    if (input && input.value != val) input.value = val;
    if (previewImg) {
        previewImg.style.height = val + 'px';
        previewImg.style.maxHeight = val + 'px';
    }
}

document.getElementById('siteLogoInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const previewImg = document.getElementById('logoPreviewImg');
            if (previewImg) previewImg.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});

document.querySelector('input[name="settings[site_name]"]')?.addEventListener('input', function(e) {
    const brand = document.getElementById('logoPreviewBrand');
    if (brand) brand.textContent = e.target.value || 'WAPI';
});

function ajaxToggleModule(moduleName, isChecked) {
    const status = isChecked ? 1 : 0;
    const badgeId = moduleName === 'chatbot_builder' ? 'badgeChatbotBuilder' : 'badgeAIChatbotBuilder';
    const badge = document.getElementById(badgeId);

    if (badge) {
        badge.className = status ? 'badge bg-success-subtle text-success px-2 py-1' : 'badge bg-secondary-subtle text-secondary px-2 py-1';
        badge.innerHTML = status ? '<i class="bi bi-check-circle-fill me-1"></i>Active & Enabled' : '<i class="bi bi-dash-circle me-1"></i>Disabled';
    }

    const csrfToken = document.querySelector('input[name="<?= CSRF_TOKEN_NAME; ?>"]')?.value || '';

    fetch('<?= baseUrl("api/toggle-module.php"); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            module: moduleName,
            status: status,
            _csrf_token: csrfToken
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Failed to update module status');
            // Revert switch
            const switchEl = document.getElementById(moduleName === 'chatbot_builder' ? 'switchChatbotBuilder' : 'switchAIChatbotBuilder');
            if (switchEl) switchEl.checked = !isChecked;
        }
    })
    .catch(err => {
        console.error(err);
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
