<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check - only admin users can access this layout
if (!isset($_SESSION['role_id']) || !in_array(intval($_SESSION['role_id']), [1,2])) {
    header('Location: ../auth/login.php');
    exit;
}

include 'nav.php'; // Include BASE_URL definition
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL?>assets/main.css">
    <style>
        /* Admin Dashboard Specific Styles */
        body {
            background-color: #121212;
            color: #FFD700;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 250px;
            background: #1a1a1a;
            padding: 20px 0;
            box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .admin-logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }
        
        .admin-logo h2 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
            color: #FFD700;
        }
        
        .admin-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-menu li {
            padding: 0;
            margin: 0;
        }
        
        .admin-menu a {
            display: block;
            padding: 15px 20px;
            color: #ddd;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .admin-menu a:hover, 
        .admin-menu a.active {
            background: #252525;
            border-left: 4px solid #FFD700;
        }
        
        .admin-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .admin-main {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        
        .admin-title h1 {
            margin: 0;
            font-size: 28px;
            color: #FFD700;
        }
        
        .admin-actions {
            display: flex;
            align-items: center;
        }
        
        .admin-user {
            margin-right: 15px;
            display: flex;
            align-items: center;
        }
        
        .admin-user span {
            margin-left: 10px;
            color: #ddd;
        }
        
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 1px solid #333;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #FFD700;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 14px;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .admin-card {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            border: 1px solid #333;
        }
        
        .admin-card h2 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
            color: #FFD700;
            font-size: 22px;
        }
        
        /* Admin table styling */
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
        }
        
        .admin-table th {
            background-color: #252525;
            text-align: left;
            padding: 15px;
            font-weight: bold;
            color: #FFD700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        .admin-table td {
            padding: 15px;
            border-bottom: 1px solid #333;
            color: #ddd;
        }
        
        .admin-table tr:last-child td {
            border-bottom: none;
        }
        
        .admin-table tr:hover td {
            background-color: #252525;
        }
        
        /* Admin buttons */
        .admin-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #FFD700;
            color: #232323 !important;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .admin-btn:hover {
            background-color: #fff;
            box-shadow: 0 0 15px 5px rgba(255, 215, 0, 0.3);
            transform: translateY(-2px);
            color: #232323 !important;
        }
        
        .admin-btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .admin-btn-danger {
            background-color: #ff4d4d;
            color: white !important;
        }
        
        .admin-btn-danger:hover {
            background-color: #ff3333;
            color: white !important;
            box-shadow: 0 0 15px 5px rgba(255, 77, 77, 0.3);
        }
        
        .admin-btn-success {
            background-color: #4CAF50;
            color: white !important;
        }
        
        .admin-btn-success:hover {
            background-color: #45a049;
            color: white !important;
            box-shadow: 0 0 15px 5px rgba(76, 175, 80, 0.3);
        }
        
        /* Admin form styling */
        .admin-form-group {
            margin-bottom: 20px;
        }
        
        .admin-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #FFD700;
            font-weight: bold;
        }
        
        .admin-form-group input,
        .admin-form-group select,
        .admin-form-group textarea {
            width: 100%;
            padding: 12px;
            background-color: #252525;
            border: 1px solid #444;
            border-radius: 4px;
            color: #ddd;
            font-size: 16px;
        }
        
        .admin-form-group input:focus,
        .admin-form-group select:focus,
        .admin-form-group textarea:focus {
            border-color: #FFD700;
            box-shadow: 0 0 8px rgba(255, 215, 0, 0.3);
            outline: none;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .admin-card, .stat-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
    </style>
    <title><?php echo isset($pageTitle) ? 'Admin | ' . $pageTitle : 'Admin Dashboard'; ?></title>
</head>
<body>
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2>AudienceLK</h2>
                <div style="font-size: 14px; color: #aaa; margin-top: 5px;">Admin Panel</div>
            </div>
            
            <ul class="admin-menu">
                <li><a href="<?php echo BASE_URL?>dashboards/admin_dashboard.php" <?php echo $pageTitle == 'Admin Dashboard' ? 'class="active"' : ''; ?>>
                    <span>📊</span> Dashboard
                </a></li>
                <li><a href="<?php echo BASE_URL?>dashboards/admin_dashboard.php#users" <?php echo $pageTitle == 'Manage Users' ? 'class="active"' : ''; ?>>
                    <span>👥</span> Users
                </a></li>
                <li><a href="<?php echo BASE_URL?>dashboards/admin_dashboard.php#events" <?php echo $pageTitle == 'Manage Events' ? 'class="active"' : ''; ?>>
                    <span>🎭</span> Events
                </a></li>
                <li><a href="<?php echo BASE_URL?>dashboards/admin_dashboard.php#categories" <?php echo $pageTitle == 'Manage Categories' ? 'class="active"' : ''; ?>>
                    <span>🏷️</span> Categories
                </a></li>
                <li><a href="<?php echo BASE_URL?>bookings/admin_manage_bookings.php" <?php echo $pageTitle == 'Manage Bookings' ? 'class="active"' : ''; ?>>
                    <span>🎫</span> Bookings
                </a></li>
                <li><a href="<?php echo BASE_URL?>index.php">
                    <span>🏠</span> Public Site
                </a></li>
                <li><a href="<?php echo BASE_URL?>auth/logout.php" style="color: #ff6b6b;">
                    <span>🚪</span> Logout
                </a></li>
            </ul>
        </div>
        
        <!-- Admin Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <div class="admin-title">
                    <h1><?php echo isset($pageTitle) ? $pageTitle : 'Admin Dashboard'; ?></h1>
                </div>
                <div class="admin-actions">
                    <div class="admin-user">
                        <span>👤 <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></span>
                    </div>
                    <a href="<?php echo BASE_URL?>auth/logout.php" class="admin-btn admin-btn-small">Logout</a>
                </div>
            </div>
            
            <!-- Main content goes here -->
            <?php 
            // This is where the content of each page will be displayed 
            ?>