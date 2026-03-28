<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$pageTitle = 'Overview';
include __DIR__ . '/../includes/v2-header.php';
?>

<div class="row mb-5 align-items-center">
    <div class="col-md-6">
        <h2 style="font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.75rem; color: var(--dash-slate-900);">Welcome Back, <?= e($_SESSION['user_name']); ?>! 👋</h2>
        <p style="color: var(--dash-slate-500); font-size: 0.9375rem; margin-top: 0.25rem;">Here’s what’s happening with your business today.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <div class="btn-group shadow-sm">
            <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200">Today</button>
            <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200 active">7 Days</button>
            <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200">30 Days</button>
        </div>
        <button class="btn btn-primary btn-sm ms-2 shadow-sm"><i class="bi bi-download"></i> Export data</button>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Messages Sent</span>
            <div class="stat-icon"><i class="bi bi-send-fill text-primary"></i></div>
        </div>
        <div class="stat-value">24,562</div>
        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> 12% vs last week</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Delivered</span>
            <div class="stat-icon"><i class="bi bi-check-all text-primary"></i></div>
        </div>
        <div class="stat-value">22,810</div>
        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> 8% vs last week</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Read Ratio</span>
            <div class="stat-icon"><i class="bi bi-eye-fill text-info"></i></div>
        </div>
        <div class="stat-value">91%</div>
        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i> 3% increase</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Failed</span>
            <div class="stat-icon" style="background: #fee2e2; color: #ef4444;"><i class="bi bi-exclamation-triangle-fill"></i></div>
        </div>
        <div class="stat-value">124</div>
        <div class="stat-change down"><i class="bi bi-arrow-down-short"></i> 5% decrease</div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <div class="card border-0 shadow-premium p-4" style="height: 100%; border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="m-0 fw-bold">Message Analytics</h5>
                <select class="form-select form-select-sm" style="width: auto; border: 0; background-color: var(--dash-slate-100);">
                    <option>Sent</option>
                    <option>Received</option>
                </select>
            </div>
            <canvas id="messagesChart" height="280"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-premium p-4" style="height: 100%; border-radius: 16px;">
            <h5 class="m-0 fw-bold mb-4">Active Campaigns</h5>
            <div class="list-group list-group-flush">
                <div class="list-group-item px-0 py-3 border-0 d-flex align-items-center gap-3">
                    <div style="width: 44px; height: 44px; background: #e0f2fe; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #0ea5e9;">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold mb-0" style="font-size: 0.9375rem;">Summer Flash Sale</div>
                        <div style="font-size: 0.8125rem; color: var(--dash-slate-500);">34,222 Total • 88% Success</div>
                    </div>
                    <div class="ms-auto"><span class="badge bg-success-subtle text-success">Running</span></div>
                </div>
                
                <div class="list-group-item px-0 py-3 border-0 d-flex align-items-center gap-3">
                    <div style="width: 44px; height: 44px; background: #dcfce7; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #22c55e;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="fw-bold mb-0" style="font-size: 0.9375rem;">Abandoned Cart Reminder</div>
                        <div style="font-size: 0.8125rem; color: var(--dash-slate-500);">Automation • 2.4k Messages</div>
                    </div>
                    <div class="ms-auto"><span class="badge bg-info-subtle text-info">Ongoing</span></div>
                </div>
                
                <div class="list-group-item px-0 py-3 border-0 d-flex align-items-center gap-3">
                    <div style="width: 44px; height: 44px; background: #fef3c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #f59e0b;">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div>
                        <div class="fw-bold mb-0" style="font-size: 0.9375rem;">Welcome Flow</div>
                        <div style="font-size: 0.8125rem; color: var(--dash-slate-500);">Triggers • 12,045 New Users</div>
                    </div>
                    <div class="ms-auto"><span class="badge bg-secondary-subtle text-secondary">Paused</span></div>
                </div>
            </div>
            <a href="<?= baseUrl('dashboard/v2-campaigns.php'); ?>" class="btn btn-outline-secondary w-100 mt-4 border-slate-200" style="font-size: 0.875rem;">View All Campaigns</a>
        </div>
    </div>
</div>

<!-- Recent Conversations -->
<div class="card border-0 shadow-premium p-4" style="border-radius: 16px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="m-0 fw-bold">Recent Conversations</h5>
        <a href="<?= baseUrl('dashboard/v2-live-chat.php'); ?>" class="text-primary fw-bold" style="font-size: 0.875rem;">Go to Inbox</a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="bg-light">
                <tr style="font-size: 0.75rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase;">
                    <th>Contact</th>
                    <th>Last Message</th>
                    <th>Status</th>
                    <th>Agent</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 32px; height: 32px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; font-weight: 600;">JD</div>
                            <div>
                                <div class="fw-bold mb-0" style="font-size: 0.875rem;">John Doe</div>
                                <div style="font-size: 0.75rem; color: var(--dash-slate-400);">+91 99887 76655</div>
                            </div>
                        </div>
                    </td>
                    <td>
                         <div style="font-size: 0.875rem; color: var(--dash-slate-600); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">I have a question about my recent order #12345.</div>
                         <div style="font-size: 0.75rem; color: var(--dash-slate-400);">10:24 AM</div>
                    </td>
                    <td><span class="badge rounded-pill bg-warning-subtle text-warning">Pending</span></td>
                    <td><span style="font-size: 0.8125rem;">Samantha</span></td>
                    <td><button class="btn btn-sm btn-icon" style="color: var(--dash-slate-400);"><i class="bi bi-chat-text"></i></button></td>
                </tr>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 32px; height: 32px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; font-weight: 600; color: #0ea5e9;">AS</div>
                            <div>
                                <div class="fw-bold mb-0" style="font-size: 0.875rem;">Alice Smith</div>
                                <div style="font-size: 0.75rem; color: var(--dash-slate-400);">+91 88776 65544</div>
                            </div>
                        </div>
                    </td>
                    <td>
                         <div style="font-size: 0.875rem; color: var(--dash-slate-600);">Thank you for the update!</div>
                         <div style="font-size: 0.75rem; color: var(--dash-slate-400);">Yesterday, 5:45 PM</div>
                    </td>
                    <td><span class="badge rounded-pill bg-success-subtle text-success">Resolved</span></td>
                    <td><span style="font-size: 0.8125rem;">Bot</span></td>
                    <td><button class="btn btn-sm btn-icon" style="color: var(--dash-slate-400);"><i class="bi bi-chat-text"></i></button></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('messagesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Messages Sent',
                data: [1200, 1900, 3000, 5000, 2300, 3400, 8400],
                borderColor: '#25D366',
                backgroundColor: 'rgba(37, 211, 102, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#25D366',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                    ticks: { color: '#94a3b8', font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11 } }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/v2-footer.php'; ?>
