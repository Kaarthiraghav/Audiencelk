<?php
/**
 * InfinityFree Compatibility Checker
 * Run this file to check if your hosting environment is compatible
 */

echo "<h1>InfinityFree Compatibility Check</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// Check PHP Version
echo "<h2>PHP Information</h2>";
$php_version = phpversion();
echo "<p>PHP Version: <strong>$php_version</strong>";
if (version_compare($php_version, '7.0', '>=')) {
    echo " <span class='ok'>✓ Compatible</span>";
} else {
    echo " <span class='error'>✗ Requires PHP 7.0+</span>";
}
echo "</p>";

// Check Required Extensions
echo "<h2>Required Extensions</h2>";
$required_extensions = ['mysqli', 'session', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    echo "<p>$ext: ";
    if (extension_loaded($ext)) {
        echo "<span class='ok'>✓ Available</span>";
    } else {
        echo "<span class='error'>✗ Missing</span>";
    }
    echo "</p>";
}

// Check PHP Settings
echo "<h2>PHP Settings</h2>";
$settings = [
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'display_errors' => ini_get('display_errors'),
    'log_errors' => ini_get('log_errors')
];

foreach ($settings as $setting => $value) {
    echo "<p>$setting: <strong>$value</strong></p>";
}

// Check Database Connection (if configured)
echo "<h2>Database Connection Test</h2>";
require_once 'config.php';

if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
    try {
        $test_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($test_conn->connect_error) {
            echo "<p class='error'>✗ Database Connection Failed: " . $test_conn->connect_error . "</p>";
        } else {
            echo "<p class='ok'>✓ Database Connection Successful</p>";
            
            // Test if tables exist
            $tables = ['users', 'events', 'bookings', 'event_categories'];
            foreach ($tables as $table) {
                $result = $test_conn->query("SHOW TABLES LIKE '$table'");
                if ($result && $result->num_rows > 0) {
                    echo "<p class='ok'>✓ Table '$table' exists</p>";
                } else {
                    echo "<p class='warning'>⚠ Table '$table' not found (import database schema)</p>";
                }
            }
            $test_conn->close();
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Database Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='warning'>⚠ Database configuration not found. Update config.php with your credentials.</p>";
}

// Check File Permissions
echo "<h2>File System Check</h2>";
$writable_dirs = ['assets', 'includes'];
foreach ($writable_dirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p class='ok'>✓ $dir/ is writable</p>";
        } else {
            echo "<p class='warning'>⚠ $dir/ is not writable</p>";
        }
    } else {
        echo "<p class='error'>✗ $dir/ directory not found</p>";
    }
}

// Environment Detection
echo "<h2>Environment Information</h2>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Host: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";

// InfinityFree Specific Checks
echo "<h2>InfinityFree Specific</h2>";
if (strpos($_SERVER['HTTP_HOST'], 'infinityfreeapp.com') !== false || 
    strpos($_SERVER['HTTP_HOST'], 'epizy.com') !== false) {
    echo "<p class='ok'>✓ Running on InfinityFree hosting</p>";
} else {
    echo "<p class='warning'>⚠ Not detected as InfinityFree hosting</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If database connection failed, update your credentials in config.php</li>";
echo "<li>If tables are missing, import db/audiencelk_full_schema.sql via phpMyAdmin</li>";
echo "<li>If file permissions are wrong, contact InfinityFree support</li>";
echo "<li>After fixing issues, delete this compatibility.php file for security</li>";
echo "</ol>";

echo "<p><em>For security, delete this file after checking compatibility.</em></p>";
?>