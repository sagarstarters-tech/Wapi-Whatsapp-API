<?php
/**
 * WhatsApp Cloud API Integration Class
 * Handles all communication with Meta's WhatsApp Business API
 */
class WhatsApp {
    private $db;
    private $apiUrl;
    private $apiVersion;

    public function __construct() {
        $this->db = Database::getInstance();
        $settings = new Settings();
        $this->apiVersion = $settings->get('whatsapp_api_version', 'v17.0');
        $this->apiUrl = $settings->get('whatsapp_api_url', 'https://graph.facebook.com') . '/' . $this->apiVersion;
    }

    /**
     * Send a text message
     */
    public function sendText($userId, $phoneNumberId, $accessToken, $to, $message) {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhone($to),
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $message]
        ];

        return $this->sendMessage($userId, $phoneNumberId, $accessToken, $to, 'text', $message, $payload);
    }

    /**
     * Send an image message
     */
    public function sendImage($userId, $phoneNumberId, $accessToken, $to, $imageUrl, $caption = '') {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhone($to),
            'type' => 'image',
            'image' => ['link' => $imageUrl, 'caption' => $caption]
        ];

        return $this->sendMessage($userId, $phoneNumberId, $accessToken, $to, 'image', $caption, $payload, $imageUrl);
    }

    /**
     * Send a video message
     */
    public function sendVideo($userId, $phoneNumberId, $accessToken, $to, $videoUrl, $caption = '') {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhone($to),
            'type' => 'video',
            'video' => ['link' => $videoUrl, 'caption' => $caption]
        ];

        return $this->sendMessage($userId, $phoneNumberId, $accessToken, $to, 'video', $caption, $payload, $videoUrl);
    }

    /**
     * Send a document message
     */
    public function sendDocument($userId, $phoneNumberId, $accessToken, $to, $docUrl, $filename = '', $caption = '') {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhone($to),
            'type' => 'document',
            'document' => ['link' => $docUrl, 'caption' => $caption, 'filename' => $filename]
        ];

        return $this->sendMessage($userId, $phoneNumberId, $accessToken, $to, 'document', $caption, $payload, $docUrl);
    }

    /**
     * Send a template message
     */
    public function sendTemplate($userId, $phoneNumberId, $accessToken, $to, $templateName, $language = 'en', $components = []) {
        $template = ['name' => $templateName, 'language' => ['code' => $language]];
        if (!empty($components)) {
            $template['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhone($to),
            'type' => 'template',
            'template' => $template
        ];

        return $this->sendMessage($userId, $phoneNumberId, $accessToken, $to, 'template', $templateName, $payload);
    }

    /**
     * Core message sending method
     */
    private function sendMessage($userId, $phoneNumberId, $accessToken, $to, $type, $content, $payload, $mediaUrl = null) {
        $url = "{$this->apiUrl}/{$phoneNumberId}/messages";

        // Check subscription
        $sub = $this->db->fetch("SELECT id FROM subscriptions WHERE user_id = ? AND status = 'active' AND expires_at > NOW() LIMIT 1", [$userId]);
        if (!$sub) {
            return ['success' => false, 'message' => 'Subscription expired or inactive. Please renew your plan.'];
        }

        // Check credits
        $credits = $this->db->fetch("SELECT total_credits, used_credits FROM credits WHERE user_id = ?", [$userId]);
        if ($credits && ($credits['total_credits'] - $credits['used_credits']) <= 0) {
            return ['success' => false, 'message' => 'Insufficient credits. Please upgrade your plan.'];
        }

        // Log message
        $contactId = $this->db->fetchColumn("SELECT id FROM contacts WHERE user_id = ? AND phone = ?", [$userId, $this->formatPhone($to)]) ?: null;
        $waAccount = $this->db->fetchColumn("SELECT id FROM whatsapp_accounts WHERE user_id = ? AND phone_number_id = ?", [$userId, $phoneNumberId]) ?: null;

        $messageId = $this->db->insert('messages', [
            'user_id' => $userId,
            'whatsapp_account_id' => $waAccount,
            'contact_id' => $contactId,
            'to_number' => $this->formatPhone($to),
            'type' => $type,
            'content' => $content,
            'media_url' => $mediaUrl,
            'template_name' => $type === 'template' ? $content : null,
            'status' => 'queued',
            'direction' => 'outbound'
        ]);

        // Make API call
        $response = $this->makeApiCall($url, $payload, $accessToken);

        if ($response['success']) {
            // Update message status
            $waMessageId = $response['data']['messages'][0]['id'] ?? null;
            $this->db->update('messages', [
                'message_id' => $waMessageId,
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$messageId]);

            // Deduct credits
            $this->deductCredit($userId, $messageId);

            return ['success' => true, 'message' => 'Message sent successfully!', 'message_id' => $waMessageId];
        } else {
            $this->db->update('messages', [
                'status' => 'failed',
                'error_message' => $response['message']
            ], 'id = ?', [$messageId]);

            return ['success' => false, 'message' => $response['message']];
        }
    }

    /**
     * Send bulk messages
     */
    public function sendBulk($userId, $phoneNumberId, $accessToken, $contacts, $type, $content, $mediaUrl = null) {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($contacts as $contact) {
            $phone = is_array($contact) ? $contact['phone'] : $contact;
            
            if ($type === 'text') {
                $result = $this->sendText($userId, $phoneNumberId, $accessToken, $phone, $content);
            } elseif ($type === 'image') {
                $result = $this->sendImage($userId, $phoneNumberId, $accessToken, $phone, $mediaUrl, $content);
            } elseif ($type === 'template') {
                $result = $this->sendTemplate($userId, $phoneNumberId, $accessToken, $phone, $content);
            } else {
                $result = $this->sendText($userId, $phoneNumberId, $accessToken, $phone, $content);
            }

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "$phone: " . $result['message'];
            }

            // Rate limiting: 80 messages per second (Meta limit)
            usleep(15000); // 15ms delay
        }

        return $results;
    }

    /**
     * Process incoming webhook
     */
    public function processWebhook($payload, $userId = null) {
        // Log webhook
        $this->db->insert('webhook_logs', [
            'user_id' => $userId,
            'event_type' => 'incoming',
            'payload' => json_encode($payload),
            'status' => 'received'
        ]);

        if (!isset($payload['entry'])) return;

        foreach ($payload['entry'] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Status updates
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->updateMessageStatus($status);
                    }
                }

                // Incoming messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $msg) {
                        $this->handleIncomingMessage($msg, $value['metadata']['phone_number_id'] ?? '', $userId);
                    }
                }
            }
        }
    }

    /**
     * Update message delivery status
     */
    private function updateMessageStatus($status) {
        $waMessageId = $status['id'] ?? '';
        $newStatus = $status['status'] ?? '';

        if ($waMessageId && in_array($newStatus, ['sent', 'delivered', 'read', 'failed'])) {
            $updateData = ['status' => $newStatus];
            if ($newStatus === 'delivered') $updateData['delivered_at'] = date('Y-m-d H:i:s');
            if ($newStatus === 'read') $updateData['read_at'] = date('Y-m-d H:i:s');
            if ($newStatus === 'failed') $updateData['error_message'] = json_encode($status['errors'] ?? []);

            $this->db->update('messages', $updateData, 'message_id = ?', [$waMessageId]);
        }
    }

    /**
     * Handle incoming message (for auto-reply / chatbot)
     */
    private function handleIncomingMessage($msg, $phoneNumberId, $userId = null) {
        $from = $msg['from'] ?? '';
        $text = $msg['text']['body'] ?? '';
        $msgType = $msg['type'] ?? 'text';

        // Handle Quick Reply Buttons
        if ($msgType === 'interactive' && isset($msg['interactive']['button_reply'])) {
            $replyId = $msg['interactive']['button_reply']['id'] ?? '';
            $text = $msg['interactive']['button_reply']['title'] ?? '';

            if (strpos($replyId, 'flow_btn_') === 0) {
                $parts = explode('_', $replyId);
                $nodeId = $parts[2] . '_' . $parts[3] . '_' . $parts[4];
                $btnIdx = $parts[5];
                
                if (!$userId) {
                    $account = $this->db->fetch("SELECT user_id, access_token FROM whatsapp_accounts WHERE phone_number_id = ? AND status = 'active'", [$phoneNumberId]);
                    if ($account) {
                        $this->processFlowButtonClick($account['user_id'], $phoneNumberId, $account['access_token'], $from, $nodeId, $btnIdx);
                    }
                }
            }
        }

        // Handle List Response (interactive menu click)
        if ($msgType === 'interactive' && isset($msg['interactive']['list_reply'])) {
            $replyId = $msg['interactive']['list_reply']['id'] ?? '';
            $text = $msg['interactive']['list_reply']['title'] ?? '';

            if (strpos($replyId, 'flow_btn_') === 0) {
                $parts = explode('_', $replyId);
                $nodeId = $parts[2] . '_' . $parts[3] . '_' . $parts[4];
                $btnIdx = $parts[5];
                
                if (!$userId) {
                    $account = $this->db->fetch("SELECT user_id, access_token FROM whatsapp_accounts WHERE phone_number_id = ? AND status = 'active'", [$phoneNumberId]);
                    if ($account) {
                        $this->processFlowButtonClick($account['user_id'], $phoneNumberId, $account['access_token'], $from, $nodeId, $btnIdx);
                    }
                }
            }
        }

        // Find user by phone_number_id
        if (!$userId) {
            $account = $this->db->fetch("SELECT user_id, access_token FROM whatsapp_accounts WHERE phone_number_id = ? AND status = 'active'", [$phoneNumberId]);
            if (!$account) return;
            $userId = $account['user_id'];
        }

        // Log incoming message
        $this->db->insert('messages', [
            'user_id' => $userId,
            'message_id' => $msg['id'] ?? null,
            'to_number' => $from,
            'type' => $msgType,
            'content' => $text,
            'status' => 'delivered',
            'direction' => 'inbound'
        ]);

        // Check chatbot auto-reply or User Input Capture
        if ($msgType === 'text') {
            $this->processIncomingText($userId, $phoneNumberId, $from, $text);
        }
    }

    /**
     * Centralized Entrance for incoming text
     */
    private function processIncomingText($userId, $phoneNumberId, $from, $incomingText) {
        $accessToken = $this->db->fetchColumn("SELECT access_token FROM whatsapp_accounts WHERE phone_number_id = ?", [$phoneNumberId]);
        if (!$accessToken) return;

        // 1. Check if user is in an active session (waiting for input)
        $session = $this->db->fetch("SELECT * FROM chatbot_sessions WHERE user_id = ? AND phone = ?", [$userId, $from]);
        if ($session && !empty($session['current_node'])) {
            $this->handleUserInput($userId, $phoneNumberId, $accessToken, $from, $session, $incomingText);
            return;
        }

        // 2. Otherwise handle as normal auto-reply keyword trigger
        $this->processAutoReply($userId, $phoneNumberId, $from, $incomingText);
    }

    /**
     * Handle User Input Node (Capturing Variable)
     */
    private function handleUserInput($userId, $phoneNumberId, $accessToken, $to, $session, $text) {
        $flow = $this->db->fetch("SELECT response_content FROM chatbot_flows WHERE id = ?", [$session['flow_id']]);
        if (!$flow) return;
        $flowData = json_decode($flow['response_content'], true);
        $nodeId = $session['current_node'];
        $node = $flowData['nodes'][$nodeId] ?? null;

        if ($node && $node['type'] === 'user_input') {
            $varName = $node['data']['variable'] ?? 'input';
            
            // Store lead/variable
            $this->db->insert('chatbot_leads', [
                'user_id' => $userId,
                'phone' => $to,
                'variable_name' => $varName,
                'variable_value' => $text
            ]);

            // Clear session to allow next flow parts
            $this->db->query("UPDATE chatbot_sessions SET current_node = NULL WHERE id = ?", [$session['id']]);

            // Move to next node
            $this->executeFlowStep($userId, $phoneNumberId, $accessToken, $to, $flowData, $nodeId);
        }
    }

    /**
     * Handle button click in a flow
     */
    private function processFlowButtonClick($userId, $phoneNumberId, $accessToken, $to, $nodeId, $btnIdx) {
        // Find the flow this node belongs to
        $flow = $this->db->fetch("SELECT response_content FROM chatbot_flows WHERE user_id = ? AND response_content LIKE ?", [$userId, "%$nodeId%"]);
        if (!$flow) return;

        $flowData = json_decode($flow['response_content'], true);
        if (!$flowData) return;

        $node = $flowData['nodes'][$nodeId] ?? null;
        if (!$node) return;

        // 1. Check if there's a specific connection for this button index
        $portId = "btn_{$btnIdx}";
        $connections = array_filter($flowData['connections'] ?? [], function($c) use ($nodeId, $portId) {
            return $c['fromNode'] === $nodeId && $c['fromPort'] === $portId;
        });

        // 2. Fallback to generic 'out' port if no specific button connection
        if (empty($connections)) {
             $connections = array_filter($flowData['connections'] ?? [], function($c) use ($nodeId) {
                return $c['fromNode'] === $nodeId && $c['fromPort'] === 'out';
            });
        }

        foreach ($connections as $conn) {
            $nextNodeId = $conn['toNode'];
            $nextNode = $flowData['nodes'][$nextNodeId] ?? null;
            if ($nextNode) {
                $this->processNode($userId, $phoneNumberId, $accessToken, $to, $flowData, $nextNode);
            }
        }
    }

    /**
     * Process chatbot auto-replies
     */
    private function processAutoReply($userId, $phoneNumberId, $to, $incomingText) {
        $flows = $this->db->fetchAll(
            "SELECT * FROM chatbot_flows WHERE user_id = ? AND is_active = 1 ORDER BY priority DESC",
            [$userId]
        );

        $incomingText = strtolower(trim($incomingText));

        foreach ($flows as $flow) {
            $matched = false;
            $keywords = explode(',', strtolower($flow['trigger_keyword']));
            $matchType = $flow['match_type'];

            foreach ($keywords as $kw) {
                $kw = trim($kw);
                if (empty($kw)) continue;

                if ($matchType === 'exact') {
                    if ($incomingText === $kw) $matched = true;
                } elseif ($matchType === 'contains') {
                    if (strpos($incomingText, $kw) !== false) $matched = true;
                } elseif ($matchType === 'starts_with') {
                    if (strpos($incomingText, $kw) === 0) $matched = true;
                }
                if ($matched) break;
            }

            if ($matched) {
                $account = $this->db->fetch("SELECT access_token FROM whatsapp_accounts WHERE user_id = ? AND phone_number_id = ?", [$userId, $phoneNumberId]);
                if (!$account) continue;

                $flowData = json_decode($flow['response_content'], true);
                if (!$flowData) continue;

                // Start from the 'node_start'
                $this->executeFlowStep($userId, $phoneNumberId, $account['access_token'], $to, $flowData, 'node_start');
                break; // Only first matching flow
            }
        }
    }

    /**
     * Replace {{variable}} with actual values from chatbot_leads
     */
    private function replaceVariables($userId, $to, $text) {
        $leads = $this->db->fetchAll("SELECT variable_name, variable_value FROM chatbot_leads WHERE user_id = ? AND phone = ?", [$userId, $to]);
        foreach ($leads as $lead) {
            $text = str_replace('{{' . $lead['variable_name'] . '}}', $lead['variable_value'], $text);
        }
        return $text;
    }

    /**
     * Execute a specific node in the chatbot flow
     */
    private function executeFlowStep($userId, $phoneNumberId, $accessToken, $to, $flowData, $nodeId, $port = null) {
        // Find connections from this node
        $connections = array_filter($flowData['connections'] ?? [], function($c) use ($nodeId, $port) {
            if ($port !== null) {
                return $c['fromNode'] === $nodeId && $c['fromPort'] === $port;
            }
            return $c['fromNode'] === $nodeId;
        });

        foreach ($connections as $conn) {
            $nextNodeId = $conn['toNode'];
            $nextNode = $flowData['nodes'][$nextNodeId] ?? null;
            if (!$nextNode) continue;

            $this->processNode($userId, $phoneNumberId, $accessToken, $to, $flowData, $nextNode);
        }
    }

    /**
     * Process and send content for a specific node types
     */
    private function processNode($userId, $phoneNumberId, $accessToken, $to, $flowData, $node) {
        $data = $node['data'] ?? [];
        $delay = isset($data['delay']) ? (int)$data['delay'] : 0;
        if ($delay > 0) sleep($delay);

        switch ($node['type']) {
            case 'text':
                $msg = $this->replaceVariables($userId, $to, $data['message'] ?? '');
                $this->sendText($userId, $phoneNumberId, $accessToken, $to, $msg);
                $this->executeFlowStep($userId, $phoneNumberId, $accessToken, $to, $flowData, $node['id']);
                break;

            case 'image':
                $this->sendImage($userId, $phoneNumberId, $accessToken, $to, $data['url'] ?? '', $data['caption'] ?? '');
                $this->executeFlowStep($userId, $phoneNumberId, $accessToken, $to, $flowData, $node['id']);
                break;

            case 'button':
            case 'interactive':
                $this->sendInteractiveMessage($userId, $phoneNumberId, $accessToken, $to, $node);
                // Connections from buttons are handled by Meta callbacks (Interactive Webhooks)
                break;

            case 'video':
                $this->sendVideo($userId, $phoneNumberId, $accessToken, $to, $data['url'] ?? '', $data['caption'] ?? '');
                $this->executeFlowStep($userId, $phoneNumberId, $accessToken, $to, $flowData, $node['id']);
                break;

            case 'file':
                $this->sendDocument($userId, $phoneNumberId, $accessToken, $to, $data['url'] ?? '', $data['filename'] ?? '');
                $this->executeFlowStep($userId, $phoneNumberId, $accessToken, $to, $flowData, $node['id']);
                break;

            case 'user_input':
                // Record session that we are waiting for input from this node
                $this->db->query("INSERT INTO chatbot_sessions (user_id, phone, flow_id, current_node) VALUES (?, ?, (SELECT id FROM chatbot_flows WHERE user_id = ? AND response_content LIKE ? LIMIT 1), ?) 
                                  ON DUPLICATE KEY UPDATE current_node = ?, flow_id = (SELECT id FROM chatbot_flows WHERE user_id = ? AND response_content LIKE ? LIMIT 1)", 
                                  [$userId, $to, $userId, "%".$node['id']."%", $node['id'], $node['id'], $userId, "%".$node['id']."%"]);
                
                $this->sendText($userId, $phoneNumberId, $accessToken, $to, $data['message'] ?? 'Please response:');
                break;

            case 'condition':
                $result = $this->handleConditionNode($userId, $to, $node);
                $port = $result ? 'true' : 'false';
                $this->executeFlowStep($userId, $phoneNumberId, $accessToken, $to, $flowData, $node['id'], $port);
                break;

            case 'delay':
                $duration = (int)($data['duration'] ?? 5);
                $unit = $data['unit'] ?? 'Sec';
                if ($unit === 'Min') $duration *= 60;
                if ($unit === 'Hour') $duration *= 3600;
                
                // For long delays, we would normally use a scheduler, but for small ones sleep is fine.
                // Limit sleep to avoid script timeout
                if ($duration > 30) $duration = 30; 
                
                sleep($duration);
                $this->executeFlowStep($userId, $phoneNumberId, $accessToken, $to, $flowData, $node['id']);
                break;
        }
    }

    /**
     * Logical If/Else handling for Condition Node
     */
    private function handleConditionNode($userId, $to, $node) {
        $data = $node['data'] ?? [];
        $varName = $data['variable'] ?? '';
        $operator = $data['operator'] ?? 'equals';
        $expectedValue = $data['value'] ?? '';

        // Fetch the stored variable value
        $actualValue = $this->db->fetchColumn("SELECT variable_value FROM chatbot_leads WHERE user_id = ? AND phone = ? AND variable_name = ? ORDER BY created_at DESC", [$userId, $to, $varName]);
        
        if ($actualValue === false) return false;

        switch ($operator) {
            case 'equals': return strtolower($actualValue) == strtolower($expectedValue);
            case 'contains': return strpos(strtolower($actualValue), strtolower($expectedValue)) !== false;
            case 'starts_with': return strpos(strtolower($actualValue), strtolower($expectedValue)) === 0;
            default: return false;
        }
    }

    /**
     * Send Interactive (Buttons/List) via WhatsApp API
     */
    private function sendInteractiveMessage($userId, $phoneNumberId, $accessToken, $to, $node) {
        $data = $node['data'] ?? [];
        $url = $data['url'] ?? '';
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhone($to),
            'type' => 'interactive',
            'interactive' => [
                'body' => ['text' => $this->replaceVariables($userId, $to, $data['message'] ?? 'Please select an option:')]
            ]
        ];

        // If card has description/footer
        if (!empty($data['description'])) {
            $payload['interactive']['footer'] = ['text' => mb_substr($data['description'], 0, 60)];
        }

        // Check if we should send CTA URL button OR Reply buttons
        if (!empty($url)) {
            // WhatsApp Call-to-Action (Link) button
            $payload['interactive']['type'] = 'cta_url';
            $payload['interactive']['action'] = [
                'name' => 'cta_url',
                'parameters' => [
                    'display_text' => mb_substr(($data['buttons'][0] ?? 'Visit Website'), 0, 20),
                    'url' => $url
                ]
            ];
        } else if (($data['display_type'] ?? 'button') === 'list') {
            // WhatsApp List Message (Menu)
            $options = $data['buttons'] ?? [];
            $rows = [];
            foreach ($options as $i => $btnText) {
                if ($i >= 10) break;
                $rows[] = [
                    'id' => "flow_btn_{$node['id']}_{$i}",
                    'title' => mb_substr($btnText, 0, 24)
                ];
            }
            $payload['interactive']['type'] = 'list';
            $payload['interactive']['action'] = [
                'button' => 'Select Options',
                'sections' => [
                    [
                        'title' => mb_substr($data['message'] ?? 'Options', 0, 24),
                        'rows' => $rows
                    ]
                ]
            ];
        } else {
            // Standard Quick Reply buttons (up to 3)
            $buttons = $data['buttons'] ?? ['Yes'];
            $btnConfig = [];
            foreach ($buttons as $i => $btnText) {
                if ($i >= 3) break;
                $btnConfig[] = [
                    'type' => 'reply',
                    'reply' => ['id' => "flow_btn_{$node['id']}_{$i}", 'title' => mb_substr($btnText, 0, 20)]
                ];
            }
            $payload['interactive']['type'] = 'button';
            $payload['interactive']['action'] = ['buttons' => $btnConfig];
        }

        return $this->sendMessage($userId, $phoneNumberId, $accessToken, $to, 'interactive', $data['message'] ?? 'Interactive', $payload);
    }

    /**
     * Deduct credit for a message
     */
    private function deductCredit($userId, $messageId) {
        $this->db->query("UPDATE credits SET used_credits = used_credits + 1 WHERE user_id = ?", [$userId]);

        $credits = $this->db->fetch("SELECT total_credits, used_credits FROM credits WHERE user_id = ?", [$userId]);
        $balance = $credits ? ($credits['total_credits'] - $credits['used_credits']) : 0;

        $this->db->insert('credit_transactions', [
            'user_id' => $userId,
            'type' => 'debit',
            'amount' => 1,
            'balance_after' => $balance,
            'description' => 'Message sent',
            'reference_id' => $messageId
        ]);

        // Low credit warning
        if ($balance <= 50 && $balance > 0) {
            $this->db->insert('notifications', [
                'user_id' => $userId,
                'type' => 'warning',
                'title' => 'Low Credits',
                'message' => "You have only {$balance} credits remaining. Please upgrade your plan to continue sending messages.",
                'link' => '/dashboard/subscription.php'
            ]);
        }
    }

    /**
     * Format phone number (remove spaces, dashes, add country code)
     */
    private function formatPhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '91' . substr($phone, 1); // Default India
        }
        $phone = ltrim($phone, '+');
        return $phone;
    }

    /**
     * Make API call to Meta's WhatsApp API
     */
    private function makeApiCall($url, $data, $accessToken) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'API connection error: ' . $error, 'data' => null];
        }

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'Success', 'data' => $result];
        } else {
            $errorMsg = $result['error']['message'] ?? 'Unknown API error (HTTP ' . $httpCode . ')';
            return ['success' => false, 'message' => $errorMsg, 'data' => $result];
        }
    }
}
