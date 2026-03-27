<?php
/**
 * WAPI SaaS - Image Upload API for Chatbot
 * Handles file uploads and returns the URL
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
Auth::requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed']);
}

if (!isset($_FILES['file'])) {
    jsonResponse(['success' => false, 'message' => 'No file uploaded']);
}

$file = $_FILES['file'];
$userId = $_SESSION['user_id'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    jsonResponse(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
}

// Validate size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(['success' => false, 'message' => 'File too large. Max 5MB allowed.']);
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/../uploads/chatbot/' . $userId . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('img_') . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $url = baseUrl('uploads/chatbot/' . $userId . '/' . $filename);
    jsonResponse(['success' => true, 'url' => $url]);
} else {
    jsonResponse(['success' => false, 'message' => 'Failed to save file.']);
}
