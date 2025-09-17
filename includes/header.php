<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL?>assets/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'AudienceLK'; ?></title>
</head>
<body class="fade-in">

<div style="display: flex; align-items: center; justify-content: space-between; width: 90%; max-width: 1100px; margin: 30px auto 0 auto;">
    <div class="navbar" style="margin: 0; flex: 1; background-color: #1a1a1a; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);">
        <nav>
            <ul style="margin:0; display: flex; justify-content: center;">
                <li><a href="<?php echo BASE_URL?>index.php" style="font-weight: 600;">Home</a></li>
                <li><a href="<?php echo BASE_URL?>events/view_events.php" style="font-weight: 600;">Events</a></li>
                <?php if (isset($_SESSION['role_id'])): ?>
                    <?php if ($_SESSION['role_id'] === 1): ?>
                        <li><a href="<?php echo BASE_URL?>dashboards/admin_dashboard.php" style="font-weight: 600;">Admin Dashboard</a></li>
                    <?php elseif ($_SESSION['role_id'] === 2): ?>
                        <li><a href="<?php echo BASE_URL?>dashboards/organizer_dashboard.php" style="font-weight: 600;">Organizer Dashboard</a></li>
                    <?php elseif ($_SESSION['role_id'] === 3): ?>
                        <li><a href="<?php echo BASE_URL?>dashboards/user_dashboard.php" style="font-weight: 600;">User Dashboard</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL?>about.php" style="font-weight: 600;">About</a></li>
                <li><a href="<?php echo BASE_URL?>contactus.php" style="font-weight: 600;">Contact Us</a></li>
            </ul>
        </nav>
    </div>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?php echo BASE_URL?>auth/logout.php">
            <button class="button-exploreevents" style="margin-left: 24px; background: linear-gradient(45deg, #FFD700, #FFA500); border: none;">Logout</button>
        </a>
    <?php else: ?>
        <a href="<?php echo BASE_URL?>auth/login.php">
            <button class="button-exploreevents" style="margin-left: 24px; background: linear-gradient(45deg, #FFD700, #FFA500); border: none;">Login</button>
        </a>
        <a href="<?php echo BASE_URL?>auth/register.php">
            <button class="button-exploreevents" style="margin-left: 24px;">Register</button>
        </a>
    <?php endif; ?>
</div>