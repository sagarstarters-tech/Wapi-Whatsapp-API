<?php
/**
 * WAPI SaaS - WhatsApp Connect Page
 * Easy integration steps for Meta WhatsApp Cloud API
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$settings = new Settings();
$userId = $_SESSION['user_id'];

$hideNav = true; // Prevents landing page nav from appearing in dashboard

// Get existing WhatsApp account
$waAccount = $db->fetch("SELECT * FROM whatsapp_accounts WHERE user_id = ? LIMIT 1", [$userId]);
$isConnected = !empty($waAccount['phone_number_id']) && !empty($waAccount['access_token']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validateToken()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'connect') {
        $phone_number_id = sanitize($_POST['phone_number_id'] ?? '');
        $waba_id = sanitize($_POST['waba_id'] ?? '');
        $access_token = trim($_POST['access_token'] ?? '');
        $business_name = sanitize($_POST['business_name'] ?? 'My WhatsApp App');

        if (empty($phone_number_id) || empty($access_token)) {
            setFlash('danger', 'Phone Number ID and Access Token are required!');
        } else {
            $data = [
                'user_id' => $userId,
                'phone_number_id' => $phone_number_id,
                'waba_id' => $waba_id,
                'access_token' => $access_token,
                'business_name' => $business_name,
                'status' => 'active'
            ];

            if ($waAccount) {
                $db->update('whatsapp_accounts', $data, 'id = ?', [$waAccount['id']]);
            } else {
                $db->insert('whatsapp_accounts', $data);
            }
            
            setFlash('success', 'WhatsApp successfully connected! You can now send and receive messages.');
            redirect('dashboard/whatsapp.php');
        }
    }

    if ($action === 'disconnect') {
        if ($waAccount) {
            $db->delete('whatsapp_accounts', 'id = ?', [$waAccount['id']]);
            setFlash('success', 'WhatsApp account disconnected.');
        }
        redirect('dashboard/whatsapp.php');
    }
}

$pageTitle = 'Connect WhatsApp';
$extraCss = [asset('assets/css/dashboard.css')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header mb-4">
            <div>
                <h1 class="dash-title">Connect to WhatsApp API <i class="bi bi-whatsapp text-success"></i></h1>
                <div class="dash-breadcrumb"><a href="<?= baseUrl('dashboard/'); ?>">Dashboard</a><i class="bi bi-chevron-right"></i><span>WhatsApp Setup</span></div>
            </div>
        </div>

        <!-- Connection Method Tabs -->
        <ul class="nav nav-tabs mb-4" id="connectMethodTabs" role="tablist" style="border-bottom: 2px solid #e3e8f0;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="cloudapi-tab" data-bs-toggle="tab" data-bs-target="#cloudapi" type="button" role="tab" aria-controls="cloudapi" aria-selected="true">
                    <i class="bi bi-cloud-fill me-1 text-primary"></i> Cloud API
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="qrscan-tab" data-bs-toggle="tab" data-bs-target="#qrscan" type="button" role="tab" aria-controls="qrscan" aria-selected="false">
                    <i class="bi bi-qr-code me-1 text-success"></i> QR Code Scan
                </button>
            </li>
        </ul>

        <div class="tab-content" id="connectMethodContent">
        <!-- ═══════════ TAB 1: Cloud API (Existing - Untouched) ═══════════ -->
        <div class="tab-pane fade show active" id="cloudapi" role="tabpanel" aria-labelledby="cloudapi-tab">

        <?php if ($isConnected): ?>
        
        <!-- Connected Status -->
        <div class="card mb-4 border-success">
            <div class="card-body d-flex align-items-center justify-content-between p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-success">WhatsApp Connected!</h5>
                        <p class="mb-0 text-muted">Your Meta WhatsApp App is active and ready to send messages.</p>
                    </div>
                </div>
                <div>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to disconnect? All active bots and scheduled messages will fail.')">
                        <?= CSRF::tokenField(); ?>
                        <input type="hidden" name="action" value="disconnect">
                        <button type="submit" class="btn btn-outline-danger"><i class="bi bi-plugin"></i> Disconnect</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Connection Details -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary"></i> App Details</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span class="text-muted">Business Name</span>
                                <strong><?= e($waAccount['business_name']); ?></strong>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span class="text-muted">Phone Number ID</span>
                                <strong><?= e($waAccount['phone_number_id']); ?></strong>
                            </li>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span class="text-muted">WABA ID</span>
                                <strong><?= e($waAccount['waba_id'] ?: 'N/A'); ?></strong>
                            </li>
                            <li class="list-group-item px-0">
                                <span class="text-muted d-block mb-1">Access Token</span>
                                <input type="password" class="form-control form-control-sm bg-light" value="<?= e($waAccount['access_token']); ?>" readonly>
                            </li>
                        </ul>
                        <button class="btn btn-primary btn-sm mt-3 w-100" onclick="document.getElementById('editSetup').style.display='block'; window.scrollTo(0, document.body.scrollHeight);"><i class="bi bi-pencil"></i> Edit Details</button>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card h-100 bg-primary bg-opacity-10 border-primary">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-diagram-3-fill"></i> Webhook Configuration</h6>
                        <p class="text-secondary small mb-3">To receive incoming messages and delivery status updates, copy the URL below and paste it into finding <strong>Webhooks</strong> section of your Meta App.</p>
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Callback URL (Webhook URL)</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-white" id="webhookUrl" value="<?= APP_URL; ?>/api/webhook.php" readonly>
                                <button class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('webhookUrl').value); alert('Copied!')"><i class="bi bi-copy"></i> Copy</button>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label text-muted small fw-bold">Verify Token</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-white" id="verifyToken" value="<?= e($settings->get('webhook_verify_token', 'wapi_verify_token')); ?>" readonly>
                                <button class="btn btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('verifyToken').value); alert('Copied!')"><i class="bi bi-copy"></i> Copy</button>
                            </div>
                            <small class="text-muted d-block mt-2">Subscribe to the `messages` fields in Meta Webhooks.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- Setup Form (Always shown if disconnected, hidden initially if connected) -->
        <div id="editSetup" style="<?= $isConnected ? 'display:none; margin-top: 2rem;' : '' ?>">
            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-body p-4 p-lg-5">
                            <h4 class="fw-bold mb-1"><?= $isConnected ? 'Update Cloud API Credentials' : 'Link Your WhatsApp Cloud API' ?></h4>
                            <p class="text-muted mb-4">Follow the official Meta guides to generate these API credentials. You must use a permanent token to prevent connection loss.</p>
                            
                            <form method="POST">
                                <?= CSRF::tokenField(); ?>
                                <input type="hidden" name="action" value="connect">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">App / Business Name</label>
                                    <input type="text" name="business_name" class="form-control form-control-lg" required placeholder="e.g. My Awesome Store" value="<?= e($waAccount['business_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number ID <span class="text-danger">*</span></label>
                                    <input type="text" name="phone_number_id" class="form-control form-control-lg" required placeholder="e.g. 10456123987123" value="<?= e($waAccount['phone_number_id'] ?? ''); ?>">
                                    <div class="form-text">Found in Meta Developer > WhatsApp > API Setup > Phone number ID.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">WhatsApp Business Account ID (WABA ID)</label>
                                    <input type="text" name="waba_id" class="form-control form-control-lg" placeholder="e.g. 109871234761234" value="<?= e($waAccount['waba_id'] ?? ''); ?>">
                                    <div class="form-text">Found right below the Phone Number ID.</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Permanent Access Token <span class="text-danger">*</span></label>
                                    <textarea name="access_token" rows="4" class="form-control" required placeholder="EAALx... (Make sure to generate a permanent System User token, NOT a temporary 24-hr token)"><?= e($waAccount['access_token'] ?? ''); ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
                                    <i class="bi bi-link-45deg me-1"></i> <?= $isConnected ? 'Update Connection' : 'Connect WhatsApp API' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Helper Column -->
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="card bg-light border-0">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4"><i class="bi bi-lightbulb-fill text-warning me-2"></i> How to get these?</h5>
                            
                            <div class="d-flex mb-4">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px; font-weight: bold;">1</div>
                                <div class="ms-3">
                                    <h6 class="fw-bold mb-1">Create a Meta App</h6>
                                    <p class="text-muted small mb-0">Go to <a href="https://developers.facebook.com/" target="_blank">Meta Developer</a> > My Apps, create a "Business" app, and add WhatsApp.</p>
                                </div>
                            </div>

                            <div class="d-flex mb-4">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px; font-weight: bold;">2</div>
                                <div class="ms-3">
                                    <h6 class="fw-bold mb-1">Get IDs</h6>
                                    <p class="text-muted small mb-0">In your App Dashboard, go to <strong>WhatsApp > API Setup</strong> to copy your Phone Number ID and WABA ID.</p>
                                </div>
                            </div>

                            <div class="d-flex">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px; font-weight: bold;">3</div>
                                <div class="ms-3">
                                    <h6 class="fw-bold mb-1">Generate Permanent Token</h6>
                                    <p class="text-muted small mb-0">Go to Business Manager > System Users. Assign your app, and generate a token with `whatsapp_business_messaging` and `whatsapp_business_management` permissions.</p>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-top">
                                <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-journal-text me-1"></i> Read Official Meta Guide</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div><!-- /cloudapi tab-pane -->

        <!-- ═══════════ TAB 2: QR Code Scan (New) ═══════════ -->
        <div class="tab-pane fade" id="qrscan" role="tabpanel" aria-labelledby="qrscan-tab">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="card border-0" style="border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
                        <div class="card-body p-4 p-lg-5 text-center">

                            <!-- Initial / Scanning State -->
                            <div id="qrSection">
                                <div class="mb-4">
                                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width:70px;height:70px;">
                                        <i class="bi bi-qr-code-scan text-success" style="font-size:2rem;"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold mb-2">Connect via QR Code</h4>
                                <p class="text-muted mb-4">Scan the QR code with your WhatsApp app to link your account instantly.</p>

                                <!-- QR Display Area -->
                                <div id="qrDisplay" style="min-height:300px;" class="d-flex flex-column align-items-center justify-content-center mb-4">
                                    <button id="startQrBtn" class="btn btn-success btn-lg px-5 py-3" onclick="startQrSession()" style="border-radius:12px; font-weight:600;">
                                        <i class="bi bi-qr-code me-2"></i> Generate QR Code
                                    </button>
                                </div>

                                <div id="qrStatus" class="mb-3"></div>

                                <!-- How-to Instructions -->
                                <div class="text-start mt-4 p-3 bg-light rounded-3">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-1"></i> How to scan</h6>
                                    <ol class="small text-muted mb-0" style="line-height:1.8;">
                                        <li>Click <strong>"Generate QR Code"</strong> above</li>
                                        <li>Open <strong>WhatsApp</strong> on your phone</li>
                                        <li>Go to <strong>Settings → Linked Devices → Link a Device</strong></li>
                                        <li>Point your phone camera at the QR code displayed here</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- Connected State (hidden by default) -->
                            <div id="qrConnected" style="display:none;">
                                <div class="mb-4">
                                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width:70px;height:70px;">
                                        <i class="bi bi-check-circle-fill" style="font-size:2rem;"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold text-success mb-2">WhatsApp Connected via QR!</h4>
                                <p id="qrConnectedInfo" class="text-muted mb-4"></p>
                                <button class="btn btn-outline-danger px-4" onclick="disconnectQrSession()">
                                    <i class="bi bi-x-circle me-1"></i> Disconnect QR Session
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- Service Note -->
                    <div class="card mt-3 border-0" style="background:linear-gradient(135deg, #fff8e1 0%, #fff3cd 100%); border-radius:12px;">
                        <div class="card-body p-3">
                            <small style="color:#856404;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>Note:</strong> QR Code connection requires the WhatsApp QR Service to be running on your server.
                                Start it via terminal: <code style="background:rgba(0,0,0,0.08);padding:2px 6px;border-radius:4px;">node services/whatsapp-qr/server.js</code>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Right Column: How it works -->
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="card bg-light border-0 h-100" style="border-radius:16px;">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-4"><i class="bi bi-lightning-fill text-warning me-2"></i> QR vs Cloud API</h5>

                            <div class="mb-4">
                                <h6 class="fw-bold text-success mb-2"><i class="bi bi-qr-code me-1"></i> QR Code Scan</h6>
                                <ul class="small text-muted mb-0" style="line-height:1.9;">
                                    <li>Connect instantly — no Meta Developer setup</li>
                                    <li>Works like WhatsApp Web</li>
                                    <li>Phone must stay online & connected</li>
                                    <li>Best for quick testing & personal use</li>
                                </ul>
                            </div>

                            <div class="mb-4">
                                <h6 class="fw-bold text-primary mb-2"><i class="bi bi-cloud-fill me-1"></i> Cloud API</h6>
                                <ul class="small text-muted mb-0" style="line-height:1.9;">
                                    <li>Official Meta Business API</li>
                                    <li>No phone dependency — fully server-side</li>
                                    <li>Supports templates, webhooks & bots</li>
                                    <li>Best for production & business use</li>
                                </ul>
                            </div>

                            <div class="p-3 rounded-3" style="background:rgba(37,211,102,0.1); border: 1px solid rgba(37,211,102,0.2);">
                                <small class="text-success fw-semibold">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Tip: For production, always prefer Cloud API. Use QR Scan for quick demos or personal testing.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /qrscan tab-pane -->

        </div><!-- /tab-content -->

    </main>
</div>

<!-- QR Session JavaScript -->
<script>
const QR_API = '../api/whatsapp-qr.php';
let qrPollTimer = null;

async function startQrSession() {
    const display = document.getElementById('qrDisplay');
    const statusEl = document.getElementById('qrStatus');

    display.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-success mb-3" role="status" style="width:3rem;height:3rem;"></div>
            <p class="text-muted fw-semibold">Starting WhatsApp session...<br><small>This may take 10-20 seconds</small></p>
        </div>`;
    statusEl.innerHTML = '';

    try {
        const res = await fetch(QR_API + '?action=start', { method: 'POST' });
        const data = await res.json();

        if (data.service_down) {
            display.innerHTML = `
                <div class="alert alert-danger text-start" style="border-radius:12px;">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i>
                    <strong>QR Service is not running!</strong><br>
                    <small>Start it in terminal: <code>node services/whatsapp-qr/server.js</code></small>
                    <p class="mt-2 mb-0 text-muted" style="font-size:0.85rem;"><i class="bi bi-bug me-1"></i> ${data.error || 'Unknown error'}</p>
                </div>
                <button class="btn btn-success btn-lg px-5 py-3 mt-3" onclick="startQrSession()" style="border-radius:12px; font-weight:600;">
                    <i class="bi bi-arrow-repeat me-2"></i> Retry
                </button>`;
            return;
        }

        if (data.status === 'connected') {
            showConnectedState(data.info);
            return;
        }

        // Start polling for QR code
        startQrPolling();

    } catch (err) {
        display.innerHTML = `
            <div class="alert alert-danger" style="border-radius:12px;">
                <i class="bi bi-wifi-off me-2"></i> Failed to connect to QR service.
            </div>
            <button class="btn btn-success btn-lg px-5 py-3 mt-3" onclick="startQrSession()" style="border-radius:12px; font-weight:600;">
                <i class="bi bi-arrow-repeat me-2"></i> Retry
            </button>`;
    }
}

function startQrPolling() {
    if (qrPollTimer) clearInterval(qrPollTimer);
    qrPollTimer = setInterval(pollQrCode, 2000);
    pollQrCode(); // immediate first poll
}

async function pollQrCode() {
    try {
        const res = await fetch(QR_API + '?action=qr');
        const data = await res.json();
        const display = document.getElementById('qrDisplay');
        const statusEl = document.getElementById('qrStatus');

        if (data.status === 'connected') {
            stopPolling();
            // Fetch full status with info
            const statusRes = await fetch(QR_API + '?action=status');
            const statusData = await statusRes.json();
            showConnectedState(statusData.info);
            return;
        }

        if (data.status === 'authenticated') {
            display.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-success mb-3" role="status" style="width:3rem;height:3rem;"></div>
                    <p class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i> Authenticated! Connecting...</p>
                </div>`;
            return;
        }

        if (data.status === 'waiting_scan' && data.qr) {
            display.innerHTML = `
                <div>
                    <img src="${data.qr}" alt="WhatsApp QR Code" style="width:280px; height:280px; border-radius:12px; border:3px solid #25d366; padding:8px; background:#fff;">
                    <p class="text-muted mt-3 mb-0"><i class="bi bi-phone me-1"></i> Scan this QR code with your WhatsApp app</p>
                </div>`;
            statusEl.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Waiting for scan...</span>';
            return;
        }

        if (data.status === 'initializing') {
            display.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-success mb-3" role="status" style="width:3rem;height:3rem;"></div>
                    <p class="text-muted fw-semibold">Generating QR Code...<br><small>Please wait</small></p>
                </div>`;
            return;
        }

        if (data.status === 'auth_failed' || data.status === 'error') {
            stopPolling();
            display.innerHTML = `
                <div class="alert alert-danger" style="border-radius:12px;">
                    <i class="bi bi-x-circle-fill me-2"></i> Connection failed. Please try again.
                </div>
                <button class="btn btn-success btn-lg px-5 py-3 mt-3" onclick="startQrSession()" style="border-radius:12px; font-weight:600;">
                    <i class="bi bi-arrow-repeat me-2"></i> Retry
                </button>`;
        }

    } catch (err) {
        // silently ignore polling errors
    }
}

function showConnectedState(info) {
    stopPolling();
    document.getElementById('qrSection').style.display = 'none';
    document.getElementById('qrConnected').style.display = 'block';
    const infoEl = document.getElementById('qrConnectedInfo');
    if (info) {
        infoEl.innerHTML = `<strong>${info.pushname || 'WhatsApp User'}</strong> (${info.phone || 'N/A'})<br><small class="text-muted">Platform: ${info.platform || 'web'}</small>`;
    }
}

async function disconnectQrSession() {
    if (!confirm('Are you sure you want to disconnect this QR session?')) return;

    try {
        await fetch(QR_API + '?action=disconnect', { method: 'POST' });
    } catch (e) {}

    document.getElementById('qrSection').style.display = 'block';
    document.getElementById('qrConnected').style.display = 'none';
    document.getElementById('qrDisplay').innerHTML = `
        <button id="startQrBtn" class="btn btn-success btn-lg px-5 py-3" onclick="startQrSession()" style="border-radius:12px; font-weight:600;">
            <i class="bi bi-qr-code me-2"></i> Generate QR Code
        </button>`;
    document.getElementById('qrStatus').innerHTML = '';
}

function stopPolling() {
    if (qrPollTimer) {
        clearInterval(qrPollTimer);
        qrPollTimer = null;
    }
}

// Auto-check QR session status on tab switch
document.getElementById('qrscan-tab')?.addEventListener('shown.bs.tab', async () => {
    try {
        const res = await fetch(QR_API + '?action=status');
        const data = await res.json();
        if (data.status === 'connected' && data.info) {
            showConnectedState(data.info);
        }
    } catch (e) {}
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
