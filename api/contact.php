<?php
/**
 * WAPI SaaS - Contact Form API Handler
 * Receives contact form submissions and stores them in the database
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Rate limiting: max 5 submissions per 10 minutes
if (!rateLimit('contact_form_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many submissions. Please try again later.']);
    exit;
}

// Collect & sanitize input
$firstName = sanitize($_POST['first_name'] ?? '');
$lastName  = sanitize($_POST['last_name'] ?? '');
$email     = sanitizeEmail($_POST['email'] ?? '');
$subject   = sanitize($_POST['subject'] ?? '');
$message   = sanitize($_POST['message'] ?? '');

// Validate required fields
$errors = [];
if (empty($firstName)) $errors[] = 'First name is required.';
if (empty($lastName))  $errors[] = 'Last name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if (empty($subject))   $errors[] = 'Subject is required.';
if (empty($message))   $errors[] = 'Message is required.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $db = Database::getInstance();

    // Auto-create contact_messages table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS `contact_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `first_name` VARCHAR(100) NOT NULL,
        `last_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `subject` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `status` ENUM('unread','read','replied') DEFAULT 'unread',
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_status` (`status`),
        INDEX `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Insert message
    $db->insert('contact_messages', [
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'email'      => $email,
        'subject'    => $subject,
        'message'    => $message,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    echo json_encode(['success' => true, 'message' => 'Your message has been sent successfully! We will get back to you soon.']);

} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again later.']);
}
