<?php
/**
 * WAPI SaaS - Subscription / Plan Management for Users
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$settings = new Settings();
$userId = $_SESSION['user_id'];

// Get available plans
$plans = $db->fetchAll("SELECT p.*, GROUP_CONCAT(pf.feature_text, '|||', pf.is_included ORDER BY pf.sort_order SEPARATOR ';;;') as features_list FROM plans p LEFT JOIN plan_features pf ON p.id = pf.plan_id WHERE p.is_active = 1 GROUP BY p.id ORDER BY p.sort_order ASC");

// Current subscription
$currentSub = $db->fetch("SELECT s.*, p.name as plan_name FROM subscriptions s JOIN plans p ON s.plan_id = p.id WHERE s.user_id = ? AND s.status = 'active' ORDER BY s.created_at DESC LIMIT 1", [$userId]);

// Payment history
$payments = $db->fetchAll("SELECT p.*, pl.name as plan_name FROM payments p LEFT JOIN subscriptions s ON p.subscription_id = s.id LEFT JOIN plans pl ON s.plan_id = pl.id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 10", [$userId]);

$pageTitle = 'Subscription';
$extraCss = ['/wapi/assets/css/dashboard.css'];
$extraJs = ['/wapi/assets/js/admin.js'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">Subscription</h1>
                <div class="dash-breadcrumb"><a href="/wapi/dashboard/">Dashboard</a><i class="bi bi-chevron-right"></i><span>Subscription</span></div>
            </div>
            <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?>"><?= e($flash['message']); ?></div>
        <?php endif; ?>

        <!-- Current Plan -->
        <?php if ($currentSub): ?>
        <div class="card mb-4" style="border-radius: var(--border-radius); border-left: 4px solid var(--primary);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-1">Current Plan: <span class="text-primary"><?= e($currentSub['plan_name']); ?></span></h5>
                        <p class="text-muted mb-0">Expires: <?= formatDate($currentSub['expires_at']); ?> &bull; Status: <span class="status-badge status-active">Active</span></p>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold" style="font-size: 1.25rem;"><?= formatCurrency($currentSub['amount']); ?></div>
                        <small class="text-muted">/ <?= $currentSub['billing_cycle']; ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Available Plans -->
        <h5 class="fw-bold mb-3"><?= $currentSub ? 'Upgrade Plan' : 'Choose a Plan'; ?></h5>
        <div class="row g-4 mb-4">
            <?php foreach ($plans as $plan): 
                $planFeatures = [];
                if (!empty($plan['features_list'])) {
                    $featureItems = explode(';;;', $plan['features_list']);
                    foreach ($featureItems as $item) {
                        $parts = explode('|||', $item);
                        if (count($parts) === 2) $planFeatures[] = ['text' => $parts[0], 'included' => $parts[1]];
                    }
                }
                $isCurrentPlan = $currentSub && $currentSub['plan_id'] == $plan['id'];
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card <?= $plan['is_popular'] ? 'popular' : ''; ?> <?= $isCurrentPlan ? '' : ''; ?>" style="text-align:left;">
                    <?php if ($plan['is_popular']): ?><div class="pricing-badge">Most Popular</div><?php endif; ?>
                    <?php if ($isCurrentPlan): ?><div style="position:absolute;top:10px;right:15px;"><span class="status-badge status-active">Current</span></div><?php endif; ?>
                    
                    <div class="fw-bold mb-1" style="color: <?= e($plan['badge_color']); ?>; font-size: 1.125rem;"><?= e($plan['name']); ?></div>
                    <p class="text-muted mb-3" style="font-size: 0.8125rem;"><?= e($plan['description']); ?></p>
                    
                    <div class="mb-3">
                        <span class="fw-bold" style="font-size: 1.75rem;"><?= formatCurrency($plan['monthly_price']); ?></span>
                        <span class="text-muted">/month</span>
                    </div>

                    <ul class="pricing-features" style="margin-bottom: 1.5rem;">
                        <?php foreach ($planFeatures as $pf): ?>
                        <li class="<?= $pf['included'] == '0' ? 'disabled' : ''; ?>">
                            <i class="bi <?= $pf['included'] == '1' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
                            <?= e($pf['text']); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($isCurrentPlan): ?>
                        <button class="btn btn-outline-primary w-100" disabled>Current Plan</button>
                    <?php elseif ($plan['monthly_price'] == 0): ?>
                        <button class="btn btn-outline-primary w-100" onclick="activateFreePlan(<?= $plan['id']; ?>)">Activate</button>
                    <?php else: ?>
                        <button class="btn btn-primary w-100" onclick="initPayment(<?= $plan['id']; ?>, '<?= e($plan['name']); ?>', <?= $plan['monthly_price']; ?>)">
                            <i class="bi bi-credit-card"></i> Subscribe
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Payment History -->
        <div class="data-table">
            <div class="data-table-header"><h5 class="data-table-title mb-0">Payment History</h5></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Plan</th><th>Amount</th><th>Gateway</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No payments yet</td></tr>
                        <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($payment['plan_name'] ?? '-'); ?></td>
                            <td class="fw-bold"><?= formatCurrency($payment['amount']); ?></td>
                            <td><span class="badge-custom" style="background: var(--primary-bg); color: var(--primary);"><?= ucfirst($payment['payment_method'] ?? 'Razorpay'); ?></span></td>
                            <td><span class="status-badge status-<?= $payment['status'] === 'success' ? 'active' : $payment['status']; ?>"><?= ucfirst($payment['status']); ?></span></td>
                            <td style="font-size: 0.8125rem; color: var(--text-muted);"><?= formatDate($payment['created_at']); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function initPayment(planId, planName, amount) {
    const options = {
        key: '<?= e($settings->get('razorpay_key_id', '')); ?>',
        amount: amount * 100, // Razorpay takes amount in paise
        currency: 'INR',
        name: '<?= e($settings->get('site_name', 'WAPI')); ?>',
        description: planName + ' Plan Subscription',
        handler: function(response) {
            // Verify payment on server
            window.location.href = '/wapi/api/verify-payment.php?payment_id=' + response.razorpay_payment_id + '&plan_id=' + planId;
        },
        prefill: {
            name: '<?= e($_SESSION['user_name'] ?? ''); ?>',
            email: '<?= e($_SESSION['user_email'] ?? ''); ?>'
        },
        theme: { color: '#6c63ff' }
    };
    
    const rzp = new Razorpay(options);
    rzp.open();
}

function activateFreePlan(planId) {
    if (confirm('Activate the free plan?')) {
        window.location.href = '/wapi/api/activate-plan.php?plan_id=' + planId;
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
