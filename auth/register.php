<?php
// register.php: User registration form and logic
session_start();
include '../includes/db_connect.php';
$pageTitle = 'Register';

// Fetch roles for dropdown
$roles = [];
$roleResult = $connection->query('SELECT id, role FROM roles');
while ($row = $roleResult->fetch_assoc()) {
    $roles[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);
    $error = '';
    $success = '';

    if ($name && $email && $password && $confirm_password && $role_id) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $connection->prepare('INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('sssi', $name, $email, $hash, $role_id);
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Email may already exist.';
            }
        }
    } else {
        $error = 'Please fill all fields.';
    }
}

include '../includes/header.php';
?>

<div class="HomeCards1">
    <div class="card" id="signup-card">
        <h2>Register</h2>
        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post" id="signup-form">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" required>
            
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
            
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <label for="role_id">Role</label>
            <select name="role_id" id="role_id" required>
                <option value="">Select Role</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <div id="register-button-container">
                <button type="submit" class="button-exploreevents register-btn">
                    Register
                </button>
            </div>
        </form>
        <div id="register-bottom-link">
            <a href="login.php">Already have an account? Login here</a>
        </div>
        
        <style>
            #register-button-container {
                display: flex;
                justify-content: center;
                margin-top: 25px;
                width: 100%;
            }
            
            .register-btn {
                width: 140px !important;
                height: 45px !important;
                font-size: 16px !important;
                font-weight: bold !important;
                letter-spacing: 0.5px;
                border: 2px solid #FFD700 !important;
                transition: all 0.3s ease !important;
            }
            
            .register-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 0 20px 5px #FFD700 !important;
            }
            
            .register-btn:active {
                transform: translateY(0px);
            }
            
            #signup-form input, 
            #signup-form select {
                width: 90%;
                padding: 10px;
                margin: 5px 0 15px 0;
                border-radius: 8px;
                border: 1px solid #FFD700;
                background: #181818;
                color: #FFD700;
                font-size: 1em;
            }
            
            #signup-form label {
                align-self: flex-start;
                margin-left: 5%;
                color: #FFD700;
                font-weight: 600;
            }
            
            #register-bottom-link {
                text-align: center;
                margin-top: 18px;
            }
            
            #register-bottom-link a {
                color: #FFD700;
                text-decoration: underline;
                font-size: 1em;
            }
            
            #register-bottom-link a:hover {
                color: #fff176;
            }
            
            #signup-card {
                min-width: 320px;
                max-width: 500px;
                width: 100%;
                padding: 20px;
            }
        </style>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
