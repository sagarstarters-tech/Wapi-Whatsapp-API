<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

$pageTitle = 'Live Chat';
include __DIR__ . '/../includes/v2-header.php';
?>

<div class="row align-items-center mb-3">
    <div class="col-6">
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0 fw-bold">Team Inbox</h4>
            <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">12 UNREAD</span>
        </div>
    </div>
    <div class="col-6 text-end">
        <button class="btn btn-outline-secondary btn-sm bg-white border-slate-200 shadow-sm"><i class="bi bi-funnel"></i> Filters</button>
    </div>
</div>

<div class="inbox-layout shadow-premium border-0" style="height: calc(100vh - 180px); border-radius: 16px;">
    <!-- Chat List Sidebar -->
    <div class="inbox-sidebar" style="width: 320px; background: white;">
        <div class="p-3 border-bottom bg-light">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" placeholder="Search chats...">
            </div>
        </div>
        
        <div class="overflow-auto flex-1">
            <!-- Chat Item -->
            <div class="p-3 border-bottom d-flex gap-3 align-items-center cursor-pointer hover-bg-slate-50 active-chat-item">
                <div class="position-relative">
                    <div style="width: 48px; height: 48px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0ea5e9;">JD</div>
                    <div class="position-absolute bottom-0 end-0 bg-success" style="width: 12px; height: 12px; border: 2px solid white; border-radius: 50%;"></div>
                </div>
                <div class="flex-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold" style="font-size: 0.9375rem;">John Doe</span>
                        <span style="font-size: 0.7rem; color: var(--dash-slate-400);">10:24 AM</span>
                    </div>
                    <div style="font-size: 0.8125rem; color: var(--dash-slate-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">I have a question about my recent order #12345...</div>
                </div>
            </div>
            
            <div class="p-3 border-bottom d-flex gap-3 align-items-center cursor-pointer hover-bg-slate-50">
                <div class="position-relative">
                    <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #ef4444;">AS</div>
                </div>
                <div class="flex-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold" style="font-size: 0.9375rem;">Alice Smith</span>
                        <span style="font-size: 0.7rem; color: var(--dash-slate-400);">Yesterday</span>
                    </div>
                    <div style="font-size: 0.8125rem; color: var(--dash-slate-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Thank you for the update!</div>
                </div>
                <div style="width: 8px; height: 8px; background: #6366f1; border-radius: 50%;"></div>
            </div>
            
             <div class="p-3 border-bottom d-flex gap-3 align-items-center cursor-pointer hover-bg-slate-50">
                <div style="width: 48px; height: 48px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #22c55e;">RB</div>
                <div class="flex-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold" style="font-size: 0.9375rem;">Rob Brown</span>
                        <span style="font-size: 0.7rem; color: var(--dash-slate-400);">Monday</span>
                    </div>
                    <div style="font-size: 0.8125rem; color: var(--dash-slate-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Will it be delivered soon?</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chat Window -->
    <div class="inbox-chat flex-1">
        <!-- Chat Header -->
        <div class="p-3 bg-white border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 40px; height: 40px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0ea5e9;">JD</div>
                <div>
                    <h6 class="m-0 fw-bold">John Doe</h6>
                    <span style="font-size: 0.75rem; color: #10b981;"><i class="bi bi-circle-fill" style="font-size: 0.5rem; vertical-align: middle;"></i> Online • Lead</span>
                </div>
            </div>
            
            <div class="d-flex gap-1">
                <button class="btn btn-icon btn-sm text-slate-500"><i class="bi bi-archive"></i></button>
                <button class="btn btn-icon btn-sm text-slate-500"><i class="bi bi-check2-circle"></i></button>
                <div class="vr mx-2 bg-slate-200"></div>
                <button class="btn btn-icon btn-sm text-slate-500"><i class="bi bi-three-dots-vertical"></i></button>
            </div>
        </div>
        
        <!-- Messages Area -->
        <div class="flex-1 overflow-auto p-4 d-flex flex-column gap-2" id="chatArea">
            <div class="chat-date-separator text-center my-3"><span class="bg-white px-3 py-1 rounded-pill text-muted" style="font-size: 0.7rem; font-weight: 600;">TODAY</span></div>
            
            <div class="chat-bubble received">
                 Hi! I had a quick question about my order #12345.
                 <div class="wa-time">10:24 AM</div>
            </div>
            
            <div class="chat-bubble received">
                 Is it possible to change the delivery address to my office instead?
                 <div class="wa-time">10:25 AM <i class="bi bi-check2"></i></div>
            </div>
            
            <div class="chat-bubble sent">
                 Hi John! Of course, we can help with that. Please provide the new delivery address and your order confirmation email.
                 <div class="wa-time">10:28 AM <i class="bi bi-check2-all text-primary"></i></div>
            </div>
            
            <div class="chat-bubble received">
                 Sure! It's 123 Tech Park, Tower A, Silicon Valley. Email is john.doe@email.com.
                 <div class="wa-time">10:30 AM <i class="bi bi-check2"></i></div>
            </div>
        </div>
        
        <!-- Input Area -->
        <div class="p-3 bg-white border-top">
            <div class="mb-2 d-flex gap-2">
                <span class="badge bg-light text-slate-500 border cursor-pointer hover-bg-slate-50 px-2 py-1">Quick Reply <i class="bi bi-caret-down-fill" style="font-size: 0.6rem;"></i></span>
                <span class="badge bg-light text-slate-500 border cursor-pointer hover-bg-slate-50 px-2 py-1">File Upload</span>
                <span class="badge bg-light text-slate-500 border cursor-pointer hover-bg-slate-50 px-2 py-1">Internal Note</span>
            </div>
            <div class="d-flex gap-3 align-items-end">
                <div class="flex-1 position-relative">
                    <textarea class="form-control" rows="1" placeholder="Type a message..." style="border-radius: 12px; resize: none; padding-right: 40px; padding-top: 10px; padding-bottom: 10px;"></textarea>
                    <i class="bi bi-emoji-smile position-absolute text-slate-400 cursor-pointer" style="right: 12px; bottom: 10px; font-size: 1.25rem;"></i>
                </div>
                <button class="btn btn-primary" style="height: 44px; width: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; padding: 0;">
                    <i class="bi bi-send-fill" style="font-size: 1.25rem;"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Info Panels -->
    <div class="inbox-sidebar d-none d-xl-flex" style="width: 280px; background: #fdfdfd; border-left: 1px solid var(--dash-slate-200); border-right: 0;">
        <div class="p-4 text-center border-bottom bg-white">
            <div style="width: 80px; height: 80px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0ea5e9; font-size: 2rem; margin: 0 auto 1rem;">JD</div>
            <h5 class="fw-bold mb-1">John Doe</h5>
            <div style="font-size: 0.8125rem; color: var(--dash-slate-500);">+91 99887 76655</div>
            <div class="mt-3 d-flex justify-content-center gap-1">
                <span class="badge bg-info-subtle text-info">Inquiry</span>
                <span class="badge bg-success-subtle text-success">Warm Lead</span>
            </div>
        </div>
        
        <div class="p-4 flex-1 overflow-auto">
            <h6 class="fw-bold mb-3" style="font-size: 0.75rem; text-transform: uppercase; color: var(--dash-slate-400);">Contact Details</h6>
            
            <div class="mb-3">
                <div style="font-size: 0.7rem; color: var(--dash-slate-400);">Email</div>
                <div style="font-size: 0.875rem;">john.doe@email.com</div>
            </div>
            
            <div class="mb-3">
                <div style="font-size: 0.7rem; color: var(--dash-slate-400);">Location</div>
                <div style="font-size: 0.875rem;">Mumbai, India</div>
            </div>
            
            <div class="mb-4">
                <div style="font-size: 0.7rem; color: var(--dash-slate-400);">Assigned To</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <div style="width: 24px; height: 24px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem;">SM</div>
                    <span style="font-size: 0.875rem;">Samantha Miller</span>
                </div>
            </div>
            
            <h6 class="fw-bold mb-3" style="font-size: 0.75rem; text-transform: uppercase; color: var(--dash-slate-400);">Tags</h6>
            <div class="d-flex flex-wrap gap-1 mb-4">
                <span class="badge bg-light text-slate-600 border px-2 py-1">VIP Customer</span>
                <span class="badge bg-light text-slate-600 border px-2 py-1">Tech Inquiry</span>
                <button class="btn btn-sm text-primary p-0" style="font-size: 0.75rem;">+ Add Tag</button>
            </div>
            
            <button class="btn btn-outline-secondary w-100 btn-sm border-slate-200">View Full History</button>
        </div>
    </div>
</div>

<style>
.active-chat-item { background: #f0fdf4; border-left: 3px solid var(--dash-primary); }
.chat-date-separator span { border: 1px solid var(--dash-slate-200); position: relative; z-index: 1; }
.chat-date-separator::after { content: ''; position: absolute; width: 100%; height: 1px; background: var(--dash-slate-200); left: 0; top: 50%; z-index: 0; }
.hover-bg-slate-50:hover { background-color: var(--dash-slate-50); }
</style>

<?php include __DIR__ . '/../includes/v2-footer.php'; ?>
