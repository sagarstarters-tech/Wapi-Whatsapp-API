<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$pageTitle = 'Campaigns';
include __DIR__ . '/../includes/v2-header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-6">
        <h2 class="m-0 fw-bold">Broadcast Campaigns</h2>
        <p class="text-slate-500 m-0" style="font-size: 0.8125rem;">Reach your customers at scale via bulk messages.</p>
    </div>
    <div class="col-6 text-end">
        <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200 me-2"><i class="bi bi-download"></i> Reports</button>
        <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newCampaignModal"><i class="bi bi-plus-lg"></i> Create Campaign</button>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Ongoing Campaigns</span>
                <div class="stat-icon"><i class="bi bi-play-fill text-primary"></i></div>
            </div>
            <div class="stat-value">3</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Total Sent (Month)</span>
                <div class="stat-icon"><i class="bi bi-send-fill text-info"></i></div>
            </div>
            <div class="stat-value">124.5k</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Avg. Open Rate</span>
                <div class="stat-icon"><i class="bi bi-eye-fill text-success"></i></div>
            </div>
            <div class="stat-value">94.2%</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Campaign Errors</span>
                <div class="stat-icon"><i class="bi bi-bug-fill text-danger"></i></div>
            </div>
            <div class="stat-value">0.8%</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-premium p-0 overflow-hidden" style="border-radius: 16px;">
    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <select class="form-select form-select-sm border-0 bg-transparent fw-bold" style="width: auto;">
                <option>All Campaigns</option>
                <option>Scheduled</option>
                <option>Sent</option>
                <option>Draft</option>
            </select>
        </div>
        <div class="input-group input-group-sm" style="width: 240px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control border-start-0" placeholder="Search campaigns...">
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
            <thead class="bg-light">
                <tr style="font-size: 0.7rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase;">
                    <th class="ps-4">Campaign Name</th>
                    <th>Template</th>
                    <th>Audience</th>
                    <th>Sent/Delivered</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold" style="font-size: 0.875rem;">Summer Flash Sale</div>
                        <div style="font-size: 0.75rem; color: var(--dash-slate-400);">Promotional • ID: #CM6781</div>
                    </td>
                    <td><span class="badge bg-light text-slate-600 border px-2 py-1">flash_sale_v3</span></td>
                    <td><span style="font-size: 0.8125rem;">34,222 Contacts</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2" style="font-size: 0.8125rem;">
                            <span class="text-primary fw-bold">34,120</span> <span class="text-slate-400">/</span> <span class="text-success fw-bold">31,040</span>
                        </div>
                        <div class="progress mt-1" style="height: 4px; width: 80px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 90%;"></div>
                        </div>
                    </td>
                    <td><span class="badge rounded-pill bg-success-subtle text-success">SENT</span></td>
                    <td><span style="font-size: 0.8125rem;">Yesterday, 10:00 AM</span></td>
                    <td class="pe-4 text-end">
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-bar-chart"></i></button>
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-three-dots-vertical"></i></button>
                    </td>
                </tr>
                
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold" style="font-size: 0.875rem;">Eid Mubarak Wish</div>
                        <div style="font-size: 0.75rem; color: var(--dash-slate-400);">Utility • ID: #CM6782</div>
                    </td>
                    <td><span class="badge bg-light text-slate-600 border px-2 py-1">greetings_basic</span></td>
                    <td><span style="font-size: 0.8125rem;">12,040 Contacts</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2" style="font-size: 0.8125rem;">
                            <span class="text-primary fw-bold">12,040</span> <span class="text-slate-400">/</span> <span class="text-success fw-bold">11,980</span>
                        </div>
                        <div class="progress mt-1" style="height: 4px; width: 80px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%;"></div>
                        </div>
                    </td>
                    <td><span class="badge rounded-pill bg-success-subtle text-success">SENT</span></td>
                    <td><span style="font-size: 0.8125rem;">3 days ago</span></td>
                    <td class="pe-4 text-end">
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-bar-chart"></i></button>
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-three-dots-vertical"></i></button>
                    </td>
                </tr>
                
                <tr>
                    <td class="ps-4">
                        <div class="fw-bold" style="font-size: 0.875rem;">Customer Satisfaction Survey</div>
                        <div style="font-size: 0.75rem; color: var(--dash-slate-400);">Feedback • ID: #CM6783</div>
                    </td>
                    <td><span class="badge bg-light text-slate-600 border px-2 py-1">survey_form_v1</span></td>
                    <td><span style="font-size: 0.8125rem;">4,500 Contacts</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2" style="font-size: 0.8125rem;">
                            <span class="text-slate-400">0</span> <span class="text-slate-400">/</span> <span class="text-slate-400">0</span>
                        </div>
                        <div class="progress mt-1" style="height: 4px; width: 80px;">
                            <div class="progress-bar bg-slate-200" role="progressbar" style="width: 0%;"></div>
                        </div>
                    </td>
                    <td><span class="badge rounded-pill bg-info-subtle text-info">SCHEDULED</span></td>
                    <td><span style="font-size: 0.8125rem;">Tomorrow, 11:30 PM</span></td>
                    <td class="pe-4 text-end">
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-icon border-0 text-slate-400"><i class="bi bi-three-dots-vertical"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: New Campaign -->
<div class="modal fade" id="newCampaignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Create New Broadcast Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <label class="form-label fw-bold">Campaign Name</label>
                    <input type="text" class="form-control" placeholder="e.g. New Year 2024 Promotional">
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Target Audience</label>
                        <select class="form-select">
                            <option>All Contacts (34,222)</option>
                            <option>Tag: VIP Customers (1,200)</option>
                            <option>Tag: Leads (4,500)</option>
                            <option>Upload CSV/Excel</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">WhatsApp Template</label>
                        <select class="form-select">
                            <option>Select a template...</option>
                            <option>flash_sale_v3 [Approved]</option>
                            <option>welcome_greetings [Approved]</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Schedule Time</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="schedule" id="now" checked>
                            <label class="form-check-label" for="now">Send Immediately</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="schedule" id="later">
                            <label class="form-check-label" for="later">Schedule for later</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm px-4">Create Campaign</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/v2-footer.php'; ?>
