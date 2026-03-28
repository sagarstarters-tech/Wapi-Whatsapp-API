<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$pageTitle = 'Templates';
include __DIR__ . '/../includes/v2-header.php';
?>

<div class="row mb-4 align-items-center">
    <div class="col-6">
        <h2 class="m-0 fw-bold">WhatsApp Templates</h2>
        <p class="text-slate-500 m-0" style="font-size: 0.8125rem;">Create and manage your Meta-approved message templates.</p>
    </div>
    <div class="col-6 text-end">
        <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200 me-2"><i class="bi bi-arrow-clockwise"></i> Sync Meta Status</button>
        <button class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newTemplateModal"><i class="bi bi-plus-lg"></i> Create Template</button>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Approved Templates</span>
                <div class="stat-icon"><i class="bi bi-check-circle-fill text-success"></i></div>
            </div>
            <div class="stat-value">12</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Pending Meta Review</span>
                <div class="stat-icon"><i class="bi bi-clock-fill text-warning"></i></div>
            </div>
            <div class="stat-value">3</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title">Rejected Templates</span>
                <div class="stat-icon"><i class="bi bi-x-circle-fill text-danger"></i></div>
            </div>
            <div class="stat-value">1</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Template Card 1 -->
    <div class="col-md-4">
        <div class="card border-0 shadow-premium h-100 overflow-hidden" style="border-radius: 16px;">
            <div class="card-body p-4 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1" style="font-size: 0.9375rem;">flash_sale_v3</h6>
                        <span class="badge bg-success-subtle text-success" style="font-size: 0.65rem;">APPROVED</span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon border-0 text-slate-400" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-pencil me-2"></i> Edit Template</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-copy me-2"></i> Copy Template</a></li>
                            <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-trash3 me-2"></i> Delete Template</a></li>
                        </ul>
                    </div>
                </div>
                
                <div style="font-size: 0.7rem; color: var(--dash-slate-400); text-transform: uppercase; font-weight: 700; margin-bottom: 8px;">Content Preview</div>
                <div class="p-3 bg-light rounded-3" style="font-size: 0.8125rem; color: var(--dash-slate-600); border-left: 3px solid var(--dash-primary); position: relative;">
                    "Hi [{{1}}], Summer flash sale is here! Get up to [{{2}}]% off on all items. Visit now: [{{3}}]"
                </div>
                
                <div class="mt-3 d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                    <span class="text-slate-500"><i class="bi bi-tag-fill me-1"></i> MARKETING</span>
                    <span class="text-slate-500">Last sync 2h ago</span>
                </div>
            </div>
            <div class="card-footer bg-light border-0 p-3">
                <button class="btn btn-outline-primary w-100 btn-sm" style="font-size: 0.8125rem;">View Analytics</button>
            </div>
        </div>
    </div>
    
    <!-- Template Card 2 -->
    <div class="col-md-4">
        <div class="card border-0 shadow-premium h-100 overflow-hidden" style="border-radius: 16px;">
            <div class="card-body p-4 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold mb-1" style="font-size: 0.9375rem;">otp_verification</h6>
                        <span class="badge bg-warning-subtle text-warning" style="font-size: 0.65rem;">PENDING REVIEW</span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon border-0 text-slate-400" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                    </div>
                </div>
                
                <div style="font-size: 0.7rem; color: var(--dash-slate-400); text-transform: uppercase; font-weight: 700; margin-bottom: 8px;">Content Preview</div>
                <div class="p-3 bg-light rounded-3" style="font-size: 0.8125rem; color: var(--dash-slate-600); border-left: 3px solid #f59e0b;">
                    "Your verification code is [{{1}}]. Do not share this with anyone."
                </div>
                
                <div class="mt-3 d-flex justify-content-between align-items-center" style="font-size: 0.75rem;">
                    <span class="text-slate-500"><i class="bi bi-shield-check me-1"></i> AUTHENTICATION</span>
                    <span class="text-slate-500">Wait: ~2 hours</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New Template (Simplified) -->
<div class="modal fade" id="newTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">Create New Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-5">
                    <div class="col-lg-7">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Template Name</label>
                            <input type="text" class="form-control" placeholder="e.g. order_confirmation_v2">
                            <span class="text-muted" style="font-size: 0.7rem;">Only lowercase letters, numbers, and underscores are allowed.</span>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Category</label>
                                <select class="form-select">
                                    <option>Marketing</option>
                                    <option>Utility</option>
                                    <option>Authentication</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Language</label>
                                <select class="form-select">
                                    <option>English (en)</option>
                                    <option>Hindi (hi)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Header (Optional)</label>
                            <select class="form-select mb-2">
                                <option>None</option>
                                <option>Text</option>
                                <option>Image</option>
                                <option>Video</option>
                                <option>Document</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <div class="d-flex justify-content-between">
                                <label class="form-label fw-bold">Body Text</label>
                                <span class="text-primary cursor-pointer fw-bold" style="font-size: 0.75rem;">+ Add Variable</span>
                            </div>
                            <textarea class="form-control" rows="5" placeholder="Enter your message content here..."></textarea>
                        </div>
                        
                         <div class="mb-4">
                            <label class="form-label fw-bold">Footer (Optional)</label>
                            <input type="text" class="form-control" placeholder="e.g. Reply STOP to opt out">
                        </div>
                        
                         <div class="mb-0">
                            <label class="form-label fw-bold">Buttons (Optional)</label>
                            <button class="btn btn-outline-secondary w-100 btn-sm border-slate-200">+ Add Button (CTA or Quick Reply)</button>
                        </div>
                    </div>
                    
                    <div class="col-lg-5">
                        <div class="p-4 rounded-4 bg-light h-100 d-flex flex-column align-items-center justify-content-center text-center">
                            <div style="font-weight: 700; font-size: 0.75rem; color: var(--dash-slate-400); text-transform: uppercase; margin-bottom: 2rem;">Real-time Preview</div>
                            
                            <div class="wa-phone-preview shadow-lg bg-white p-3 rounded-4" style="width: 280px; position: relative; border: 12px solid #333; border-radius: 40px !important;">
                                <div class="wa-header-preview bg-success text-white p-2 rounded-top-3 d-flex align-items-center gap-2" style="font-size: 0.7rem; margin: -1rem -1rem 1rem -1rem;">
                                    <i class="bi bi-chevron-left"></i> <span class="fw-bold">Business Name</span>
                                </div>
                                <div class="wa-msg-bubble p-2 bg-white shadow-sm rounded-3 border-1 mb-2" style="font-size: 0.75rem; position: relative;">
                                    <div class="text-muted mb-2">[Image Header]</div>
                                    "Your message content will appear here with dynamic variables."
                                    <div class="text-muted mt-1" style="font-size: 0.6rem;">Reply STOP to opt out</div>
                                </div>
                                <div class="btn btn-outline-primary btn-sm w-100 mt-1 d-flex justify-content-center align-items-center gap-2" style="font-size: 0.7rem; background: #fff;">
                                    <i class="bi bi-box-arrow-up-right"></i> Visit Website
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Discard Draft</button>
                <button type="button" class="btn btn-primary btn-sm px-4">Submit for Meta Review</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/v2-footer.php'; ?>
