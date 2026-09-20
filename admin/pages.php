<?php
/**
 * WAPI SaaS - Super Admin Pages Customizer
 * 100% customize Contact Us, About Us, Privacy Policy, Terms, and Cookie Policy
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireAdmin();

$db = Database::getInstance();
$settings = new Settings();

$hideNav = true; // Admin layout

// Valid page slugs
$validSlugs = ['contact', 'about', 'privacy', 'terms', 'cookies', 'gdpr', 'data-deletion'];
$activeSlug = sanitize($_GET['page'] ?? 'contact');
if (!in_array($activeSlug, $validSlugs)) {
    $activeSlug = 'contact';
}

// Page Display Names
$pageNames = [
    'contact'       => ['name' => 'Contact Us', 'file' => 'contact.php', 'icon' => 'bi-telephone-fill'],
    'about'         => ['name' => 'About Us', 'file' => 'about.php', 'icon' => 'bi-info-circle-fill'],
    'privacy'       => ['name' => 'Privacy Policy', 'file' => 'privacy.php', 'icon' => 'bi-shield-lock-fill'],
    'terms'         => ['name' => 'Terms of Service', 'file' => 'terms.php', 'icon' => 'bi-file-earmark-text-fill'],
    'cookies'       => ['name' => 'Cookie Policy', 'file' => 'cookies.php', 'icon' => 'bi-cookie'],
    'gdpr'          => ['name' => 'GDPR Compliance', 'file' => 'gdpr.php', 'icon' => 'bi-shield-check'],
    'data-deletion' => ['name' => 'Data Deletion', 'file' => 'data-deletion.php', 'icon' => 'bi-trash3-fill'],
];

// Handle Form Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validateToken()) {
    $action = sanitize($_POST['action'] ?? 'save');

    if ($action === 'save') {
        $slug = sanitize($_POST['slug'] ?? $activeSlug);
        if (!in_array($slug, $validSlugs)) {
            $slug = 'contact';
        }

        $title           = sanitize($_POST['title'] ?? '');
        $subtitle        = sanitize($_POST['subtitle'] ?? '');
        $metaTitle       = sanitize($_POST['meta_title'] ?? '');
        $metaDescription = sanitize($_POST['meta_description'] ?? '');
        $isActive        = isset($_POST['is_active']) ? 1 : 0;

        // Content field (preserve safe HTML)
        $content = $_POST['content'] ?? '';

        // Extra structured data depending on page slug
        $extraData = [];

        if ($slug === 'contact') {
            $extraData = [
                'headline'         => sanitize($_POST['extra']['headline'] ?? 'Chat With Us'),
                'hq_title'         => sanitize($_POST['extra']['hq_title'] ?? 'Our Headquarters'),
                'hq_address'       => sanitize($_POST['extra']['hq_address'] ?? 'Mumbai, India'),
                'email_title'      => sanitize($_POST['extra']['email_title'] ?? 'Email Support'),
                'email'            => sanitizeEmail($_POST['extra']['email'] ?? ''),
                'phone_title'      => sanitize($_POST['extra']['phone_title'] ?? 'Call Us'),
                'phone'            => sanitize($_POST['extra']['phone'] ?? ''),
                'hours_title'      => sanitize($_POST['extra']['hours_title'] ?? 'Business Hours'),
                'hours'            => sanitize($_POST['extra']['hours'] ?? ''),
                'hours_enabled'    => isset($_POST['extra']['hours_enabled']) ? 1 : 0,
                'whatsapp_title'   => sanitize($_POST['extra']['whatsapp_title'] ?? 'Quick WhatsApp Chat'),
                'whatsapp_number'  => sanitize($_POST['extra']['whatsapp_number'] ?? ''),
                'whatsapp_enabled' => isset($_POST['extra']['whatsapp_enabled']) ? 1 : 0,
                'map_embed_url'    => trim($_POST['extra']['map_embed_url'] ?? ''),
                'map_enabled'      => isset($_POST['extra']['map_enabled']) ? 1 : 0,
                'form_title'       => sanitize($_POST['extra']['form_title'] ?? 'Send Message'),
                'form_subtitle'    => sanitize($_POST['extra']['form_subtitle'] ?? ''),
            ];
        } elseif ($slug === 'about') {
            $imageUrl = trim($_POST['extra']['image_url'] ?? 'uploads/cms/wapi-team.jpg');

            // Handle direct file upload from user computer
            if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] === UPLOAD_ERR_OK) {
                $uploadRes = uploadFile($_FILES['about_image'], 'cms', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                if ($uploadRes['success']) {
                    $imageUrl = $uploadRes['path'];
                } else {
                    setFlash('warning', 'Image upload issue: ' . $uploadRes['message']);
                }
            }

            $extraData = [
                'headline'      => sanitize($_POST['extra']['headline'] ?? 'Empowering Modern Business Communication'),
                'image_url'     => $imageUrl,
                'vision_title'  => sanitize($_POST['extra']['vision_title'] ?? 'Our Vision'),
                'vision_icon'   => sanitize($_POST['extra']['vision_icon'] ?? 'bi-eye-fill'),
                'vision_desc'   => sanitize($_POST['extra']['vision_desc'] ?? ''),
                'mission_title' => sanitize($_POST['extra']['mission_title'] ?? 'Our Mission'),
                'mission_icon'  => sanitize($_POST['extra']['mission_icon'] ?? 'bi-bullseye'),
                'mission_desc'  => sanitize($_POST['extra']['mission_desc'] ?? ''),
                'values_title'  => sanitize($_POST['extra']['values_title'] ?? 'Our Values'),
                'values_icon'   => sanitize($_POST['extra']['values_icon'] ?? 'bi-heart-fill'),
                'values_desc'   => sanitize($_POST['extra']['values_desc'] ?? ''),
            ];
        } elseif ($slug === 'terms') {
            $extraData = [
                'last_modified' => sanitize($_POST['extra']['last_modified'] ?? date('F Y')),
                'intro_notice'  => sanitize($_POST['extra']['intro_notice'] ?? ''),
            ];
        } elseif ($slug === 'privacy') {
            $extraData = [
                'effective_date' => sanitize($_POST['extra']['effective_date'] ?? date('F Y')),
            ];
        } elseif ($slug === 'cookies') {
            $extraData = [
                'effective_date' => sanitize($_POST['extra']['effective_date'] ?? date('F Y')),
            ];
        } elseif ($slug === 'gdpr') {
            $extraData = [
                'last_modified' => sanitize($_POST['extra']['last_modified'] ?? date('F Y')),
            ];
        } elseif ($slug === 'data-deletion') {
            $extraData = [
                'last_updated' => sanitize($_POST['extra']['last_updated'] ?? date('F d, Y')),
            ];
        }

        PageManager::savePage($slug, [
            'title'            => $title,
            'subtitle'         => $subtitle,
            'content'          => $content,
            'meta_title'       => $metaTitle,
            'meta_description' => $metaDescription,
            'extra_data'       => $extraData,
            'is_active'        => $isActive,
        ]);

        setFlash('success', "{$pageNames[$slug]['name']} page updated successfully!");
        redirect("admin/pages.php?page={$slug}");
    } elseif ($action === 'reset_default') {
        $slug = sanitize($_POST['slug'] ?? $activeSlug);
        if (in_array($slug, $validSlugs)) {
            PageManager::resetToDefault($slug);
            setFlash('success', "{$pageNames[$slug]['name']} reset to default template content.");
        }
        redirect("admin/pages.php?page={$slug}");
    }
}

// Fetch current page data
$pageData = PageManager::getPage($activeSlug);
$extra = $pageData['extra_data'] ?? [];

$pageTitle = 'Pages Customizer';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [
    asset('assets/js/admin.js'),
    'https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js'
];
include __DIR__ . '/../includes/header.php';
?>

<style>
.page-nav-pill {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    color: var(--text-muted);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}
.page-nav-pill:hover {
    color: var(--primary);
    border-color: var(--primary);
}
.page-nav-pill.active {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary);
}
.tox-tinymce {
    border: 1px solid var(--border-color, #e2e8f0) !important;
    border-radius: 10px !important;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.tox .tox-toolbar, .tox .tox-toolbar__overflow, .tox .tox-toolbar__primary {
    background-color: #f8fafc !important;
}
.tox .tox-menubar {
    background-color: #f1f5f9 !important;
    border-bottom: 1px solid #e2e8f0 !important;
}
</style>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="dash-title"><i class="bi bi-layout-text-window-reverse text-primary me-2"></i>Pages Customizer</h1>
                <div class="dash-breadcrumb">
                    <a href="<?= baseUrl('admin/'); ?>">Admin</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Pages</span>
                    <i class="bi bi-chevron-right"></i>
                    <span class="text-primary"><?= e($pageNames[$activeSlug]['name']); ?></span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= baseUrl($pageNames[$activeSlug]['file']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i> View Live Page
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#resetModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default
                </button>
                <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
            </div>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?> fade-in mb-4">
                <i class="bi bi-<?= $flash['type'] === 'success' ? 'check' : 'exclamation'; ?>-circle-fill me-1"></i> 
                <?= e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Page Switcher Tabs -->
        <div class="d-flex gap-2 flex-wrap mb-4">
            <?php foreach ($pageNames as $slug => $info): ?>
                <a href="?page=<?= $slug; ?>" class="page-nav-pill <?= $activeSlug === $slug ? 'active' : ''; ?>">
                    <i class="bi <?= $info['icon']; ?>"></i>
                    <span><?= $info['name']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <?= CSRF::tokenField(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="slug" value="<?= $activeSlug; ?>">

            <div class="row g-4">
                <div class="col-lg-8">

                    <!-- =======================================================
                         CONTACT US PAGE CUSTOMIZER
                    ======================================================= -->
                    <?php if ($activeSlug === 'contact'): ?>
                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-chat-dots-fill text-primary me-2"></i>Header & Hero Section</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Navigation / Page Title</label>
                                    <input type="text" name="title" class="form-control" value="<?= e($pageData['title']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Hero Main Headline</label>
                                    <input type="text" name="extra[headline]" class="form-control" value="<?= e($extra['headline'] ?? 'Chat With Us'); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Hero Subtitle / Description</label>
                                    <textarea name="subtitle" class="form-control" rows="2"><?= e($pageData['subtitle']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-card-checklist text-primary me-2"></i>Contact Information Cards</h5>
                            <div class="row g-3">
                                <!-- Headquarters -->
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Headquarters Card</label>
                                        <div class="mb-2">
                                            <small class="text-muted">Card Title:</small>
                                            <input type="text" name="extra[hq_title]" class="form-control form-control-sm" value="<?= e($extra['hq_title'] ?? 'Our Headquarters'); ?>">
                                        </div>
                                        <div>
                                            <small class="text-muted">Address / Location:</small>
                                            <input type="text" name="extra[hq_address]" class="form-control form-control-sm" value="<?= e($extra['hq_address'] ?? 'Mumbai, India'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold"><i class="bi bi-envelope-fill text-primary me-1"></i> Email Support Card</label>
                                        <div class="mb-2">
                                            <small class="text-muted">Card Title:</small>
                                            <input type="text" name="extra[email_title]" class="form-control form-control-sm" value="<?= e($extra['email_title'] ?? 'Email Support'); ?>">
                                        </div>
                                        <div>
                                            <small class="text-muted">Email Address:</small>
                                            <input type="email" name="extra[email]" class="form-control form-control-sm" value="<?= e($extra['email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Phone -->
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <label class="form-label fw-bold"><i class="bi bi-telephone-fill text-success me-1"></i> Phone Support Card</label>
                                        <div class="mb-2">
                                            <small class="text-muted">Card Title:</small>
                                            <input type="text" name="extra[phone_title]" class="form-control form-control-sm" value="<?= e($extra['phone_title'] ?? 'Call Us'); ?>">
                                        </div>
                                        <div>
                                            <small class="text-muted">Phone / Mobile:</small>
                                            <input type="text" name="extra[phone]" class="form-control form-control-sm" value="<?= e($extra['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Business Hours -->
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-bold mb-0"><i class="bi bi-clock-fill text-warning me-1"></i> Business Hours Card</label>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="extra[hours_enabled]" id="hoursToggle" <?= !empty($extra['hours_enabled']) ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Card Title:</small>
                                            <input type="text" name="extra[hours_title]" class="form-control form-control-sm" value="<?= e($extra['hours_title'] ?? 'Business Hours'); ?>">
                                        </div>
                                        <div>
                                            <small class="text-muted">Timings Text:</small>
                                            <input type="text" name="extra[hours]" class="form-control form-control-sm" value="<?= e($extra['hours'] ?? 'Mon - Sat: 9:00 AM - 7:00 PM IST'); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- WhatsApp Quick Chat -->
                                <div class="col-md-12">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold mb-0"><i class="bi bi-whatsapp text-success me-1"></i> WhatsApp Quick Chat Button</label>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="extra[whatsapp_enabled]" id="waToggle" <?= !empty($extra['whatsapp_enabled']) ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <input type="text" name="extra[whatsapp_title]" class="form-control form-control-sm" value="<?= e($extra['whatsapp_title'] ?? 'Chat on WhatsApp'); ?>" placeholder="Button Label">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="extra[whatsapp_number]" class="form-control form-control-sm" value="<?= e($extra['whatsapp_number'] ?? ''); ?>" placeholder="WhatsApp Number with country code (+91...)">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Interactive Google Map Embed -->
                                <div class="col-md-12">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold mb-0"><i class="bi bi-map-fill text-info me-1"></i> Interactive Google Map</label>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" name="extra[map_enabled]" id="mapToggle" <?= !empty($extra['map_enabled']) ? 'checked' : ''; ?>>
                                            </div>
                                        </div>
                                        <input type="text" name="extra[map_embed_url]" class="form-control form-control-sm" value="<?= e($extra['map_embed_url'] ?? ''); ?>" placeholder="Google Maps Embed URL (https://www.google.com/maps/embed?pb=...)">
                                        <small class="text-muted d-block mt-1">Google Maps me Share &rarr; Embed a map &rarr; src URL copy karke yahan paste karein.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-ui-checks text-primary me-2"></i>Contact Form Settings</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Form Title</label>
                                    <input type="text" name="extra[form_title]" class="form-control" value="<?= e($extra['form_title'] ?? 'Send Message'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Form Subtitle / Instruction</label>
                                    <input type="text" name="extra[form_subtitle]" class="form-control" value="<?= e($extra['form_subtitle'] ?? ''); ?>" placeholder="e.g. We will get back to you within 24 hours">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =======================================================
                         ABOUT US PAGE CUSTOMIZER
                    ======================================================= -->
                    <?php elseif ($activeSlug === 'about'): ?>
                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-building text-primary me-2"></i>About Us Story & Hero</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Page Title</label>
                                    <input type="text" name="title" class="form-control" value="<?= e($pageData['title']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Hero Main Headline</label>
                                    <input type="text" name="extra[headline]" class="form-control" value="<?= e($extra['headline'] ?? 'Empowering Modern Business Communication'); ?>" required>
                                </div>
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label class="form-label fw-bold mb-2"><i class="bi bi-image text-primary me-1"></i> WAPI Team / Hero Image</label>
                                        
                                        <div class="row align-items-center g-3">
                                            <div class="col-md-5">
                                                <div class="position-relative border rounded-3 overflow-hidden bg-white text-center p-2" style="background: #f1f5f9;">
                                                    <?php 
                                                    $currentImg = !empty($extra['image_url']) ? $extra['image_url'] : 'uploads/cms/wapi-team.jpg';
                                                    $currentImgSrc = (strpos($currentImg, 'http') === 0) ? $currentImg : baseUrl(ltrim($currentImg, '/'));
                                                    ?>
                                                    <img id="teamImgPreview" src="<?= e($currentImgSrc); ?>" alt="WAPI Team Preview" class="img-fluid rounded" style="max-height: 180px; width: 100%; object-fit: cover;" onerror="this.src='https://placehold.co/600x400/6366f1/white?text=WAPI+Team'">
                                                    <div class="small text-muted mt-2" id="imgStatusText">Current Team Image</div>
                                                </div>
                                            </div>
                                            <div class="col-md-7">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold mb-1"><i class="bi bi-upload text-success me-1"></i> Upload New Team Image</label>
                                                    <input type="file" name="about_image" id="aboutImgFile" class="form-control" accept="image/png, image/jpeg, image/webp, image/gif, image/svg+xml" onchange="previewTeamImage(this)">
                                                    <small class="text-muted d-block mt-1">Computer se image choose karein (JPG, PNG, WEBP - Max 10MB)</small>
                                                </div>
                                                <div>
                                                    <label class="form-label fw-semibold mb-1"><i class="bi bi-link-45deg text-secondary me-1"></i> Or Custom Image URL / Path</label>
                                                    <input type="text" name="extra[image_url]" id="aboutImgUrl" class="form-control form-control-sm" value="<?= e($extra['image_url'] ?? 'uploads/cms/wapi-team.jpg'); ?>" oninput="updatePreviewFromUrl(this.value)">
                                                    <small class="text-muted">Direct external URL ya path (e.g. <code>uploads/cms/wapi-team.jpg</code>)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-semibold mb-0">Company Story / Description (Visual Editor)</label>
                                        <small class="text-muted"><i class="bi bi-magic me-1"></i> Visual WYSIWYG Mode</small>
                                    </div>
                                    <textarea name="content" id="contentArea" class="form-control" rows="8"><?= e($pageData['content']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-gem text-primary me-2"></i>Vision, Mission & Values Cards</h5>
                            <div class="row g-4">
                                <!-- Vision -->
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <h6 class="fw-bold mb-2"><i class="bi bi-eye-fill text-primary me-1"></i> Vision Card</h6>
                                        <div class="mb-2">
                                            <small class="text-muted">Title:</small>
                                            <input type="text" name="extra[vision_title]" class="form-control form-control-sm" value="<?= e($extra['vision_title'] ?? 'Our Vision'); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Bootstrap Icon:</small>
                                            <input type="text" name="extra[vision_icon]" class="form-control form-control-sm" value="<?= e($extra['vision_icon'] ?? 'bi-eye-fill'); ?>">
                                        </div>
                                        <div>
                                            <small class="text-muted">Description:</small>
                                            <textarea name="extra[vision_desc]" class="form-control form-control-sm" rows="3"><?= e($extra['vision_desc'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mission -->
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <h6 class="fw-bold mb-2"><i class="bi bi-bullseye text-danger me-1"></i> Mission Card</h6>
                                        <div class="mb-2">
                                            <small class="text-muted">Title:</small>
                                            <input type="text" name="extra[mission_title]" class="form-control form-control-sm" value="<?= e($extra['mission_title'] ?? 'Our Mission'); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Bootstrap Icon:</small>
                                            <input type="text" name="extra[mission_icon]" class="form-control form-control-sm" value="<?= e($extra['mission_icon'] ?? 'bi-bullseye'); ?>">
                                        </div>
                                        <div>
                                            <small class="text-muted">Description:</small>
                                            <textarea name="extra[mission_desc]" class="form-control form-control-sm" rows="3"><?= e($extra['mission_desc'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Values -->
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded-3 border h-100">
                                        <h6 class="fw-bold mb-2"><i class="bi bi-heart-fill text-danger me-1"></i> Values Card</h6>
                                        <div class="mb-2">
                                            <small class="text-muted">Title:</small>
                                            <input type="text" name="extra[values_title]" class="form-control form-control-sm" value="<?= e($extra['values_title'] ?? 'Our Values'); ?>">
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Bootstrap Icon:</small>
                                            <input type="text" name="extra[values_icon]" class="form-control form-control-sm" value="<?= e($extra['values_icon'] ?? 'bi-heart-fill'); ?>">
                                        </div>
                                        <div>
                                            <small class="text-muted">Description:</small>
                                            <textarea name="extra[values_desc]" class="form-control form-control-sm" rows="3"><?= e($extra['values_desc'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =======================================================
                         LEGAL / POLICY PAGES (PRIVACY, TERMS, COOKIES, GDPR, DATA DELETION)
                    ======================================================= -->
                    <?php else: ?>
                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-text text-primary me-2"></i>Header & Dates</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Page Title</label>
                                    <input type="text" name="title" class="form-control" value="<?= e($pageData['title']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold"><?= ($activeSlug === 'data-deletion') ? 'Last Updated Date' : (($activeSlug === 'terms' || $activeSlug === 'gdpr') ? 'Last Modified Date' : 'Effective Date'); ?></label>
                                    <input type="text" name="extra[<?= in_array($activeSlug, ['terms', 'gdpr']) ? 'last_modified' : ($activeSlug === 'data-deletion' ? 'last_updated' : 'effective_date'); ?>]" class="form-control" value="<?= e($extra['effective_date'] ?? ($extra['last_modified'] ?? ($extra['last_updated'] ?? date('F Y')))); ?>">
                                </div>
                                <?php if ($activeSlug === 'data-deletion'): ?>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Subtitle / Overview Description</label>
                                    <input type="text" name="subtitle" class="form-control" value="<?= e($pageData['subtitle'] ?? ''); ?>" placeholder="Learn how to manage and delete your data from WAPI">
                                </div>
                                <?php elseif ($activeSlug === 'terms'): ?>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Intro Notice Banner Text</label>
                                    <input type="text" name="extra[intro_notice]" class="form-control" value="<?= e($extra['intro_notice'] ?? ''); ?>" placeholder="By using WAPI, you agree to these terms...">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h5 class="fw-bold mb-1"><i class="bi bi-file-earmark-richtext text-primary me-2"></i>Policy Content (Visual Word-Style Editor)</h5>
                                    <small class="text-muted">Aapko koi HTML tags dekhne ya likhne ki zaroorat nahi hai. Microsoft Word ya Google Docs ki tarah directly format karein.</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openPreviewModal()">
                                        <i class="bi bi-eye me-1"></i> Live Preview
                                    </button>
                                </div>
                            </div>

                            <textarea name="content" id="contentArea" class="form-control" rows="18"><?= e($pageData['content']); ?></textarea>
                            <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                                <small class="text-muted">
                                    <i class="bi bi-check2-circle text-success me-1"></i> <strong>Visual WYSIWYG Mode:</strong> Normal typing aur formatting karein.
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-code-slash me-1"></i> Agar direct HTML code dekhna ho to toolbar me <strong>&lt;&gt;</strong> (Code) icon click karein.
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary px-4 py-2"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                </div>

                <!-- Right Sidebar: SEO & Status -->
                <div class="col-lg-4">
                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-toggle-on text-success me-2"></i>Publish Status</h5>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= !empty($pageData['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-semibold" for="isActive">Page Active & Visible</label>
                            </div>
                            <div class="p-3 bg-light rounded-3 border small text-muted">
                                <div><strong>Target URL:</strong> <code><?= baseUrl($pageNames[$activeSlug]['file']); ?></code></div>
                                <div class="mt-1"><strong>Last Saved:</strong> <?= $pageData['updated_at'] ? timeAgo($pageData['updated_at']) : 'Never'; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><i class="bi bi-search text-warning me-2"></i>SEO Meta Tags</h5>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" value="<?= e($pageData['meta_title']); ?>" placeholder="<?= e($pageNames[$activeSlug]['name']); ?> | WAPI">
                                <small class="text-muted">Browser tab title & Google Search title.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="3" placeholder="Page description for search engines..."><?= e($pageData['meta_description']); ?></textarea>
                                <small class="text-muted">Google search snippet (recommend 140-160 chars).</small>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="border-radius: var(--border-radius);">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle text-info me-1"></i> Quick Tips</h6>
                            <ul class="text-muted ps-3 mb-0 small" style="line-height: 1.6;">
                                <li>Changes save instantly and update the live website immediately.</li>
                                <li>Contact details sync with both the Contact Us page and footer.</li>
                                <li>Agar kabhi default layout wapas chahiye to <strong>Reset to Default</strong> use karein.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Reset to Default?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Kya aap sach me <strong><?= e($pageNames[$activeSlug]['name']); ?></strong> ko default template content me reset karna chahte hain?</p>
                <p class="text-muted small mb-0">Aapka current customized content default data se replace ho jayega.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST">
                    <?= CSRF::tokenField(); ?>
                    <input type="hidden" name="action" value="reset_default">
                    <input type="hidden" name="slug" value="<?= $activeSlug; ?>">
                    <button type="submit" class="btn btn-danger">Yes, Reset to Default</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Live Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2 text-primary"></i>Live Content Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="previewBody" style="font-family: inherit; line-height: 1.7;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize TinyMCE for Visual Word-style editing
if (typeof tinymce !== 'undefined') {
    tinymce.init({
        selector: '#contentArea',
        height: <?= ($activeSlug === 'about') ? '340' : '580'; ?>,
        menubar: 'edit view insert format tools table',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'table', 'wordcount'
        ],
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link table blockquote | code fullscreen preview',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 15px; line-height: 1.8; color: #374151; padding: 20px; } h1, h2, h3, h4, h5, h6 { font-weight: 700; color: #111827; margin-top: 1.5rem; margin-bottom: 0.75rem; } p { margin-bottom: 1rem; color: #4b5563; } ul, ol { padding-left: 1.5rem; margin-bottom: 1rem; } li { margin-bottom: 0.5rem; color: #4b5563; } a { color: #4f46e5; text-decoration: underline; }',
        branding: false,
        promotion: false,
        elementpath: true,
        setup: function(editor) {
            editor.on('change keyup NodeChange', function() {
                editor.save();
            });
        }
    });
}

// Ensure TinyMCE saves to textarea on form submit
const editForm = document.querySelector('form');
if (editForm) {
    editForm.addEventListener('submit', function() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
    });
}

function openPreviewModal() {
    if (typeof tinymce !== 'undefined' && tinymce.get('contentArea')) {
        tinymce.triggerSave();
    }
    const el = document.getElementById('contentArea');
    if (!el) return;
    document.getElementById('previewBody').innerHTML = el.value;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function previewTeamImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('teamImgPreview');
            if (preview) {
                preview.src = e.target.result;
            }
            const status = document.getElementById('imgStatusText');
            if (status) {
                status.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> New image selected! (Click "Save Changes" to save)</span>';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updatePreviewFromUrl(url) {
    if (url && url.trim().length > 0) {
        const preview = document.getElementById('teamImgPreview');
        if (preview) {
            preview.src = (url.indexOf('http') === 0) ? url : '<?= baseUrl(); ?>/' + url.replace(/^\/+/, '');
        }
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
