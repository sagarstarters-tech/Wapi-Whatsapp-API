<?php
/**
 * WAPI SaaS - Activate Free Plan Endpoint
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

$planId = sanitizeInt($_GET['plan_id'] ?? 0);

if (empty($planId)) {
    setFlash('danger', 'Invalid plan activation request.');
    redirect('dashboard/subscription.php');
}

$plan = $db->fetch("SELECT * FROM plans WHERE id = ? AND is_active = 1", [$planId]);

if (!$plan || $plan['monthly_price'] > 0) {
    setFlash('danger', 'Invalid plan or plan is not free.');
    redirect('dashboard/subscription.php');
}

// Check if user has already used this free plan
$alreadyUsed = $db->fetch("SELECT id FROM subscriptions WHERE user_id = ? AND plan_id = ?", [$userId, $planId]);
if ($alreadyUsed) {
    setFlash('warning', 'You have already redeemed this free trial. Please select a paid plan to continue enjoying WAPI.');
    redirect('dashboard/subscription.php');
}

// Cancel existing active subscriptions
$db->update('subscriptions', ['status' => 'cancelled'], "user_id = ? AND status = 'active'", [$userId]);

// Calculate dates
$startsAt = date('Y-m-d H:i:s');
$isTrial = ($plan['slug'] === 'trial' || $plan['slug'] === '14-days-trial' || stripos($plan['name'], 'trial') !== false);
$expiryPeriod = $isTrial ? '+14 days' : '+1 month';
$expiresAt = date('Y-m-d H:i:s', strtotime($expiryPeriod));

// Create subscription
$subscriptionId = $db->insert('subscriptions', [
    'user_id' => $userId,
    'plan_id' => $planId,
    'billing_cycle' => 'monthly',
    'amount' => 0,
    'status' => 'active',
    'starts_at' => $startsAt,
    'expires_at' => $expiresAt
]);

// Create payment record
$db->insert('payments', [
    'user_id' => $userId,
    'subscription_id' => $subscriptionId,
    'amount' => 0,
    'status' => 'success',
    'payment_method' => 'free'
]);

// Update or insert credits
$creditExists = $db->fetch("SELECT id FROM credits WHERE user_id = ?", [$userId]);
if ($creditExists) {
    $db->update('credits', [
        'total_credits' => $plan['message_limit'],
        'used_credits' => 0
    ], "user_id = ?", [$userId]);
} else {
    $db->insert('credits', [
        'user_id' => $userId,
        'total_credits' => $plan['message_limit'],
        'used_credits' => 0
    ]);
}

setFlash('success', '14 Days Free Trial activated successfully!');
redirect('dashboard/subscription.php');
