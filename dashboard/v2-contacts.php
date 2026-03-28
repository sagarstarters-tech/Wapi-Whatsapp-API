<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$pageTitle = 'Contacts';
include __DIR__ . '/../includes/v2-header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="m-0 fw-bold">Contacts & Segments</h2>
        <p class="text-slate-500 m-0" style="font-size: 0.8125rem;">Manage your database and segment users for targeted messaging.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200 me-2"><i class="bi bi-download"></i> Export CSV</button>
        <button class="btn btn-outline-primary btn-sm me-2"><i class="bi bi-upload"></i> Import Contacts</button>
        <button class="btn btn-primary btn-sm px-3 shadow-sm"><i class="bi bi-plus-lg"></i> Add Contact</button>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-premium p-4 h-100" style="border-radius: 16px;">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #e0f2fe; display: flex; align-items: center; justify-content: center; color: #0ea5e9;"><i class="bi bi-people-fill"></i></div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase;">Total Contacts</div>
            </div>
            <div class="h3 fw-bold m-0">45,671</div>
            <div class="mt-2 text-success" style="font-size: 0.8125rem;"><i class="bi bi-arrow-up-short"></i> 2.4% this month</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-premium p-4 h-100" style="border-radius: 16px;">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #fef2f2; display: flex; align-items: center; justify-content: center; color: #ef4444;"><i class="bi bi-slash-circle-fill"></i></div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase;">Opt-outs</div>
            </div>
            <div class="h3 fw-bold m-0">1,204</div>
            <div class="mt-2 text-danger" style="font-size: 0.8125rem;"><i class="bi bi-arrow-up-short"></i> 0.3% unsubscribe rate</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-premium p-4 h-100" style="border-radius: 16px;">
             <div class="d-flex align-items-center gap-3 mb-2">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #dcfce7; display: flex; align-items: center; justify-content: center; color: #22c55e;"><i class="bi bi-check-circle-fill"></i></div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase;">Active Subscriptions</div>
            </div>
            <div class="h3 fw-bold m-0">34,120</div>
            <div class="mt-2 text-success" style="font-size: 0.8125rem;">Active in last 24h</div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-premium p-4 h-100" style="border-radius: 16px;">
             <div class="d-flex align-items-center gap-3 mb-2">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #faf5ff; display: flex; align-items: center; justify-content: center; color: #a855f7;"><i class="bi bi-tag-fill"></i></div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase;">Unique Tags</div>
            </div>
            <div class="h3 fw-bold m-0">124</div>
            <div class="mt-2 text-primary" style="font-size: 0.8125rem;">For segmentation</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-premium p-0 overflow-hidden" style="border-radius: 16px;">
    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200"><i class="bi bi-funnel"></i> Filters</button>
            <div class="dropdown">
                <button class="btn btn-white btn-sm border-0 dropdown-toggle" data-bs-toggle="dropdown">All Segments</button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">VIP Customers</a></li>
                    <li><a class="dropdown-item" href="#">Leads</a></li>
                    <li><a class="dropdown-item" href="#">Unsubscribed</a></li>
                </ul>
            </div>
        </div>
        <div class="input-group input-group-sm" style="width: 240px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Search by name or number...">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
            <thead class="bg-light">
                <tr style="font-size: 0.7rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase;">
                    <th class="ps-4">Contact</th>
                    <th>Tags</th>
                    <th>Last Active</th>
                    <th>Subscribed</th>
                    <th>Source</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 32px; height: 32px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; font-weight: 600; color: #0ea5e9;">JD</div>
                            <div>
                                <div class="fw-bold mb-0" style="font-size: 0.875rem;">John Doe</div>
                                <div style="font-size: 0.75rem; color: var(--dash-slate-400);">+91 99887 76655</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-slate-600 border px-2 py-1">VIP</span> <span class="badge bg-light text-slate-600 border px-2 py-1">Tech Inquiry</span></td>
                    <td><span style="font-size: 0.8125rem;">Today, 10:24 AM</span></td>
                    <td><span class="badge bg-success-subtle text-success border-0 px-2 py-1" style="font-size: 0.65rem;">YES</span></td>
                    <td><span style="font-size: 0.8125rem;">Shopify API</span></td>
                    <td class="pe-4 text-end">
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-chat-text"></i></button>
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-three-dots-vertical"></i></button>
                    </td>
                </tr>
                
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 32px; height: 32px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8125rem; font-weight: 600; color: #ef4444;">AS</div>
                            <div>
                                <div class="fw-bold mb-0" style="font-size: 0.875rem;">Alice Smith</div>
                                <div style="font-size: 0.75rem; color: var(--dash-slate-400);">+91 88776 65544</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-slate-600 border px-2 py-1">Lead</span></td>
                    <td><span style="font-size: 0.8125rem;">Yesterday, 5:45 PM</span></td>
                    <td><span class="badge bg-success-subtle text-success border-0 px-2 py-1" style="font-size: 0.65rem;">YES</span></td>
                    <td><span style="font-size: 0.8125rem;">Direct Message</span></td>
                    <td class="pe-4 text-end">
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-chat-text"></i></button>
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-three-dots-vertical"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/v2-footer.php'; ?>
