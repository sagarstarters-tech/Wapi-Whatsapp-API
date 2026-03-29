<?php
/**
 * WAPI SaaS Platform - Generic Media Upload API Endpoint
 * Handles file uploads from Chatbot Builder
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

// Check if user is logged in (session validation)
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error occurred.']);
    exit;
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileSize = $file['size'];
$fileTmpPath = $file['tmp_name'];

// Validate size
if ($fileSize > MAX_UPLOAD_SIZE) {
    echo json_encode(['status' => 'error', 'message' => 'File size exceeds maximum allowed size (10MB).']);
    exit;
}

// Validate extension
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExtensions = ALLOWED_EXTENSIONS; // From config.php (['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'mp4', 'mp3'])

if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions)]);
    exit;
}

// Create chatbot uploads directory if it doesn't exist
$uploadPath = UPLOAD_DIR . 'chatbot/';
if (!is_dir($uploadPath)) {
    mkdir($uploadPath, 0755, true);
}

// Generate unique file name to avoid overwriting
$newFileName = uniqid('media_') . '_' . time() . '.' . $fileExtension;
$destPath = $uploadPath . $newFileName;

if (move_uploaded_file($fileTmpPath, $destPath)) {
    // Generate public URL
    // e.g. https://domain.com/wapi/uploads/chatbot/media_123.png
    // Strip everything before 'wapi' folder if APP_URL doesn't perfectly resolve subfolder (just basic concatenation)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // We assume the application is accessible from the web root or a subpath
    // $_SERVER['SCRIPT_NAME'] is usually /wapi/api/upload_media.php
    $appPath = dirname(dirname($_SERVER['SCRIPT_NAME'])); // Should be /wapi
    if ($appPath === '/' || $appPath === '\\') $appPath = '';

    $publicUrl = $protocol . '://' . $host . $appPath . '/uploads/chatbot/' . $newFileName;

    echo json_encode([
        'status' => 'success',
        'url' => $publicUrl,
        'message' => 'File uploaded successfully'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to move uploaded file.']);
}
