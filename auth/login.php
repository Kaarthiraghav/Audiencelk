<?php
// login.php: Enhanced user login with Remember Me functionality
session_start();
include '../includes/db_connect.php';
$pageTitle = 'Sign In to AudienceLK';

// Initialize variables
$error = '';
$success = '';
$email = '';

// Check for logout confirmation
if (isset($_GET['logged_out']) && $_GET['logged_out'] == '1') {
    $success = 'You have been successfully logged out.';
}

// Check for "Remember Me" cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $connection->prepare('SELECT user_id FROM remember_tokens WHERE token = ? AND expires_at > NOW()');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Get user details
        $userStmt = $connection->prepare('SELECT * FROM users WHERE id = ?');
        $userStmt->bind_param('i', $row['user_id']);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($user = $userResult->fetch_assoc()) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            
            // Get role name
            $roleStmt = $connection->prepare('SELECT role FROM roles WHERE id = ?');
            $roleStmt->bind_param('i', $user['role_id']);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();
            $roleRow = $roleResult->fetch_assoc();
            $_SESSION['role'] = $roleRow ? $roleRow['role'] : '';
            
            // Redirect based on role
            if ($user['role_id'] == 1) {
                header('Location: ../dashboards/admin_dashboard.php');
            } elseif ($user['role_id'] == 2) {
                header('Location: ../dashboards/organizer_dashboard.php');
            } else {
                header('Location: ../dashboards/user_dashboard.php');
            }
            exit;
        }
    }
}

// Initialize login attempts tracking
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Check if user is locked out due to too many failed attempts
$locked_out = false;
if ($_SESSION['login_attempts'] >= 5) {
    $lockout_time = 15 * 60; // 15 minutes lockout
    $time_elapsed = time() - $_SESSION['last_attempt_time'];
    
    if ($time_elapsed < $lockout_time) {
        $locked_out = true;
        $wait_time = $lockout_time - $time_elapsed;
        $error = 'Too many failed login attempts. Please wait ' . ceil($wait_time / 60) . ' minutes before trying again.';
    } else {
        // Reset attempts after lockout period
        $_SESSION['login_attempts'] = 0;
    }
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked_out) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Update the last attempt time
    $_SESSION['last_attempt_time'] = time();

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($password)) {
        $error = 'Please enter your password.';
    } elseif (strlen($password) < 8) {
        $_SESSION['login_attempts']++;
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            $stmt = $connection->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    // Reset login attempts on successful login
                    $_SESSION['login_attempts'] = 0;
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role_id'] = $row['role_id'];
                    
                    // Get role name
                    $roleStmt = $connection->prepare('SELECT role FROM roles WHERE id = ?');
                    $roleStmt->bind_param('i', $row['role_id']);
                    $roleStmt->execute();
                    $roleResult = $roleStmt->get_result();
                    $roleRow = $roleResult->fetch_assoc();
                    $_SESSION['role'] = $roleRow ? $roleRow['role'] : '';
                    
                    // Handle "Remember Me" functionality
                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                        
                        // Create remember_tokens table if it doesn't exist
                        $connection->query("CREATE TABLE IF NOT EXISTS remember_tokens (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            token VARCHAR(64) NOT NULL UNIQUE,
                            expires_at DATETIME NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                        )");
                        
                        // Clean old tokens for this user
                        $cleanStmt = $connection->prepare('DELETE FROM remember_tokens WHERE user_id = ? OR expires_at <= NOW()');
                        $cleanStmt->bind_param('i', $row['id']);
                        $cleanStmt->execute();
                        
                        // Insert new token
                        $tokenStmt = $connection->prepare('INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
                        $tokenStmt->bind_param('iss', $row['id'], $token, $expires);
                        $tokenStmt->execute();
                        
                        // Set cookie
                        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
                    }
                    
                    // Redirect based on role_id (exclude admin from normal login)
                    if ($row['role_id'] == 1) {
                        header('Location: ../dashboards/admin_dashboard.php');
                    } elseif ($row['role_id'] == 2) {
                        header('Location: ../dashboards/organizer_dashboard.php');
                    } else {
                        header('Location: ../dashboards/user_dashboard.php');
                    }
                    exit;
                } else {
                    $error = 'Invalid password.';
                    $_SESSION['login_attempts']++;
                }
            } else {
                $error = 'User not found.';
                $_SESSION['login_attempts']++;
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}

