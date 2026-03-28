<aside class="dash-sidebar">
    <div class="dash-logo">
        <i class="bi bi-whatsapp"></i>
        <span>WAPI SaaS</span>
    </div>
    
    <nav class="dash-nav">
        <a href="<?= baseUrl('dashboard/v2-index.php'); ?>" class="dash-nav-item active">
            <i class="bi bi-grid-fill"></i>
            <span>Overview</span>
        </a>
        <a href="<?= baseUrl('dashboard/v2-chatbot.php'); ?>" class="dash-nav-item">
            <i class="bi bi-robot"></i>
            <span>Chatbot Builder</span>
            <span class="dash-nav-tag">New</span>
        </a>
        <a href="<?= baseUrl('dashboard/v2-live-chat.php'); ?>" class="dash-nav-item">
            <i class="bi bi-chat-dots-fill"></i>
            <span>Live Chat</span>
            <span class="dash-nav-tag">Pro</span>
        </a>
        <a href="<?= baseUrl('dashboard/v2-campaigns.php'); ?>" class="dash-nav-item">
            <i class="bi bi-megaphone-fill"></i>
            <span>Campaigns</span>
        </a>
        <a href="<?= baseUrl('dashboard/v2-templates.php'); ?>" class="dash-nav-item">
            <i class="bi bi-file-earmark-richtext-fill"></i>
            <span>Templates</span>
        </a>
        <a href="<?= baseUrl('dashboard/v2-contacts.php'); ?>" class="dash-nav-item">
            <i class="bi bi-people-fill"></i>
            <span>Contacts</span>
        </a>
        
        <div style="margin: 1.5rem 0 0.5rem; font-size: 0.7rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase; letter-spacing: 0.05em;">Automation</div>
        
        <a href="<?= baseUrl('dashboard/v2-automation.php'); ?>" class="dash-nav-item">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>Triggers</span>
        </a>
        <a href="<?= baseUrl('dashboard/v2-integrations.php'); ?>" class="dash-nav-item">
            <i class="bi bi-puzzle-fill"></i>
            <span>Integrations</span>
        </a>
        
        <div style="margin: 1.5rem 0 0.5rem; font-size: 0.7rem; font-weight: 700; color: var(--dash-slate-400); text-transform: uppercase; letter-spacing: 0.05em;">Settings</div>
        
        <a href="<?= baseUrl('dashboard/v2-settings.php'); ?>" class="dash-nav-item">
            <i class="bi bi-gear-fill"></i>
            <span>Settings</span>
        </a>
    </nav>
    
    <div class="user-profile-summary" style="margin-top: auto; padding-top: 1.5rem; border-top: 1px solid var(--dash-slate-100); display: flex; align-items: center; gap: 12px;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--dash-primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
        </div>
        <div style="flex: 1; min-width: 0;">
            <div style="font-weight: 600; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= e($_SESSION['user_name'] ?? 'Guest'); ?></div>
            <div style="font-size: 0.75rem; color: var(--dash-slate-500);">Pro Plan</div>
        </div>
        <a href="<?= baseUrl('auth/logout.php'); ?>" style="color: var(--dash-slate-400);"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</aside>
