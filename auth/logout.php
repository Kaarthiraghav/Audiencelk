<?php
// logout.php: Enhanced logout with remember token cleanup
session_start();
include '../includes/db_connect.php';

// Clean up remember tokens if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Remove all remember tokens for this user
        $stmt = $connection->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
    } catch (Exception $e) {
        // Log error but continue with logout
        error_log("Error cleaning remember tokens: " . $e->getMessage());
    }
}

// Clear remember token cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Log admin logout for security
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] === 1) {
    error_log("Admin logout: " . ($_SESSION['username'] ?? 'Unknown') . " from IP: " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
}

// Clear all session data
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to appropriate login page
$redirect = isset($_GET['admin']) ? 'admin-login.php' : 'login.php?logged_out=1';
header('Location: ' . $redirect);
exit;
?>
