<?php
/**
 * WAPI SaaS - Admin AI Settings
 * Configure AI provider API keys, models, and defaults
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireAdmin();

$db = Database::getInstance();
$settings = new Settings();
$hideNav = true;

// Handle AJAX Test Connection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_connection') {
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    if (!CSRF::validateToken()) {
        echo json_encode(['success' => false, 'message' => 'CSRF security token expired. Please refresh the page.']);
        exit;
    }

    $provider = sanitize($_POST['provider'] ?? '');
    $apiKey = trim($_POST['api_key'] ?? '');

    try {
        $startTime = microtime(true);
        $result = AIModelAdapter::testProvider($provider, $apiKey);
        $durationMs = round((microtime(true) - $startTime) * 1000);

        echo json_encode([
            'success' => true,
            'message' => 'Connected successfully!',
            'model' => $result['model'] ?? $provider,
            'reply' => $result['content'] ?? '',
            'latency_ms' => $durationMs,
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle POST - Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRF::validateToken()) {
    $aiSettings = [
        'ai_openai_api_key' => sanitize($_POST['ai_openai_api_key'] ?? ''),
        'ai_openai_enabled' => isset($_POST['ai_openai_enabled']) ? '1' : '0',
        'ai_gemini_api_key' => sanitize($_POST['ai_gemini_api_key'] ?? ''),
        'ai_gemini_enabled' => isset($_POST['ai_gemini_enabled']) ? '1' : '0',
        'ai_claude_api_key' => sanitize($_POST['ai_claude_api_key'] ?? ''),
        'ai_claude_enabled' => isset($_POST['ai_claude_enabled']) ? '1' : '0',
        'ai_custom_enabled' => isset($_POST['ai_custom_enabled']) ? '1' : '0',
        'ai_default_model' => sanitize($_POST['ai_default_model'] ?? 'gpt-4o'),
        'ai_max_tokens_per_response' => sanitizeInt($_POST['ai_max_tokens_per_response'] ?? 1024),
        'ai_max_context_messages' => sanitizeInt($_POST['ai_max_context_messages'] ?? 10),
        'ai_rate_limit_default' => sanitizeInt($_POST['ai_rate_limit_default'] ?? 100),
        'ai_default_system_prompt' => sanitize($_POST['ai_default_system_prompt'] ?? ''),
        'enable_ai_chatbot_builder' => isset($_POST['enable_ai_chatbot_builder']) ? '1' : '0',
    ];

    foreach ($aiSettings as $key => $value) {
        // Don't overwrite API keys with empty values (preserve existing)
        if (in_array($key, ['ai_openai_api_key', 'ai_gemini_api_key', 'ai_claude_api_key']) && empty($value)) {
            continue;
        }
        $exists = $db->exists('settings', 'setting_key = ?', [$key]);
        if ($exists) {
            $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
        } else {
            $db->insert('settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_group' => 'ai',
                'setting_type' => 'text'
            ]);
        }
    }

    setFlash('success', 'AI settings saved successfully!');
    redirect('admin/ai-settings.php');
}

// Load current settings
$aiOpenaiKey = $settings->get('ai_openai_api_key', '');
$aiOpenaiEnabled = $settings->get('ai_openai_enabled', '1');
$aiGeminiKey = $settings->get('ai_gemini_api_key', '');
$aiGeminiEnabled = $settings->get('ai_gemini_enabled', '1');
$aiClaudeKey = $settings->get('ai_claude_api_key', '');
$aiClaudeEnabled = $settings->get('ai_claude_enabled', '0');
$aiCustomEnabled = $settings->get('ai_custom_enabled', '1');
$aiDefaultModel = $settings->get('ai_default_model', 'gpt-4o');
$aiMaxTokens = $settings->get('ai_max_tokens_per_response', '1024');
$aiMaxContext = $settings->get('ai_max_context_messages', '10');
$aiRateLimit = $settings->get('ai_rate_limit_default', '100');
$aiDefaultPrompt = $settings->get('ai_default_system_prompt', 'You are a helpful customer support assistant. Answer questions based on the provided knowledge base. If you cannot find the answer, politely let the customer know and offer to connect them with a human agent.');

// Stats
try { $totalBots = $db->count('ai_bots', '1'); } catch (Exception $e) { $totalBots = 0; }
try { $activeBots = $db->count('ai_bots', "status = 'active'"); } catch (Exception $e) { $activeBots = 0; }
try { $totalConversations = $db->count('ai_conversations', '1'); } catch (Exception $e) { $totalConversations = 0; }

$pageTitle = 'AI Settings';
$extraCss = [asset('assets/css/dashboard.css')];
$extraJs = [asset('assets/js/admin.js')];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

    <main class="main-content">
        <div class="dash-header">
            <div>
                <h1 class="dash-title">🤖 AI Settings</h1>
                <div class="dash-breadcrumb">
                    <a href="<?= baseUrl('admin/'); ?>">Admin</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>AI Settings</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary btn-sm d-lg-none" id="mobileSidebarToggle"><i class="bi bi-list"></i></button>
            </div>
        </div>

        <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type']; ?> fade-in"><i class="bi bi-check-circle-fill"></i> <?= e($flash['message']); ?></div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="bi bi-robot"></i></div>
                    <div>
                        <div class="stat-value"><?= $totalBots; ?></div>
                        <div class="stat-label">Total AI Bots</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-value"><?= $activeBots; ?></div>
                        <div class="stat-label">Active Bots</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="bi bi-chat-dots-fill"></i></div>
                    <div>
                        <div class="stat-value"><?= formatNumber($totalConversations); ?></div>
                        <div class="stat-label">Total Conversations</div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <?= CSRF::tokenField(); ?>
            <!-- Master AI ChatBot Builder Module Toggle -->
            <div class="card mb-4" style="border-radius: var(--border-radius); border-left: 4px solid #667eea;">
                <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 text-white d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; font-size: 1.4rem; background: linear-gradient(135deg, #667eea, #764ba2);">
                            <i class="bi bi-stars"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">AI ChatBot Builder Module</h5>
                            <div class="text-muted small">Enable or disable AI ChatBot Builder, AI Analytics, and AI Conversations across the platform.</div>
                        </div>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="enable_ai_chatbot_builder" id="masterAIChatbotSwitch" <?= $settings->get('enable_ai_chatbot_builder', '1') === '1' ? 'checked' : ''; ?> style="width: 3rem; height: 1.6rem; cursor: pointer;">
                        <label class="form-check-label fw-semibold ms-1" for="masterAIChatbotSwitch">Enabled</label>
                    </div>
                </div>
            </div>

            <!-- OpenAI -->
            <div class="card mb-4" style="border-radius: var(--border-radius);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-cpu text-success me-2"></i>OpenAI (GPT-4o / GPT-4.1)</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ai_openai_enabled" id="openaiEnabled" <?= $aiOpenaiEnabled === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="openaiEnabled">Enabled</label>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">API Key</label>
                            <div class="input-group">
                                <input type="password" name="ai_openai_api_key" class="form-control" value="<?= e($aiOpenaiKey); ?>" placeholder="sk-..." id="openaiKey">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('openaiKey')"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="form-text">Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></div>
                        </div>
                        <div class="col-12 pt-2 border-top">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div id="openaiTestResult" class="flex-grow-1"></div>
                                <button type="button" class="btn btn-sm btn-outline-success" id="btnTestOpenai" onclick="testAIProvider('openai')">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Test OpenAI Connection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gemini -->
            <div class="card mb-4" style="border-radius: var(--border-radius);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-stars text-primary me-2"></i>Google Gemini</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ai_gemini_enabled" id="geminiEnabled" <?= $aiGeminiEnabled === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="geminiEnabled">Enabled</label>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">API Key</label>
                            <div class="input-group">
                                <input type="password" name="ai_gemini_api_key" class="form-control" value="<?= e($aiGeminiKey); ?>" placeholder="AIza..." id="geminiKey">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('geminiKey')"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="form-text">Get your API key from <a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a></div>
                        </div>
                        <div class="col-12 pt-2 border-top">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div id="geminiTestResult" class="flex-grow-1"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnTestGemini" onclick="testAIProvider('gemini')">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Test Gemini Connection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Claude -->
            <div class="card mb-4" style="border-radius: var(--border-radius);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-lightning-fill me-2" style="color: #cc7832;"></i>Anthropic Claude</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ai_claude_enabled" id="claudeEnabled" <?= $aiClaudeEnabled === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="claudeEnabled">Enabled</label>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">API Key</label>
                            <div class="input-group">
                                <input type="password" name="ai_claude_api_key" class="form-control" value="<?= e($aiClaudeKey); ?>" placeholder="sk-ant-..." id="claudeKey">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('claudeKey')"><i class="bi bi-eye"></i></button>
                            </div>
                            <div class="form-text">Get your API key from <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></div>
                        </div>
                        <div class="col-12 pt-2 border-top">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div id="claudeTestResult" class="flex-grow-1"></div>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="btnTestClaude" onclick="testAIProvider('claude')">
                                    <i class="bi bi-lightning-charge-fill me-1"></i> Test Claude Connection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom API -->
            <div class="card mb-4" style="border-radius: var(--border-radius);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-plug-fill text-info me-2"></i>Custom OpenAI-Compatible API</h5>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ai_custom_enabled" id="customEnabled" <?= $aiCustomEnabled === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="customEnabled">Enabled</label>
                        </div>
                    </div>
                    <p class="text-muted" style="font-size: 0.875rem;">Allow users to connect their own OpenAI-compatible API endpoints (e.g., local LLMs, Azure OpenAI, etc.)</p>
                </div>
            </div>

            <!-- Defaults -->
            <div class="card mb-4" style="border-radius: var(--border-radius);">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-gear-fill text-secondary me-2"></i>Default Configuration</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Default Model</label>
                            <select name="ai_default_model" class="form-select">
                                <option value="gpt-4o" <?= $aiDefaultModel === 'gpt-4o' ? 'selected' : ''; ?>>GPT-4o</option>
                                <option value="gpt-4.1" <?= $aiDefaultModel === 'gpt-4.1' ? 'selected' : ''; ?>>GPT-4.1</option>
                                <option value="gemini" <?= $aiDefaultModel === 'gemini' ? 'selected' : ''; ?>>Gemini</option>
                                <option value="claude" <?= $aiDefaultModel === 'claude' ? 'selected' : ''; ?>>Claude</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Tokens/Response</label>
                            <input type="number" name="ai_max_tokens_per_response" class="form-control" value="<?= e($aiMaxTokens); ?>" min="100" max="4096">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Context Messages</label>
                            <input type="number" name="ai_max_context_messages" class="form-control" value="<?= e($aiMaxContext); ?>" min="1" max="50">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rate Limit (msg/min)</label>
                            <input type="number" name="ai_rate_limit_default" class="form-control" value="<?= e($aiRateLimit); ?>" min="1" max="10000">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Default System Prompt</label>
                            <textarea name="ai_default_system_prompt" class="form-control" rows="4" style="font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.8125rem;"><?= e($aiDefaultPrompt); ?></textarea>
                            <div class="form-text">This prompt is used when users don't provide their own system prompt.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="<?= baseUrl('admin/'); ?>" class="btn btn-outline-primary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Settings</button>
            </div>
        </form>
    </main>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function testAIProvider(provider) {
    const keyInput = document.getElementById(provider + 'Key');
    const resultDiv = document.getElementById(provider + 'TestResult');
    const btn = document.getElementById('btnTest' + provider.charAt(0).toUpperCase() + provider.slice(1));
    
    if (!keyInput || !resultDiv || !btn) {
        console.error('Test provider elements missing for:', provider);
        return;
    }
    
    const apiKey = keyInput.value.trim();
    if (!apiKey) {
        resultDiv.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-circle-fill me-1"></i> Please enter an API key first.</span>';
        return;
    }
    
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing...';
    resultDiv.innerHTML = '<span class="text-muted small"><i class="bi bi-hourglass-split me-1"></i> Connecting to ' + provider.toUpperCase() + ' servers...</span>';
    
    // Retrieve CSRF token from page DOM or fallback to PHP helper
    const csrfInput = document.querySelector('input[name="<?= CSRF_TOKEN_NAME; ?>"]') || document.querySelector('input[name="csrf_token"]');
    const csrfVal = csrfInput ? csrfInput.value : '<?= CSRF::getToken(); ?>';

    const formData = new FormData();
    formData.append('action', 'test_connection');
    formData.append('provider', provider);
    formData.append('api_key', apiKey);
    formData.append('<?= CSRF_TOKEN_NAME; ?>', csrfVal);
    formData.append('csrf_token', csrfVal);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Non-JSON response from server:', text);
            throw new Error('Server error: ' + (text.substring(0, 150) || response.statusText));
        }
    })
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success py-2 px-3 mb-0 d-inline-block border-0 shadow-sm" style="font-size: 0.85rem; border-radius: 8px;">
                    <div class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> Connection Successful! (${data.latency_ms}ms)</div>
                    <div class="text-secondary small mt-1"><strong>Model:</strong> ${data.model} | <strong>Reply:</strong> "${data.reply}"</div>
                </div>`;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger py-2 px-3 mb-0 d-inline-block border-0 shadow-sm" style="font-size: 0.85rem; border-radius: 8px;">
                    <div class="fw-bold text-danger"><i class="bi bi-x-circle-fill me-1"></i> Connection Failed!</div>
                    <div class="text-danger small mt-1">${data.message}</div>
                </div>`;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        resultDiv.innerHTML = '<span class="text-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i> ' + err.message + '</span>';
    });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
