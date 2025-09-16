<?php
// register.php: Enhanced user registration with role selection for User/Organizer only
session_start();
include '../includes/db_connect.php';
$pageTitle = 'Join AudienceLK';

// Only allow User and Organizer roles for public signup (no Admin)
$allowedRoles = [
    2 => 'Event Organizer',
    3 => 'Attendee'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);
    $error = '';
    $success = '';

    // Validate role selection (only allow User/Organizer)
    if (!array_key_exists($role_id, $allowedRoles)) {
        $error = 'Invalid role selection.';
    } elseif ($name && $email && $password && $confirm_password && $role_id) {
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';
        } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $error = 'Password must contain at least one special character.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check for unique email and username
            $checkStmt = $connection->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
            $checkStmt->bind_param('ss', $email, $name);
            $checkStmt->execute();
            $checkStmt->store_result();
            if ($checkStmt->num_rows > 0) {
                $error = 'Email or username already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $connection->prepare('INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('sssi', $name, $email, $hash, $role_id);
                if ($stmt->execute()) {
                    $success = 'Registration successful! You can now login to your account.';
                    // Clear form data on success
                    $name = $email = '';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
            $checkStmt->close();
        }
    } else {
        $error = 'Please fill all required fields.';
    }
}

include '../includes/header.php';
?>

<div class="HomeCards1">
    <div class="modern-auth-container">
        <div class="auth-header">
            <h1>Join AudienceLK</h1>
            <p>Create your account and start discovering amazing events</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="post" class="modern-auth-form" onsubmit="return validateRegistrationForm()">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>
                </div>
            </div>
            
            <div class="password-requirements">
                <div class="req-title">Password Requirements:</div>
                <div class="req-list">
                    <span class="req-item">• At least 8 characters</span>
                    <span class="req-item">• One uppercase letter</span>
                    <span class="req-item">• One lowercase letter</span>
                    <span class="req-item">• One number</span>
                    <span class="req-item">• One special character</span>
                </div>
            </div>

            <div class="role-selection">
                <label class="role-title">Choose Your Account Type:</label>
                <div class="role-cards">
                    <div class="role-card" onclick="selectRole(3)">
                        <input type="radio" name="role_id" value="3" id="role_attendee" <?= (isset($_POST['role_id']) && $_POST['role_id'] == 3) ? 'checked' : '' ?>>
                        <div class="role-icon">👤</div>
                        <h3>Attendee</h3>
                        <p>Discover and book tickets for amazing events in your area</p>
                        <ul>
                            <li>Browse events</li>
                            <li>Book tickets</li>
                            <li>Manage bookings</li>
                            <li>Get event updates</li>
                        </ul>
                    </div>
                    
                    <div class="role-card" onclick="selectRole(2)">
                        <input type="radio" name="role_id" value="2" id="role_organizer" <?= (isset($_POST['role_id']) && $_POST['role_id'] == 2) ? 'checked' : '' ?>>
                        <div class="role-icon">🎭</div>
                        <h3>Event Organizer</h3>
                        <p>Create and manage your own events to reach more audiences</p>
                        <ul>
                            <li>Create events</li>
                            <li>Manage bookings</li>
                            <li>Track analytics</li>
                            <li>Promote events</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <span>Create Account</span>
                    <div class="btn-icon">→</div>
                </button>
            </div>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>
</div>

