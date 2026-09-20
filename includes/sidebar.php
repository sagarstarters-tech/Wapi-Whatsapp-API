<?php
/**
 * WAPI SaaS - User Dashboard Sidebar Component
 */
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$db = Database::getInstance();

// Get user credit balance
$isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
$credits = $db->fetch("SELECT total_credits, used_credits FROM credits WHERE user_id = ?", [$_SESSION['user_id']]);
$creditBalance = $credits ? ($credits['total_credits'] - $credits['used_credits']) : 0;
$unreadNotifications = $db->count('notifications', "user_id = ? AND is_read = 0", [$_SESSION['user_id']]);
?>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-user-block d-flex align-items-center gap-2">
            <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.75rem;">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
            </div>
            <div>
                <div class="fw-bold" style="font-size: 0.8125rem; line-height: 1.2;"><?= e($_SESSION['user_name'] ?? 'User'); ?></div>
                <div style="font-size: 0.6875rem; color: var(--text-muted);">
                    Credits: <span class="fw-bold text-primary"><?= $isAdmin ? 'Unlimited' : number_format($creditBalance); ?></span>
                </div>
            </div>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <!-- Overview -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Overview</div>
            <a href="<?= baseUrl('dashboard/'); ?>" class="sidebar-link <?= $currentPage === 'index' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= baseUrl('dashboard/whatsapp.php'); ?>" class="sidebar-link <?= $currentPage === 'whatsapp' ? 'active' : ''; ?>">
                <i class="bi bi-whatsapp"></i>
                <span>WhatsApp Setup</span>
            </a>
        </div>

        <!-- Messaging -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Messaging</div>
            <a href="<?= baseUrl('dashboard/messages.php'); ?>" class="sidebar-link <?= $currentPage === 'messages' ? 'active' : ''; ?>">
                <i class="bi bi-send-fill"></i>
                <span>Send Message</span>
            </a>
            <a href="<?= baseUrl('dashboard/bulk-messages.php'); ?>" class="sidebar-link <?= $currentPage === 'bulk-messages' ? 'active' : ''; ?>">
                <i class="bi bi-megaphone-fill"></i>
                <span>Bulk Messages</span>
            </a>
            <a href="<?= baseUrl('dashboard/templates.php'); ?>" class="sidebar-link <?= $currentPage === 'templates' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span>Templates</span>
            </a>

            <a href="<?= baseUrl('dashboard/live-chat.php'); ?>" class="sidebar-link <?= $currentPage === 'live-chat' ? 'active' : ''; ?>">
                <i class="bi bi-chat-dots-fill"></i>
                <span>Live Chat</span>
            </a>

        </div>

        <!-- Automations -->
        <?php 
        $sidebarSettings = isset($settings) ? $settings : new Settings();
        $sidebarUserId = $_SESSION['user_id'] ?? 0;
        
        $enableChatbot = $sidebarSettings->get('enable_chatbot_builder', '1') === '1';
        $userChatbotPref = $sidebarSettings->get("user_{$sidebarUserId}_chatbot_builder", null);
        if ($userChatbotPref !== null) {
            $enableChatbot = ($userChatbotPref === '1');
        }

        $enableAIChatbot = $sidebarSettings->get('enable_ai_chatbot_builder', '1') === '1';
        $userAIPref = $sidebarSettings->get("user_{$sidebarUserId}_ai_chatbot_builder", null);
        if ($userAIPref !== null) {
            $enableAIChatbot = ($userAIPref === '1');
        }

        $isUserAdmin = Auth::isAdmin();
        ?>
        <div class="sidebar-section">
            <div class="sidebar-section-title d-flex justify-content-between align-items-center">
                <span>Automations</span>
                <?php if ($isUserAdmin): ?>
                    <a href="<?= baseUrl('admin/settings.php?tab=automations'); ?>" title="Configure Automations" style="font-size: 0.75rem; color: var(--text-muted);">
                        <i class="bi bi-gear"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Chatbot Builder with Quick Switch -->
            <div class="sidebar-toggle-item d-flex align-items-center justify-content-between pe-2 mb-1" style="border-radius: var(--border-radius-sm); transition: var(--transition-fast);">
                <a href="<?= baseUrl('dashboard/chatbot-builder.php'); ?>" class="sidebar-link flex-grow-1 <?= $currentPage === 'chatbot-builder' ? 'active' : ''; ?>" style="<?= !$enableChatbot ? 'opacity: 0.65;' : ''; ?> padding-right: 4px;">
                    <i class="bi bi-robot"></i>
                    <span>Chatbot Builder</span>
                </a>
                <div class="form-check form-switch m-0 p-0 d-flex align-items-center" title="Toggle Chatbot Builder">
                    <input class="form-check-input module-quick-toggle" type="checkbox" role="switch" data-module="chatbot_builder" <?= $enableChatbot ? 'checked' : ''; ?> style="width: 2rem; height: 1.05rem; cursor: pointer; margin: 0;">
                </div>
            </div>

            <!-- AI ChatBot Builder with Quick Switch -->
            <div class="sidebar-toggle-item d-flex align-items-center justify-content-between pe-2 mb-1" style="border-radius: var(--border-radius-sm); transition: var(--transition-fast);">
                <a href="<?= baseUrl('dashboard/ai-chatbot.php'); ?>" class="sidebar-link flex-grow-1 <?= ($currentPage === 'ai-chatbot' || $currentPage === 'ai-chatbot-editor') ? 'active' : ''; ?>" style="<?= !$enableAIChatbot ? 'opacity: 0.65;' : ''; ?> padding-right: 4px;">
                    <i class="bi bi-stars"></i>
                    <span>AI ChatBot Builder</span>
                </a>
                <div class="form-check form-switch m-0 p-0 d-flex align-items-center" title="Toggle AI ChatBot Builder">
                    <input class="form-check-input module-quick-toggle" type="checkbox" role="switch" data-module="ai_chatbot_builder" <?= $enableAIChatbot ? 'checked' : ''; ?> style="width: 2rem; height: 1.05rem; cursor: pointer; margin: 0;">
                </div>
            </div>
        </div>

        <!-- CRM & Contacts -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">CRM & Contacts</div>
            <a href="<?= baseUrl('dashboard/crm.php'); ?>" class="sidebar-link <?= $currentPage === 'crm' ? 'active' : ''; ?>">
                <i class="bi bi-kanban-fill"></i>
                <span>WhatsApp CRM</span>
                <span class="badge rounded-pill bg-success ms-auto" style="font-size: 0.6rem;">PRO</span>
            </a>
            <a href="<?= baseUrl('dashboard/contacts.php'); ?>" class="sidebar-link <?= $currentPage === 'contacts' ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i>
                <span>Contacts</span>
            </a>
        </div>

        <!-- Analytics -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Analytics</div>
            <a href="<?= baseUrl('dashboard/message-logs.php'); ?>" class="sidebar-link <?= $currentPage === 'message-logs' ? 'active' : ''; ?>">
                <i class="bi bi-list-check"></i>
                <span>Message Logs</span>
            </a>
            <?php if ($enableAIChatbot || $isUserAdmin): ?>
            <a href="<?= baseUrl('dashboard/ai-analytics.php'); ?>" class="sidebar-link <?= $currentPage === 'ai-analytics' ? 'active' : ''; ?>" style="<?= !$enableAIChatbot ? 'opacity: 0.55;' : ''; ?>">
                <i class="bi bi-graph-up-arrow"></i>
                <span>AI Analytics</span>
                <?php if (!$enableAIChatbot): ?><span class="badge rounded-pill bg-secondary ms-auto" style="font-size: 0.6rem;">OFF</span><?php endif; ?>
            </a>
            <a href="<?= baseUrl('dashboard/ai-conversations.php'); ?>" class="sidebar-link <?= $currentPage === 'ai-conversations' ? 'active' : ''; ?>" style="<?= !$enableAIChatbot ? 'opacity: 0.55;' : ''; ?>">
                <i class="bi bi-chat-left-text"></i>
                <span>AI Conversations</span>
                <?php if (!$enableAIChatbot): ?><span class="badge rounded-pill bg-secondary ms-auto" style="font-size: 0.6rem;">OFF</span><?php endif; ?>
            </a>
            <?php endif; ?>
        </div>

        <!-- Account -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Account</div>
            <a href="<?= baseUrl('dashboard/api-keys.php'); ?>" class="sidebar-link <?= $currentPage === 'api-keys' ? 'active' : ''; ?>">
                <i class="bi bi-key-fill"></i>
                <span>API Keys</span>
            </a>
            <a href="<?= baseUrl('dashboard/subscription.php'); ?>" class="sidebar-link <?= $currentPage === 'subscription' ? 'active' : ''; ?>">
                <i class="bi bi-credit-card-fill"></i>
                <span>Subscription</span>
            </a>
            <a href="<?= baseUrl('dashboard/settings.php'); ?>" class="sidebar-link <?= $currentPage === 'settings' ? 'active' : ''; ?>">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <?php if ($isAdmin): ?>
        <a href="<?= baseUrl('admin/'); ?>" class="sidebar-link" style="color: var(--primary);">
            <i class="bi bi-shield-check"></i>
            <span>Admin Panel</span>
        </a>
        <?php endif; ?>
        <a href="<?= baseUrl(); ?>" class="sidebar-link">
            <i class="bi bi-globe"></i>
            <span>View Website</span>
        </a>
        <a href="<?= baseUrl('auth/logout.php'); ?>" class="sidebar-link" style="color: var(--danger);">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<script>
