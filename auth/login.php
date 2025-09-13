<?php
// login.php: User login form and authentication logic
session_start();
include '../includes/db_connect.php';
$pageTitle = 'Login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = '';

    if ($email && $password) {
        $stmt = $connection->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role_id'] = $row['role_id'];
                // Get role name
                $roleStmt = $connection->prepare('SELECT role FROM roles WHERE id = ?');
                $roleStmt->bind_param('i', $row['role_id']);
                $roleStmt->execute();
                $roleResult = $roleStmt->get_result();
                $roleRow = $roleResult->fetch_assoc();
                $_SESSION['role'] = $roleRow ? $roleRow['role'] : '';
                // Redirect based on role_id
                if ($row['role_id'] == 1) {
                    header('Location: ../index.php');
                } elseif ($row['role_id'] == 2) {
                    header('Location: ../dashboards/organizer_dashboard.php');
                } else {
                    header('Location: ../dashboards/user_dashboard.php');
                }
                exit;
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'User not found.';
        }
    } else {
        $error = 'Please enter email and password.';
    }
}

include '../includes/header.php';
?>

<div class="HomeCards1">
    <div class="card" id="login-card">
        <h2>Login</h2>
        <?php if (!empty($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" id="login-form">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
            
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
            
            <div id="login-button-container">
                <button type="submit" class="button-exploreevents login-btn">
                    Login
                </button>
            </div>
        </form>
        <div id="login-bottom-link">
            <a href="register.php">Don't have an account? Register here</a>
        </div>
        
        <style>
            #login-button-container {
                display: flex;
                justify-content: center;
                margin-top: 25px;
                width: 100%;
            }
            
            .login-btn {
                width: 140px !important;
                height: 45px !important;
                font-size: 16px !important;
                font-weight: bold !important;
                letter-spacing: 0.5px;
                border: 2px solid #FFD700 !important;
                transition: all 0.3s ease !important;
            }
            
            .login-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 0 20px 5px #FFD700 !important;
            }
            
            .login-btn:active {
                transform: translateY(0px);
            }
            
            #login-form input {
                margin-bottom: 15px;
            }
        </style>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
