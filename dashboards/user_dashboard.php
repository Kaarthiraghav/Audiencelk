<?php
// User Dashboard: Show bookings, allow cancel, profile management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = 'User Dashboard';
include '../includes/header.php';
include '../includes/db_connect.php';

// Check if user is a regular user (role_id = 3)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 3) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // If user wants to change password
            if (!empty($new_password)) {
                if (strlen($new_password) < 8) {
                    $error = 'New password must be at least 8 characters long.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } else {
                    // Verify current password
                    $stmt = $connection->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();
                    
                    if (!password_verify($current_password, $user_data['password'])) {
                        $error = 'Current password is incorrect.';
                    } else {
                        // Update both email and password
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $connection->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("ssi", $email, $hash, $user_id);
                        if ($stmt->execute()) {
                            $success = 'Profile updated successfully!';
                        } else {
                            $error = 'Failed to update profile.';
                        }
                    }
                }
            } else {
                // Update only email
                $stmt = $connection->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->bind_param("si", $email, $user_id);
                if ($stmt->execute()) {
                    $success = 'Email updated successfully!';
                } else {
                    $error = 'Failed to update email.';
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred while updating your profile.';
        }
    }
}

// Fetch user bookings with event details
try {
    $stmt = $connection->prepare("SELECT b.*, e.title, e.venue, e.event_date, e.price FROM bookings b JOIN events e ON b.event_id = e.id WHERE b.user_id = ? ORDER BY b.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $bookings = $stmt->get_result();
} catch (Exception $e) {
    $bookings = null;
    $error = 'Error loading your bookings.';
}

// Fetch user details
try {
    $stmt = $connection->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} catch (Exception $e) {
    $user = null;
    $error = 'Error loading user data.';
}

// Get booking statistics
try {
    $total_bookings = $connection->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
    $total_bookings->bind_param("i", $user_id);
    $total_bookings->execute();
    $total_bookings_count = $total_bookings->get_result()->fetch_row()[0];
    
    $upcoming_events = $connection->prepare("SELECT COUNT(*) FROM bookings b JOIN events e ON b.event_id = e.id WHERE b.user_id = ? AND e.event_date > NOW()");
    $upcoming_events->bind_param("i", $user_id);
    $upcoming_events->execute();
    $upcoming_events_count = $upcoming_events->get_result()->fetch_row()[0];
} catch (Exception $e) {
    $total_bookings_count = $upcoming_events_count = 0;
}
?>

<div class="HomeCards1" style="justify-content:center; margin-top:40px;">
  <div class="card" style="width:100%; max-width:1000px;">
    <h2 style="text-align:center; margin-bottom: 30px;">Welcome, <?= htmlspecialchars($user['username'] ?? 'User') ?>!</h2>
    
    <?php if (!empty($success)): ?>
        <div class="message success" style="margin-bottom: 20px;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="message error" style="margin-bottom: 20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <!-- Quick Stats -->
    <div style="display: flex; justify-content: space-around; margin: 30px 0; flex-wrap: wrap;">
        <div style="background: #232323; padding: 20px; border-radius: 12px; margin: 10px; text-align: center; min-width: 180px; border: 2px solid #FFD700;">
            <h3 style="margin: 0 0 10px 0; color: #FFD700;">Total Bookings</h3>
            <div style="font-size: 28px; font-weight: bold; color: #FFD700;"><?= $total_bookings_count ?></div>
        </div>
        <div style="background: #232323; padding: 20px; border-radius: 12px; margin: 10px; text-align: center; min-width: 180px; border: 2px solid #FFD700;">
            <h3 style="margin: 0 0 10px 0; color: #FFD700;">Upcoming Events</h3>
            <div style="font-size: 28px; font-weight: bold; color: #FFD700;"><?= $upcoming_events_count ?></div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div style="text-align: center; margin: 30px 0;">
        <a href="../events/view_events.php" class="button-exploreevents" style="margin: 0 10px;">Browse Events</a>
        <a href="../bookings/add_booking.php" class="button-exploreevents" style="margin: 0 10px;">Book New Event</a>
        <a href="../bookings/manage_bookings.php" class="button-exploreevents" style="margin: 0 10px;">Manage Bookings</a>
    </div>
    
    <!-- Recent Bookings -->
    <h3 style="text-align: center; margin: 40px 0 20px 0;">Your Recent Bookings</h3>
    <?php if ($bookings && $bookings->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; min-width: 600px; border-collapse: separate; border-spacing: 0; background: #1a1a1a; border-radius: 12px; overflow: hidden;">
                <thead>
                    <tr style="background: #2c2c2c;">
                        <th style="padding: 15px; text-align: left; color: #FFD700; border-bottom: 2px solid #FFD700;">Event</th>
                        <th style="padding: 15px; text-align: center; color: #FFD700; border-bottom: 2px solid #FFD700;">Venue</th>
                        <th style="padding: 15px; text-align: center; color: #FFD700; border-bottom: 2px solid #FFD700;">Date</th>
                        <th style="padding: 15px; text-align: center; color: #FFD700; border-bottom: 2px solid #FFD700;">Price</th>
                        <th style="padding: 15px; text-align: center; color: #FFD700; border-bottom: 2px solid #FFD700;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $bookings->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #333;">
                        <td style="padding: 15px; color: #FFD700;"><?= htmlspecialchars($row['title']) ?></td>
                        <td style="padding: 15px; text-align: center; color: #ddd;"><?= htmlspecialchars($row['venue']) ?></td>
                        <td style="padding: 15px; text-align: center; color: #ddd;"><?= date('M j, Y', strtotime($row['event_date'])) ?></td>
                        <td style="padding: 15px; text-align: center; color: #ddd;">$<?= number_format($row['price'], 2) ?></td>
                        <td style="padding: 15px; text-align: center;">
                            <span style="padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;
                                background: rgba(40, 167, 69, 0.2); color: #28a745; border: 1px solid #28a745;">
                                Confirmed
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="../bookings/manage_bookings.php" class="button-backtohome">View All Bookings</a>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; background: #232323; border-radius: 12px; margin: 20px 0;">
            <h3 style="color: #aaa; margin-bottom: 15px;">No bookings yet</h3>
            <p style="color: #888; margin-bottom: 25px;">Start exploring events and make your first booking!</p>
            <a href="../events/view_events.php" class="button-exploreevents">Browse Events</a>
        </div>
    <?php endif; ?>
    
    <!-- Profile Section -->
    <h3 style="text-align: center; margin: 40px 0 20px 0;">Profile Settings</h3>
    <div style="max-width: 500px; margin: 0 auto;">
        <form method="post" class="beautiful-form">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="current_password">Current Password (required for password change)</label>
                <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password (leave blank to keep current)</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
            </div>
            
            <button type="submit" class="button-exploreevents" style="width: 100%; margin-top: 20px;">Update Profile</button>
        </form>
    </div>
    
    <div style="text-align: center; margin-top: 40px;">
        <a href="<?= BASE_URL ?>auth/logout.php" class="button-backtohome">Logout</a>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>