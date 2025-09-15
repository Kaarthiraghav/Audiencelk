<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="<?php echo BASE_URL?>assets/main.css">
    <style>
        body, h1, h2, h3, h4, h5, h6, p, label, th, td, ul, li, a, div, span {
            color: #FFD700 !important;
        }
        .button-exploreevents, .button-backtohome, input[type="submit"], button {
            color: #232323 !important;
        }
    </style>
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Event Watch'; ?></title>
</head>
<body>
<div style="display: flex; align-items: center; justify-content: space-between; width: 90%; max-width: 1100px; margin: 30px auto 0 auto;">
    <div class="navbar" style="margin: 0; flex: 1;">
        <nav>
            <ul style="margin:0;">
                <li><a href="<?php echo BASE_URL?>index.php">Home</a></li>
                <li><a href="<?php echo BASE_URL?>events/view_events.php">Events</a></li>
                <?php if (isset($_SESSION['role_id'])): ?>
<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
                    <?php if ($_SESSION['role_id'] === 1): ?>
                        <li><a href="<?php echo BASE_URL?>dashboards/admin_dashboard.php">Admin Dashboard</a></li>
                    <?php elseif ($_SESSION['role_id'] === 2): ?>
                        <li><a href="<?php echo BASE_URL?>dashboards/organizer_dashboard.php">Organizer Dashboard</a></li>
=======
=======
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
                    <?php if ($_SESSION['role_id'] === 2): ?>
                        <li><a href="<?php echo BASE_URL?>dashboards/organizer_dashboard.php" style="font-weight: 600;">Organizer Dashboard</a></li>
>>>>>>> Stashed changes
                    <?php elseif ($_SESSION['role_id'] === 3): ?>
                        <li><a href="<?php echo BASE_URL?>dashboards/user_dashboard.php">User Dashboard</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL?>about.php">About</a></li>
                <li><a href="<?php echo BASE_URL?>contactus.php">Contact Us</a></li>
            </ul>
        </nav>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] === 1): ?>
            <a href="<?php echo BASE_URL?>dashboards/admin_dashboard.php">
                <button class="button-exploreevents" style="margin-left: 24px; background: linear-gradient(45deg, #FF6B6B, #FF6B6B); border: none;">Admin Panel</button>
            </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL?>auth/logout.php">
            <button class="button-exploreevents" style="margin-left: 24px;">Logout</button>
        </a>
    <?php else: ?>
        <a href="<?php echo BASE_URL?>auth/login.php">
            <button class="button-exploreevents" style="margin-left: 24px;">Login</button>
        </a>
    <?php endif; ?>
</div>