include '../includes/header.php';
?>

<div class="HomeCards1">
    <div class="modern-auth-container login-container">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your AudienceLK account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post" class="modern-auth-form" onsubmit="return validateLoginForm()">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
                <div class="password-hint">Password must be at least 8 characters</div>
            </div>
            
            <div class="form-options">
                <div class="remember-me">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <label for="remember_me" class="checkbox-label">
                        <span class="checkmark"></span>
                        Remember me for 30 days
                    </label>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot your password?</a>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary" <?= $locked_out ? 'disabled' : '' ?>>
                    <span><?= $locked_out ? 'Account Locked' : 'Sign In' ?></span>
                    <?php if (!$locked_out): ?>
                        <div class="btn-icon">→</div>
                    <?php endif; ?>
                </button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Create one here</a></p>
        </div>
        
        <?php if ($_SESSION['login_attempts'] > 0 && $_SESSION['login_attempts'] < 5): ?>
            <div class="security-notice">
                <div class="security-icon">🔒</div>
                <div class="security-text">
                    <strong>Security Notice:</strong> <?= $_SESSION['login_attempts'] ?> failed attempt(s). 
                    Account will be locked after 5 failed attempts.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function validateLoginForm() {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        if (!email) {
            showError('Please enter your email address.');
            return false;
        }
        
        if (!email.includes('@') || !email.includes('.')) {
            showError('Please enter a valid email address.');
            return false;
        }
        
        if (password.length < 8) {
            showError('Password must be at least 8 characters long.');
            return false;
        }
        
        return true;
    }
    
    function showError(message) {
        // Create or update error message
        let errorDiv = document.querySelector('.message.error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'message error';
            document.querySelector('.auth-header').after(errorDiv);
        }
        errorDiv.textContent = message;
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    // Add loading state to form submission
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.modern-auth-form');
        const submitBtn = document.querySelector('.btn-primary');
        
        form.addEventListener('submit', function() {
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Signing In...</span><div class="spinner"></div>';
                
                // Re-enable button after 5 seconds as failsafe
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Sign In</span><div class="btn-icon">→</div>';
                }, 5000);
            }
        });
        
        // Auto-focus first empty field
        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('password');
        
        if (!emailField.value) {
            emailField.focus();
        } else if (!passwordField.value) {
            passwordField.focus();
        }
    });
</script>

<style>
/* Login-specific styles extending the modern auth styles */
.login-container {
    max-width: 500px;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
    flex-wrap: wrap;
    gap: 15px;
}

.remember-me {
    display: flex;
    align-items: center;
}

.remember-me input[type="checkbox"] {
    display: none;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    color: #ccc;
    font-size: 0.9em;
    user-select: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #444;
    border-radius: 4px;
    margin-right: 10px;
    position: relative;
    transition: all 0.3s ease;
    background: #232323;
}

.remember-me input:checked + .checkbox-label .checkmark {
    background: #FFD700;
    border-color: #FFD700;
}

.remember-me input:checked + .checkbox-label .checkmark::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #000;
    font-weight: bold;
    font-size: 12px;
}

.forgot-password a {
    color: #FFD700;
    text-decoration: none;
    font-size: 0.9em;
    transition: all 0.3s ease;
}

.forgot-password a:hover {
    color: #fff;
    text-shadow: 0 0 10px #FFD700;
}

.password-hint {
    font-size: 0.8em;
    color: #aaa;
    margin-top: 5px;
}

.security-notice {
    background: rgba(255, 193, 7, 0.1);
    border: 1px solid #ffc107;
    border-radius: 12px;
    padding: 15px;
    margin-top: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.security-icon {
    font-size: 1.5em;
}

.security-text {
    color: #ffc107;
    font-size: 0.9em;
    line-height: 1.4;
}

.spinner {
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid #000;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Additional responsive adjustments for login */
@media (max-width: 480px) {
    .form-options {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .security-notice {
        padding: 12px;
        font-size: 0.85em;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