<script>
    function selectRole(roleId) {
        // Remove active class from all role cards
        document.querySelectorAll('.role-card').forEach(card => {
            card.classList.remove('active');
        });
        
        // Add active class to selected role card
        const selectedCard = document.querySelector(`input[value="${roleId}"]`).closest('.role-card');
        selectedCard.classList.add('active');
        
        // Check the radio button
        document.querySelector(`input[value="${roleId}"]`).checked = true;
    }
    
    function validateRegistrationForm() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const roleSelected = document.querySelector('input[name="role_id"]:checked');
        
        if (!roleSelected) {
            showError('Please select an account type.');
            return false;
        }
        
        if (password.length < 8) {
            showError('Password must be at least 8 characters long.');
            return false;
        }
        
        if (!/[A-Z]/.test(password)) {
            showError('Password must contain at least one uppercase letter.');
            return false;
        }
        
        if (!/[a-z]/.test(password)) {
            showError('Password must contain at least one lowercase letter.');
            return false;
        }
        
        if (!/[0-9]/.test(password)) {
            showError('Password must contain at least one number.');
            return false;
        }
        
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            showError('Password must contain at least one special character.');
            return false;
        }
        
        if (password !== confirmPassword) {
            showError('Passwords do not match.');
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
    
    // Initialize role selection if form was submitted with errors
    document.addEventListener('DOMContentLoaded', function() {
        const checkedRole = document.querySelector('input[name="role_id"]:checked');
        if (checkedRole) {
            checkedRole.closest('.role-card').classList.add('active');
        }
        
        // Add password strength indicator
        const passwordInput = document.getElementById('password');
        if (passwordInput) {
            passwordInput.addEventListener('input', updatePasswordStrength);
        }
    });
    
    function updatePasswordStrength() {
        const password = document.getElementById('password').value;
        const requirements = document.querySelectorAll('.req-item');
        
        // Check each requirement
        const checks = [
            password.length >= 8,
            /[A-Z]/.test(password),
            /[a-z]/.test(password),
            /[0-9]/.test(password),
            /[!@#$%^&*(),.?":{}|<>]/.test(password)
        ];
        
        requirements.forEach((req, index) => {
            if (checks[index]) {
                req.classList.add('met');
            } else {
                req.classList.remove('met');
            }
        });
    }
</script>

<style>
/* Modern Authentication Styles */
.modern-auth-container {
    max-width: 800px;
    width: 100%;
    background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 215, 0, 0.2);
    margin: 20px auto;
    position: relative;
    overflow: hidden;
}

.modern-auth-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #FFD700, transparent);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.auth-header {
    text-align: center;
    margin-bottom: 40px;
}

.auth-header h1 {
    font-size: 2.5em;
    color: #FFD700;
    margin-bottom: 10px;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.auth-header p {
    color: #ccc;
    font-size: 1.1em;
    margin: 0;
}

.modern-auth-form {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    color: #FFD700;
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
    border-color: #FFD700;
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
    transform: translateY(-2px);
}

.password-requirements {
    background: #2a2a2a;
    border-radius: 12px;
    padding: 20px;
    border-left: 4px solid #FFD700;
}

.req-title {
    color: #FFD700;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 1em;
}

.req-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 8px;
}

.req-item {
    color: #aaa;
    font-size: 0.9em;
    transition: all 0.3s ease;
    padding: 5px 0;
}

.req-item.met {
    color: #28a745;
    font-weight: 600;
}

.req-item.met::before {
    content: '✓ ';
    margin-right: 5px;
}

.role-selection {
    margin: 30px 0;
}

.role-title {
    display: block;
    color: #FFD700;
    font-weight: 600;
    margin-bottom: 20px;
    font-size: 1.1em;
    text-align: center;
}

.role-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.role-card {
    background: #2a2a2a;
    border: 2px solid #444;
    border-radius: 15px;
    padding: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    text-align: center;
}

.role-card:hover {
    border-color: #FFD700;
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
}

.role-card.active {
    border-color: #FFD700;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
    box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
}

.role-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.role-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

.role-card h3 {
    color: #FFD700;
    margin-bottom: 10px;
    font-size: 1.3em;
    font-weight: 600;
}

.role-card p {
    color: #ccc;
    margin-bottom: 15px;
    line-height: 1.5;
}

.role-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.role-card li {
    color: #aaa;
    font-size: 0.9em;
    margin-bottom: 5px;
    position: relative;
    padding-left: 15px;
}

.role-card li::before {
    content: '→';
    position: absolute;
    left: 0;
    color: #FFD700;
}

.form-actions {
    margin-top: 30px;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #000;
    border: none;
    padding: 15px 40px;
    border-radius: 50px;
    font-size: 1.1em;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 215, 0, 0.4);
    filter: brightness(1.1);
}

.btn-icon {
    font-size: 1.2em;
    transition: transform 0.3s ease;
}

.btn-primary:hover .btn-icon {
    transform: translateX(5px);
}

.auth-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #444;
}

.auth-footer p {
    color: #ccc;
    margin: 0;
}

.auth-footer a {
    color: #FFD700;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.auth-footer a:hover {
    color: #fff;
    text-shadow: 0 0 10px #FFD700;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modern-auth-container {
        padding: 30px 20px;
        margin: 10px;
    }
    
    .auth-header h1 {
        font-size: 2em;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .role-cards {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .req-list {
        grid-template-columns: 1fr;
    }
    
    .role-card {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .modern-auth-container {
        padding: 20px 15px;
    }
    
    .auth-header h1 {
        font-size: 1.8em;
    }
    
    .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
