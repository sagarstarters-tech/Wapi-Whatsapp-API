<?php
/**
 * WAPI SaaS - Admin Email / SMTP Settings
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireAdmin();

$db = Database::getInstance();
$settings = new Settings();

$hideNav = true; // Prevents landing page nav from appearing in admin

if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validateToken()) {
    $action = sanitize($_POST['action'] ?? 'save');

    if ($action === 'save') {
        $fields = ['email_driver','smtp_host','smtp_port','smtp_encryption','smtp_username','smtp_password','smtp_from_name','smtp_from_email'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $val = trim($_POST[$f]);
                // Password field: don't overwrite with empty if existing password is saved
                if ($f === 'smtp_password' && empty($val)) {
                    continue;
                }
                $settings->set($f, sanitize($val));
            }
        }
        if (isset($_POST['contact_email'])) {
            $cEmail = sanitizeEmail($_POST['contact_email']);
            if (!empty($cEmail)) {
                $settings->set('contact_email', $cEmail, 'general', 'text');
            }
        }
        setFlash('success', 'Email settings saved successfully!');
    } elseif ($action === 'test') {
        $testEmail = sanitizeEmail($_POST['test_email'] ?? '');
        if ($testEmail) {
            $subject = 'WAPI - SMTP Test Email';
            $body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
                <h3 style="color: #6c63ff; margin-top: 0;">🎉 WAPI Email System Working!</h3>
                <p>Hello Admin,</p>
                <p>This is a test notification confirming that your WAPI email delivery system is configured and working properly.</p>
                <div style="background: #f8fafc; padding: 12px 16px; border-left: 4px solid #6c63ff; border-radius: 4px; font-size: 13px; color: #475569;">
                    <strong>Driver:</strong> ' . htmlspecialchars($settings->get('email_driver', 'auto')) . '<br>
                    <strong>Timestamp:</strong> ' . date('d M Y, h:i:s A') . '
                </div>
            </div>';
            
            $result = Mail::send($testEmail, $subject, $body);
            
            if ($result['success']) {
                setFlash('success', "Test email sent successfully to {$testEmail}! (" . $result['message'] . ")");
            } else {
                setFlash('danger', $result['message']);
            }
        }
    }
    redirect('admin/email-settings.php');
}

$pageTitle = 'Email / SMTP Settings';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Email / SMTP Settings</h1>
                <div class="dash-breadcrumb"><a href="<?= baseUrl('admin/'); ?>">Admin</a><i class="bi bi-chevron-right"></i><span>Email</span></div>
            </div>
            <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?> fade-in"><i class="bi bi-<?= $flash['type'] === 'success' ? 'check' : 'exclamation'; ?>-circle-fill"></i> <?= e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card" style="border-radius: var(--border-radius);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="bi bi-envelope text-primary"></i> SMTP & Notification Configuration</h5>
                        <form method="POST">
                            <?= CSRF::tokenField(); ?>
                            <input type="hidden" name="action" value="save">
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <label class="form-label fw-bold mb-1"><i class="bi bi-bell-fill text-warning me-1"></i> Contact Us Notifications Recipient Email</label>
                                        <input type="email" name="contact_email" class="form-control" value="<?= e($settings->get('contact_email', defined('SMTP_USER') ? SMTP_USER : '')); ?>" placeholder="wapiwhatsappapi@gmail.com" required>
                                        <small class="text-muted d-block mt-1">Website contact form (<code>contact.php</code>) par customer ke messages is email address par deliver honge.</small>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Mail Dispatch Driver</label>
                                    <select name="email_driver" class="form-control">
                                        <option value="smtp" <?= $settings->get('email_driver', 'smtp') === 'smtp' ? 'selected' : ''; ?>>SMTP (Gmail, Google Workspace, Hostinger, cPanel)</option>
                                        <option value="mail" <?= $settings->get('email_driver') === 'mail' ? 'selected' : ''; ?>>PHP Mail (Server Default Sendmail / Postfix)</option>
                                    </select>
                                    <small class="text-muted">SMTP is recommended for reliable inbox delivery. Agar SMTP fail hota hai to system automatically PHP mail fallback karega.</small>
                                </div>

                                <div class="col-md-8">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" value="<?= e($settings->get('smtp_host', defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com')); ?>" placeholder="smtp.gmail.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Port</label>
                                    <input type="number" name="smtp_port" class="form-control" value="<?= e($settings->get('smtp_port', defined('SMTP_PORT') ? SMTP_PORT : '587')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Encryption</label>
                                    <select name="smtp_encryption" class="form-control">
                                        <option value="tls" <?= $settings->get('smtp_encryption', defined('SMTP_SECURE') ? SMTP_SECURE : 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Port 587)</option>
                                        <option value="ssl" <?= $settings->get('smtp_encryption', defined('SMTP_SECURE') ? SMTP_SECURE : 'tls') === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                        <option value="none" <?= $settings->get('smtp_encryption') === 'none' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" name="smtp_username" class="form-control" value="<?= e($settings->get('smtp_username', defined('SMTP_USER') ? SMTP_USER : '')); ?>" placeholder="wapiwhatsappapi@gmail.com">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">SMTP Password / Google App Password</label>
                                    <input type="password" name="smtp_password" class="form-control" placeholder="<?= !empty($settings->get('smtp_password')) || (defined('SMTP_PASS') && !empty(SMTP_PASS)) ? '•••••••••••••••• (Leave blank to keep existing)' : 'Enter 16-char Google App Password'; ?>">
                                    <small class="text-muted">Google account ke liye 16-akshar ka App Password use karein.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">From Name</label>
                                    <input type="text" name="smtp_from_name" class="form-control" value="<?= e($settings->get('smtp_from_name', defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Wapi Support')); ?>" placeholder="WAPI Platform">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">From Email</label>
                                    <input type="email" name="smtp_from_email" class="form-control" value="<?= e($settings->get('smtp_from_email', defined('SMTP_USER') ? SMTP_USER : '')); ?>" placeholder="wapiwhatsappapi@gmail.com">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-4"><i class="bi bi-check-lg"></i> Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card" style="border-radius: var(--border-radius);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-send-check text-success"></i> Send Test Email</h5>
                        <p class="text-muted" style="font-size: 0.8125rem;">Test karein ki email notification successfully deliver ho raha hai ya nahi.</p>
                        <form method="POST">
                            <?= CSRF::tokenField(); ?>
                            <input type="hidden" name="action" value="test">
                            <div class="form-group mb-3">
                                <label class="form-label">Recipient Email</label>
                                <input type="email" name="test_email" class="form-control" value="<?= e($settings->get('contact_email', defined('SMTP_USER') ? SMTP_USER : '')); ?>" placeholder="admin@example.com" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-send me-1"></i> Send Test Email</button>
                        </form>
                    </div>
                </div>

                <div class="card mt-4" style="border-radius: var(--border-radius);">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-google text-danger"></i> Gmail Setup Guide</h5>
                        <div style="font-size: 0.8125rem; line-height: 1.6;">
                            <p class="mb-2">Gmail account use karne ke liye normal password nahi balki <strong>Google App Password</strong> chahiye:</p>
                            <ol class="ps-3 mb-3">
                                <li>Google Account me <strong>2-Step Verification</strong> ON karein.</li>
                                <li><a href="https://myaccount.google.com/apppasswords" target="_blank" class="fw-bold text-decoration-none">Google App Passwords <i class="bi bi-box-arrow-up-right"></i></a> page open karein.</li>
                                <li>App me <strong>Mail</strong> aur Device me <strong>Other (WAPI)</strong> choose karke <em>Generate</em> karein.</li>
                                <li>Google jo 16-akshar ka code dikhayega, use copy karke <strong>Password</strong> field me paste karein.</li>
                            </ol>
                            <div class="p-2 bg-light rounded border">
                                <strong>Gmail Defaults:</strong><br>
                                Host: <code>smtp.gmail.com</code><br>
                                Port: <code>587</code> (TLS) ya <code>465</code> (SSL)<br>
                                Username: Aapka full Gmail ID
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