(function() {
    function showModuleToast(message, isSuccess) {
        let container = document.getElementById('wapiToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'wapiToastContainer';
            container.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 999999; display: flex; flex-direction: column; gap: 10px; pointer-events: none;';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.style.cssText = 'background: ' + (isSuccess ? '#10b981' : '#ef4444') + '; color: #ffffff; padding: 12px 20px; border-radius: 10px; font-size: 0.875rem; font-weight: 500; box-shadow: 0 10px 30px rgba(0,0,0,0.18); display: flex; align-items: center; gap: 10px; pointer-events: auto; opacity: 0; transform: translateY(-10px); transition: all 0.3s ease;';
        toast.innerHTML = '<i class="bi ' + (isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') + '" style="font-size: 1.1rem;"></i> <span>' + message + '</span>';
        container.appendChild(toast);
        setTimeout(function() {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);
        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(function() { toast.remove(); }, 300);
        }, 3200);
    }

    function updateModuleVisuals(moduleName, isEnabled) {
        // Sync all switches on the current page
        document.querySelectorAll('.module-quick-toggle[data-module="' + moduleName + '"]').forEach(function(el) {
            el.checked = !!isEnabled;
        });

        // Update badges
        if (moduleName === 'chatbot_builder') {
            const badges = [
                document.getElementById('badgeChatbotStatus'),
                document.getElementById('dashBadgeChatbot'),
                document.getElementById('topbarChatbotBadge'),
                document.getElementById('badgeChatbotBuilder')
            ];
            badges.forEach(function(badge) {
                if (badge) {
                    if (isEnabled) {
                        badge.className = 'badge rounded-pill bg-success';
                        badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>ACTIVE';
                    } else {
                        badge.className = 'badge rounded-pill bg-secondary';
                        badge.innerHTML = '<i class="bi bi-dash-circle me-1"></i>DISABLED';
                    }
                }
            });
        } else if (moduleName === 'ai_chatbot_builder') {
            const badges = [
                document.getElementById('badgeAIChatbotStatus'),
                document.getElementById('dashBadgeAIChatbot'),
                document.getElementById('headerAIChatbotBadge'),
                document.getElementById('badgeAIChatbotBuilder')
            ];
            badges.forEach(function(badge) {
                if (badge) {
                    if (isEnabled) {
                        badge.className = 'badge rounded-pill text-white';
                        badge.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
                        badge.innerHTML = '<i class="bi bi-stars me-1"></i>ACTIVE';
                    } else {
                        badge.className = 'badge rounded-pill bg-secondary text-white';
                        badge.style.background = '#6c757d';
                        badge.innerHTML = '<i class="bi bi-dash-circle me-1"></i>DISABLED';
                    }
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.module-quick-toggle').forEach(function(toggle) {
            toggle.addEventListener('change', function(e) {
                const moduleName = this.getAttribute('data-module');
                const isChecked = this.checked ? 1 : 0;
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfInput = document.querySelector('input[name="csrf_token"]') || document.querySelector('input[name="_csrf_token"]');
                const csrfToken = (csrfMeta ? csrfMeta.getAttribute('content') : '') || (csrfInput ? csrfInput.value : '');

                updateModuleVisuals(moduleName, isChecked);

                const apiUrl = '<?= baseUrl("api/toggle-module.php"); ?>';

                fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        module: moduleName,
                        status: isChecked,
                        _csrf_token: csrfToken
                    })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.success) {
                        showModuleToast(data.message || 'Module status updated successfully.', true);
                    } else {
                        // Revert visual state
                        updateModuleVisuals(moduleName, isChecked ? 0 : 1);
                        showModuleToast(data.message || 'Failed to update module status.', false);
                    }
                })
                .catch(function(err) {
                    console.error('Module toggle error:', err);
                    updateModuleVisuals(moduleName, isChecked ? 0 : 1);
                    showModuleToast('Network error while updating module status.', false);
                });
            });
        });
    });
})();
</script>

