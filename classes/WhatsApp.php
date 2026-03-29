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
        $this->apiVersion = $settings->get('whatsapp_api_version', 'v18.0');
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
    private function sendMessage($userId, $phoneNumberId, $accessToken, $to, $type, $content, $payload, $mediaUrl = null, $skipChecks = false) {
        $url = "{$this->apiUrl}/{$phoneNumberId}/messages";

        if (!$skipChecks) {
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
     * Handle incoming message
     */
    private function handleIncomingMessage($msg, $phoneNumberId, $userId = null) {
        $from = $msg['from'] ?? '';
        $text = $msg['text']['body'] ?? '';
        $msgType = $msg['type'] ?? 'text';

        // Handle button clicks (interactive replies)
        if ($msgType === 'interactive' && isset($msg['interactive']['button_reply'])) {
            $replyId = $msg['interactive']['button_reply']['id'] ?? '';
            $text = $msg['interactive']['button_reply']['title'] ?? '';
            
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
