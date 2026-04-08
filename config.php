<?php
// ===============================
// Environment Detection (early — needed for DB config)
// ===============================
$_serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
define('IS_LOCAL', (
    $_serverName === 'localhost' ||
    $_serverName === '127.0.0.1' ||
    str_contains($_serverName, '192.168.') ||
    str_contains($_serverName, '172.')
));

// ===============================
// Database Configuration
// ===============================
if (IS_LOCAL) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'realm_shop');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    define('DB_HOST', 'sql100.infinityfree.com');
    define('DB_NAME', 'if0_40833417_realmstores');
    define('DB_USER', 'if0_40833417');
    define('DB_PASS', '07553242z');
}

// ===============================
// Site Configuration
// ===============================
define('SITE_NAME', 'Realm');
define('SITE_TAGLINE', "Uganda's Leading E-commerce Platform");
define('ADMIN_EMAIL', 'realmstores2@gmail.com');
define('ADMIN_PHONE_1', '(+256) 757 023 168');
define('ADMIN_PHONE_2', '(+256) 771 331 531');
define('WHATSAPP_NUMBER', 'aturindah'); // used in: https://wa.me/WHATSAPP_NUMBER
define('PRODUCTION_URL', 'https://realmstores.com/');

// ===============================
// Base URL Configuration (DYNAMIC)
// Works on localhost:8088 and Cloudflare tunnel
// ===============================

$scheme = 'http';

if (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (!empty($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false)
) {
    $scheme = 'https';
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// realm-shop is the DocumentRoot, so baseDir is the web root
$docRoot  = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$baseDir  = str_replace('\\', '/', realpath(__DIR__));

$basePath = '';
if ($docRoot && $baseDir && strpos($baseDir, $docRoot) === 0) {
    $basePath = substr($baseDir, strlen($docRoot)); // usually ""
}
$basePath = rtrim($basePath, '/');

if ($basePath === '' || $basePath === '/') {
    // Site at root: http://localhost:8088 or https://realm.klcdc.org
    define('BASE_URL', $scheme . '://' . $host);
} else {
    define('BASE_URL', $scheme . '://' . $host . $basePath);
}

// Admin is /admin under same host
define('ADMIN_URL', BASE_URL . '/admin');

// PUBLIC_URL: canonical site URL — auto-switches between local and production.
// Use this for sitemap, sharing links, etc.
define('PUBLIC_URL', IS_LOCAL ? rtrim(BASE_URL, '/') . '/' : PRODUCTION_URL);

// ===============================
// Email Image Handling
// ===============================
// Emails are read in Gmail/etc which cannot reach localhost.
// So image URLs in emails must ALWAYS point to the live production domain,
// even when the site is running locally.
define('EMAIL_IMAGE_BASE_URL', rtrim(PRODUCTION_URL, '/'));

// ===============================
// Upload Configuration
// ===============================

// Physical directory for product images
define('UPLOAD_DIR', __DIR__ . '/uploads/products/');

// Public URL to product images
// (ends with / so UPLOAD_URL . $filename works)
define('UPLOAD_URL', BASE_URL . '/uploads/products/');

// Max file size for uploads (bytes) – 5 MB
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Allowed MIME types for product images
define('ALLOWED_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
]);

// ===============================
// Database Connection (PDO)
// ===============================
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// ===============================
// Session Start
// ===============================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include activity logger (with error handling)
if (file_exists(__DIR__ . '/includes/activity_logger.php')) {
    try {
        require_once __DIR__ . '/includes/activity_logger.php';
        
        // Auto-track page views (except for AJAX calls and admin pages)
        if (!defined('NO_TRACKING') && 
            isset($_SERVER['REQUEST_URI']) &&
            strpos($_SERVER['REQUEST_URI'], '/api/') === false && 
            strpos($_SERVER['REQUEST_URI'], '/admin/') === false &&
            function_exists('trackPageView')) {
            trackPageView($pdo);
        }
    } catch (Exception $e) {
        // Silently fail - don't break the site if tracking fails
        error_log('Activity logger error: ' . $e->getMessage());
    }
}
