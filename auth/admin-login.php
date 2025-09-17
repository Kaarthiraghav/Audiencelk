<?php
// admin-login.php: Dedicated admin login with enhanced security
session_start();
include '../includes/db_connect.php';
$pageTitle = 'Admin Portal - AudienceLK';

// Check if already logged in as admin
if (isset($_SESSION['user_id']) && $_SESSION['role_id'] === 1) {
    header('Location: ../dashboards/admin_dashboard.php');
    exit;
}

// Initialize variables
$error = '';
$success = '';
$email = '';

// Enhanced security for admin login
if (!isset($_SESSION['admin_login_attempts'])) {
    $_SESSION['admin_login_attempts'] = 0;
    $_SESSION['admin_last_attempt'] = time();
}

// More restrictive lockout for admin (3 attempts, 30 minutes)
$locked_out = false;
if ($_SESSION['admin_login_attempts'] >= 3) {
    $lockout_time = 30 * 60; // 30 minutes lockout
    $time_elapsed = time() - $_SESSION['admin_last_attempt'];
    
    if ($time_elapsed < $lockout_time) {
        $locked_out = true;
        $wait_time = $lockout_time - $time_elapsed;
        $error = 'Too many failed admin login attempts. Please wait ' . ceil($wait_time / 60) . ' minutes before trying again.';
        
        // Log security incident
        error_log("Admin login lockout from IP: " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
    } else {
        // Reset attempts after lockout period
        $_SESSION['admin_login_attempts'] = 0;
    }
}

// Process admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked_out) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $admin_key = $_POST['admin_key'] ?? '';
    
    $_SESSION['admin_last_attempt'] = time();
    
    // Validate admin key (additional security layer)
    $valid_admin_key = 'admin'; // You should change this to a secure key
    
    if (empty($email) || empty($password) || empty($admin_key)) {
        $error = 'All fields are required for admin access.';
        $_SESSION['admin_login_attempts']++;
    } elseif ($admin_key !== $valid_admin_key) {
        $error = 'Invalid admin access key.';
        $_SESSION['admin_login_attempts']++;
        error_log("Invalid admin key attempt from IP: " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
        $_SESSION['admin_login_attempts']++;
    } else {
        try {
            // Only allow admin role (role_id = 1)
            $stmt = $connection->prepare('SELECT * FROM users WHERE email = ? AND role_id = 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    // Reset attempts on successful login
                    $_SESSION['admin_login_attempts'] = 0;
                    
                    // Set admin session with enhanced security
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role_id'] = $row['role_id'];
                    $_SESSION['role'] = 'Admin';
                    $_SESSION['admin_login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Log successful admin login
                    error_log("Successful admin login: " . $row['email'] . " from IP: " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    header('Location: ../dashboards/admin_dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid credentials.';
                    $_SESSION['admin_login_attempts']++;
                    error_log("Failed admin login attempt: " . $email . " from IP: " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
                }
            } else {
                $error = 'Admin account not found.';
                $_SESSION['admin_login_attempts']++;
                error_log("Admin account not found: " . $email . " from IP: " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Admin login error: " . $e->getMessage());
        }
    }
}

// Don't include the regular header for admin login
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="../assets/main.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a, #1a1a1a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .admin-login-container {
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.6);
            border: 2px solid #DC143C;
            max-width: 450px;
            width: 90%;
            position: relative;
            overflow: hidden;
        }
        
        .admin-login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #DC143C, #FF6B6B, #DC143C);
            animation: adminShimmer 2s infinite;
        }
        
        @keyframes adminShimmer {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .admin-shield {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #DC143C, #FF6B6B);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .admin-header h1 {
            color: #DC143C;
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .admin-header p {
            color: #aaa;
            margin: 0;
            font-size: 1em;
        }
        
        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            color: #DC143C;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            padding: 15px;
            border: 2px solid #444;
            border-radius: 12px;
            background: #232323;
            color: #fff;
            font-size: 1em;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .form-group input:focus {
            border-color: #DC143C;
            box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.1);
            transform: translateY(-2px);
        }
        
        .admin-key-group {
            position: relative;
        }
        
        .admin-key-info {
            font-size: 0.8em;
            color: #888;
            margin-top: 5px;
            font-style: italic;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, #DC143C, #FF6B6B);
            color: #fff;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 25px rgba(220, 20, 60, 0.3);
            margin-top: 20px;
        }
        
        .btn-admin:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(220, 20, 60, 0.4);
            filter: brightness(1.1);
        }
        
        .btn-admin:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .security-warning {
            background: rgba(220, 20, 60, 0.1);
            border: 1px solid #DC143C;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            color: #DC143C;
            font-size: 0.9em;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #444;
        }
        
        .back-link a {
            color: #aaa;
            text-decoration: none;
            font-size: 0.9em;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #fff;
        }
        
        .message.error {
            background: rgba(220, 20, 60, 0.15);
            color: #DC143C;
            border: 1px solid #DC143C;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        .attempts-warning {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid #FF6B6B;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 20px;
            color: #FF6B6B;
            font-size: 0.9em;
            text-align: center;
        }
        
        @media (max-width: 480px) {
            .admin-login-container {
                padding: 30px 20px;
                margin: 20px;
            }
            
            .admin-header h1 {
                font-size: 1.8em;
            }
            
            .admin-shield {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-header">
            <div class="admin-shield">🛡️</div>
            <h1>Admin Portal</h1>
            <p>Secure administrative access to AudienceLK</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($_SESSION['admin_login_attempts'] > 0 && $_SESSION['admin_login_attempts'] < 3): ?>
            <div class="attempts-warning">
                <strong>Warning:</strong> <?= $_SESSION['admin_login_attempts'] ?> failed attempt(s). 
                Account will be locked after 3 failed attempts for 30 minutes.
            </div>
        <?php endif; ?>
        
        <form method="post" class="admin-form" onsubmit="return validateAdminForm()">
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required <?= $locked_out ? 'disabled' : '' ?>>
            </div>
            
            <div class="form-group">
                <label for="password">Admin Password</label>
                <input type="password" name="password" id="password" required <?= $locked_out ? 'disabled' : '' ?>>
            </div>
            
            <div class="form-group admin-key-group">
                <label for="admin_key">Admin Access Key</label>
                <input type="password" name="admin_key" id="admin_key" placeholder="Enter admin access key" required <?= $locked_out ? 'disabled' : '' ?>>
                <div class="admin-key-info">This key is required for additional security</div>
            </div>
            
            <button type="submit" class="btn-admin" <?= $locked_out ? 'disabled' : '' ?>>
                <span><?= $locked_out ? 'Access Locked' : 'Secure Login' ?></span>
                <?php if (!$locked_out): ?>
                    <span>🔐</span>
                <?php endif; ?>
            </button>
        </form>
        
        <?php if ($locked_out): ?>
            <div class="security-warning">
                <strong>Security Lock Activated</strong><br>
                Multiple failed login attempts detected. This incident has been logged.
            </div>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="../index.php">← Back to AudienceLK</a>
        </div>
    </div>
    
    <script>
        function validateAdminForm() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const adminKey = document.getElementById('admin_key').value;
            
            if (!email || !password || !adminKey) {
                alert('All fields are required for admin access.');
                return false;
            }
            
            if (!email.includes('@') || !email.includes('.')) {
                alert('Please enter a valid email address.');
                return false;
            }
            
            if (password.length < 8) {
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            // Add loading state
            const submitBtn = document.querySelector('.btn-admin');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Authenticating...</span><div class="spinner"></div>';
            
            return true;
        }
        
        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (emailField && !emailField.disabled) {
                emailField.focus();
            }
        });
        
        // Security monitoring (basic)
        let inactiveTime = 0;
        const maxInactiveTime = 300000; // 5 minutes
        
        function resetTimer() {
            inactiveTime = 0;
        }
        
        function checkInactive() {
            inactiveTime += 1000;
            if (inactiveTime >= maxInactiveTime) {
                alert('Session timeout due to inactivity.');
                window.location.href = '../index.php';
            }
        }
        
        // Set up activity monitoring
        document.addEventListener('mousemove', resetTimer);
        document.addEventListener('keypress', resetTimer);
        document.addEventListener('click', resetTimer);
        setInterval(checkInactive, 1000);
    </script>
</body>
</html>