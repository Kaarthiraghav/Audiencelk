<?php
// admin_setup.php - One-time admin account setup/reset
// IMPORTANT: Delete this file after use for security!

include 'includes/db_connect.php';

// Configuration
$admin_username = 'admin';
$admin_email = 'admin@audiencelk.com';
$admin_password = 'Admin123!'; // Change this to a secure password
$admin_role_id = 1; // Admin role

echo "<h2>Admin Account Setup</h2>";

try {
    // Check if admin already exists
    $check_stmt = $connection->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
    $check_stmt->bind_param('ss', $admin_email, $admin_username);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing admin
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $update_stmt = $connection->prepare('UPDATE users SET password = ?, role_id = ? WHERE email = ? OR username = ?');
        $update_stmt->bind_param('siss', $hashed_password, $admin_role_id, $admin_email, $admin_username);
        
        if ($update_stmt->execute()) {
            echo "<div style='color: green; padding: 20px; background: #f0f8ff; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>✅ Admin Account Updated Successfully!</h3>";
            echo "<p><strong>Username:</strong> {$admin_username}</p>";
            echo "<p><strong>Email:</strong> {$admin_email}</p>";
            echo "<p><strong>Password:</strong> {$admin_password}</p>";
            echo "<p><strong>Admin Key:</strong> ALK-ADMIN-2025</p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>Error updating admin account: " . $connection->error . "</p>";
        }
        $update_stmt->close();
    } else {
        // Create new admin
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $created_at = date('Y-m-d H:i:s');
        
        $insert_stmt = $connection->prepare('INSERT INTO users (username, email, password, role_id, created_at) VALUES (?, ?, ?, ?, ?)');
        $insert_stmt->bind_param('sssis', $admin_username, $admin_email, $hashed_password, $admin_role_id, $created_at);
        
        if ($insert_stmt->execute()) {
            echo "<div style='color: green; padding: 20px; background: #f0f8ff; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>✅ Admin Account Created Successfully!</h3>";
            echo "<p><strong>Username:</strong> {$admin_username}</p>";
            echo "<p><strong>Email:</strong> {$admin_email}</p>";
            echo "<p><strong>Password:</strong> {$admin_password}</p>";
            echo "<p><strong>Admin Key:</strong> ALK-ADMIN-2025</p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>Error creating admin account: " . $connection->error . "</p>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<div style='background: #ffe6e6; border: 1px solid #ff9999; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
echo "<h4>🚨 Security Warning</h4>";
echo "<p><strong>IMPORTANT:</strong> Delete this file immediately after use!</p>";
echo "<p>This file contains sensitive information and should not remain on the server.</p>";
echo "</div>";

echo "<div style='background: #f0f8f0; border: 1px solid #99cc99; border-radius: 5px; padding: 15px; margin: 20px 0;'>";
echo "<h4>📋 Login Instructions</h4>";
echo "<ol>";
echo "<li>Go to: <a href='auth/admin-login.php'>auth/admin-login.php</a></li>";
echo "<li>Enter the admin email and password shown above</li>";
echo "<li>Enter the admin key: <strong>ALK-ADMIN-2025</strong></li>";
echo "<li>Click 'Secure Login'</li>";
echo "</ol>";
echo "</div>";

$connection->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}
h2 {
    color: #333;
    text-align: center;
}
</style>