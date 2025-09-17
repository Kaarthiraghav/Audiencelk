<?php
/**
 * Configuration file for InfinityFree hosting
 * Update these values with your actual hosting details
 */

// Environment detection
$is_local = (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);

// Base URL Configuration
if ($is_local) {
    define('BASE_URL', 'http://localhost/Audiencelk/');
    define('SITE_ROOT', '/Audiencelk');
} else {
    // Update with your actual domain
    define('BASE_URL', 'https://audiencelk.infinityfree.me/');
    define('SITE_ROOT', '');
}

// Database Configuration
if ($is_local) {
    // Local development settings
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'audiencelk');
} else {
    // InfinityFree production settings
    define('DB_HOST', 'sql210.infinityfree.com');
    define('DB_USER', 'if0_39624525');
    define('DB_PASS', 'Raveen18');  // Replace with your actual password
    define('DB_NAME', 'if0_39624525_audiencelk');
}

// Application Settings
define('APP_NAME', 'AudienceLK');
define('APP_VERSION', '1.0.0');

// Error Reporting (disable in production)
if ($is_local) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Session Configuration
// Only set session parameters if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', !$is_local); // HTTPS only in production
}

// InfinityFree specific settings
if (!$is_local) {
    // Disable some functions that might not work on InfinityFree
    ini_set('max_execution_time', 30);
    ini_set('memory_limit', '64M');
}
?>