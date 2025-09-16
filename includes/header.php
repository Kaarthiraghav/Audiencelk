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
    <style>
        body {
            background-color: #121212;
            color: #FFD700;
            font-family: 'Arial', sans-serif;
            transition: opacity 0.5s;
            /* opacity: 0; */
        }
        
        /* body.fade-in {
            opacity: 1;
        }
         */
        body, h1, h2, h3, h4, h5, h6, p, label, th, td, ul, li, a, div, span {
            color: #FFD700 !important;
        }
        
        .glow-effect {
            text-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
            position: relative;
            display: inline-block;
        }
        
        .glow-effect::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #FFD700, transparent);
            transform: scaleX(0.3);
            transform-origin: center;
            transition: transform 0.3s;
        }
        
        .glow-effect:hover::after {
            transform: scaleX(1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card, .form-container, table {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            background-color: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        th {
            background-color: #2c2c2c;
            padding: 15px 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85em;
            border-bottom: 2px solid #FFD700;
            color: #FFD700 !important;
            font-weight: bold;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #333;
            transition: all 0.3s ease;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover td {
            background-color: #252525;
        }
        
        /* Zebra striping for rows */
        tr:nth-child(even) {
            background-color: #1d1d1d;
        }
        
        a {
            color: #FFD700 !important;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        a:hover {
            color: white !important;
            text-shadow: 0 0 5px #FFD700;
        }
        
        .button-exploreevents, .button-backtohome, input[type="submit"], button {
            color: #232323 !important;
            background-color: #FFD700;
            border: none;
            border-radius: 30px;
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .button-exploreevents:hover, .button-backtohome:hover, input[type="submit"]:hover, button:hover {
            background-color: #fff;
            box-shadow: 0 0 15px 5px rgba(255, 215, 0, 0.5);
            transform: translateY(-2px);
        }
        
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], 
        input[type="datetime-local"], textarea, select {
            background-color: #2c2c2c;
            color: #FFD700;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 10px;
            transition: all 0.3s ease;
        }
        
        input:focus, textarea:focus, select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 8px rgba(255, 215, 0, 0.5);
            outline: none;
        }
    </style>
    <title><?php echo isset($pageTitle) ? $pageTitle : 'AudienceLK'; ?></title>
</head>
<body>
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
                        <li><a href="<?php echo BASE_URL?>dashboards/organizer_dashboard.php">Organizer Dashboard</a></li>
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
        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] === 1): ?>
            <a href="<?php echo BASE_URL?>dashboards/admin_dashboard.php">
                <button class="button-exploreevents" style="margin-left: 24px; background: linear-gradient(45deg, #FF6B6B, #FF6B6B); border: none;">Admin Panel</button>
            </a>
        <?php endif; ?>
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