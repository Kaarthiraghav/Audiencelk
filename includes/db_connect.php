<?php

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// InfinityFree Database Configuration
// Update these with your actual InfinityFree database credentials
$DB_HOST = 'sql210.infinityfree.com';  // InfinityFree MySQL hostname
$DB_USER = 'if0_39624525';             // Your InfinityFree username (without database name)
$DB_PASS = '';    // Your InfinityFree database password
$DB_NAME = 'if0_39624525_audiencelk';  // Your database name

// For local development, you can use environment detection
if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    // Local XAMPP settings
    $DB_HOST = 'localhost';
    $DB_USER = 'root';
    $DB_PASS = '';
    $DB_NAME = 'audiencelk';  // Local database name
}

$connection = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$connection) {
    // Better error handling for production
    if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
        die("Connection Failed: " . mysqli_connect_error());
    } else {
        die("Database connection failed. Please try again later.");
    }
}

// Set charset for better character support
mysqli_set_charset($connection, "utf8");
?>