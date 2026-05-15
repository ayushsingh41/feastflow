<?php
// ============================================================
// config/config.php — FeastFlow App Configuration
// ============================================================

define('APP_NAME',    'FeastFlow');
define('APP_TAGLINE', 'Order Food. Enjoy Life.');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/feastflow');

// ── Session ──────────────────────────────────────────────────
define('SESSION_NAME',     'FF_SESSION');
define('SESSION_LIFETIME', 7200);

// ── Upload Settings ──────────────────────────────────────────
define('UPLOAD_DIR',   __DIR__ . '/../assets/uploads/products/');
define('UPLOAD_URL',   APP_URL . '/assets/uploads/products/');
define('MAX_FILE_SIZE', 3 * 1024 * 1024); // 3MB
define('ALLOWED_TYPES', ['image/jpeg','image/png','image/webp']);

// ── Security ─────────────────────────────────────────────────
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_DURATION',      15 * 60);
define('BCRYPT_COST',        10);
define('CSRF_TOKEN_NAME',    '_ff_csrf');

// ── Business Settings ────────────────────────────────────────
define('DELIVERY_FEE',       40.00);
define('FREE_DELIVERY_ABOVE', 500.00);
define('GST_PERCENT',        5);
define('ROWS_PER_PAGE',      10);

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ── Error Reporting ──────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/database.php';
