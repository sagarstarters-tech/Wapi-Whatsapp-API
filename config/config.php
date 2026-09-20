<?php
/**
 * WAPI SaaS Platform - Main Configuration
 * All database, API, and application settings
 */

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $env = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        if (!array_key_exists(trim($name), $_SERVER) && !array_key_exists(trim($name), $_ENV)) {
            putenv(trim($line));
            $_ENV[trim($name)] = trim($value);
            $_SERVER[trim($name)] = trim($value);
        }
    }
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Application Constants
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fallbackUrl = $protocol . "://" . $host;
if (!defined('APP_URL')) define('APP_URL', $_ENV['APP_URL'] ?? $fallbackUrl);
if (!defined('APP_NAME')) define('APP_NAME', $_ENV['APP_NAME'] ?? 'WAPI');
if (!defined('APP_VERSION')) define('APP_VERSION', $_ENV['APP_VERSION'] ?? '1.0.0');
if (!defined('APP_ROOT')) define('APP_ROOT', dirname(__DIR__));
if (!defined('APP_ENV')) define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'wapi_saas');
if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'root');
if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Session Configuration
if (!defined('SESSION_NAME')) define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'WAPI_SESSION');
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME'] ?? 7200);
if (!defined('SESSION_SECURE')) define('SESSION_SECURE', ($_ENV['SESSION_SECURE'] ?? 'false') === 'true');
if (!defined('SESSION_HTTPONLY')) define('SESSION_HTTPONLY', ($_ENV['SESSION_HTTPONLY'] ?? 'true') === 'true');

// Security
if (!defined('CSRF_TOKEN_NAME')) define('CSRF_TOKEN_NAME', '_csrf_token');
if (!defined('HASH_ALGO')) define('HASH_ALGO', PASSWORD_BCRYPT);
if (!defined('HASH_COST')) define('HASH_COST', 12);
if (!defined('JWT_SECRET')) define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'default-secret');
if (!defined('ENCRYPTION_KEY')) define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'default-encryption-key');
if (!defined('WEBHOOK_VERIFY_TOKEN')) define('WEBHOOK_VERIFY_TOKEN', $_ENV['WEBHOOK_VERIFY_TOKEN'] ?? 'default-verify-token');

// File Upload
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', APP_ROOT . '/uploads/');
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? (100 * 1024 * 1024)));
if (!defined('ALLOWED_EXTENSIONS')) define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'mp4', 'mp3']);

// Pagination
if (!defined('ITEMS_PER_PAGE')) define('ITEMS_PER_PAGE', $_ENV['ITEMS_PER_PAGE'] ?? 20);

// Rate Limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900);

// Timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Asia/Kolkata');

// Mail Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 465);
define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? 'ssl');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'WAPI');

// Include autoloader
require_once __DIR__ . '/autoload.php';
