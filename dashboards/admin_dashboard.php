
<?php
// Admin Dashboard: Comprehensive admin control panel
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = 'Admin Dashboard';
include '../includes/db_connect.php';

// Security check - only admins can access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/login.php');
    exit;
}

include '../includes/admin_layout.php';

// Handle various admin actions
$message = '';
$error = '';

// Process form submissions
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_event':
                $event_id = intval($_POST['event_id']);
                $stmt = $connection->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
                $stmt->bind_param("i", $event_id);
                if ($stmt->execute()) {
                    $message = "Event approved successfully!";
                } else {
                    $error = "Failed to approve event.";
                }
                break;
                
            case 'reject_event':
                $event_id = intval($_POST['event_id']);
                $stmt = $connection->prepare("UPDATE events SET status = 'rejected' WHERE id = ?");
                $stmt->bind_param("i", $event_id);
                if ($stmt->execute()) {
                    $message = "Event rejected successfully!";
                } else {
                    $error = "Failed to reject event.";
                }
                break;
                
            case 'delete_user':
                $user_id = intval($_POST['user_id']);
                if ($user_id !== $_SESSION['user_id']) { // Don't allow deleting self
                    $stmt = $connection->prepare("DELETE FROM users WHERE id = ? AND role_id != 1");
                    $stmt->bind_param("i", $user_id);
                    if ($stmt->execute()) {
                        $message = "User deleted successfully!";
                    } else {
                        $error = "Failed to delete user.";
                    }
                } else {
                    $error = "Cannot delete your own account.";
                }
                break;
                
            case 'add_category':
                $cat_name = trim($_POST['category_name']);
                $cat_desc = trim($_POST['category_description']);
                if (!empty($cat_name)) {
                    $stmt = $connection->prepare("INSERT INTO event_categories (name, description) VALUES (?, ?)");
                    $stmt->bind_param("ss", $cat_name, $cat_desc);
                    if ($stmt->execute()) {
                        $message = "Category added successfully!";
                    } else {
                        $error = "Failed to add category.";
                    }
                } else {
                    $error = "Category name is required.";
                }
                break;
        }
    }
}

// Fetch statistics
try {
    $total_users = $connection->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $total_events = $connection->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
    $pending_events = $connection->query("SELECT COUNT(*) as count FROM events WHERE status = 'pending'")->fetch_assoc()['count'];
    $total_bookings = $connection->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
    $total_categories = $connection->query("SELECT COUNT(*) as count FROM event_categories")->fetch_assoc()['count'];
    
    // Recent activities
    $recent_users = $connection->query("SELECT username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $pending_events_list = $connection->query("SELECT e.*, u.username as organizer_name FROM events e JOIN users u ON e.organizer_id = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC LIMIT 10");
    $recent_bookings = $connection->query("SELECT b.*, e.title as event_title, u.username FROM bookings b JOIN events e ON b.event_id = e.id JOIN users u ON b.user_id = u.id ORDER BY b.booking_date DESC LIMIT 8");
    
} catch (Exception $e) {
    $error = "Error loading dashboard data.";
}
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Dashboard Statistics -->
<div class="admin-stats">
    <div class="stat-card">
        <div class="stat-value"><?= $total_users ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $total_events ?></div>
        <div class="stat-label">Total Events</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $pending_events ?></div>
        <div class="stat-label">Pending Events</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $total_bookings ?></div>
        <div class="stat-label">Total Bookings</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $total_categories ?></div>
        <div class="stat-label">Categories</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <h2>🚀 Quick Actions</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="../events/add_event.php" class="admin-btn">➕ Add Event</a>
        <a href="#categories" class="admin-btn">🏷️ Manage Categories</a>
        <a href="../bookings/admin_manage_bookings.php" class="admin-btn">🎫 View Bookings</a>
        <a href="../events/view_events.php" class="admin-btn">👁️ View Public Site</a>
    </div>
</div>

<!-- Pending Events for Approval -->
<?php if (isset($pending_events_list) && $pending_events_list->num_rows > 0): ?>
<div class="admin-card" id="events">
    <h2>⏳ Pending Events (Require Approval)</h2>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Organizer</th>
                    <th>Date</th>
                    <th>Venue</th>
                    <th>Seats</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($event = $pending_events_list->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($event['title']) ?></strong>
                        <br><small style="color: #aaa;"><?= htmlspecialchars(substr($event['description'], 0, 80)) ?>...</small>
                    </td>
                    <td><?= htmlspecialchars($event['organizer_name']) ?></td>
                    <td><?= date('M d, Y H:i', strtotime($event['event_date'])) ?></td>
                    <td><?= htmlspecialchars($event['venue']) ?></td>
                    <td><?= $event['total_seats'] ?></td>
                    <td>LKR <?= number_format($event['price'], 2) ?></td>
                    <td>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="approve_event">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button type="submit" class="admin-btn admin-btn-small admin-btn-success" onclick="return confirm('Approve this event?')">✅ Approve</button>
                        </form>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="reject_event">
                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                            <button type="submit" class="admin-btn admin-btn-small admin-btn-danger" onclick="return confirm('Reject this event?')">❌ Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- User Management -->
<div class="admin-card" id="users">
    <h2>👥 User Management</h2>
    <?php
    $users = $connection->query("SELECT u.*, r.role FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC LIMIT 15");
    ?>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span class="admin-role-badge role-<?= strtolower($user['role']) ?>">
                            <?= htmlspecialchars($user['role']) ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($user['created_at'] ?? 'now')) ?></td>
                    <td>
                        <?php if ($user['id'] !== $_SESSION['user_id'] && $user['role'] !== 'Admin'): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="admin-btn admin-btn-small admin-btn-danger" onclick="return confirm('Delete this user? This action cannot be undone.')">🗑️ Delete</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #aaa;">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Category Management -->
<div class="admin-card" id="categories">
    <h2>🏷️ Category Management</h2>
    
    <!-- Add New Category Form -->
    <div style="background: #252525; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: #FFD700;">Add New Category</h3>
        <form method="post" style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 5px; color: #FFD700;">Category Name</label>
                <input type="text" name="category_name" required maxlength="150" style="width: 100%; padding: 8px; background: #1a1a1a; border: 1px solid #444; border-radius: 4px; color: #fff;">
            </div>
            <div>
                <label style="display: block; margin-bottom: 5px; color: #FFD700;">Description</label>
                <input type="text" name="category_description" maxlength="255" style="width: 100%; padding: 8px; background: #1a1a1a; border: 1px solid #444; border-radius: 4px; color: #fff;">
            </div>
            <div>
                <input type="hidden" name="action" value="add_category">
                <button type="submit" class="admin-btn">➕ Add Category</button>
            </div>
        </form>
    </div>
    
    <!-- Existing Categories -->
    <?php
    $categories = $connection->query("SELECT c.*, COUNT(e.id) as event_count FROM event_categories c LEFT JOIN events e ON c.id = e.category_id GROUP BY c.id ORDER BY c.name");
    ?>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Events</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($category = $categories->fetch_assoc()): ?>
                <tr>
                    <td><?= $category['id'] ?></td>
                    <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                    <td><?= htmlspecialchars($category['description'] ?? 'No description') ?></td>
                    <td><?= $category['event_count'] ?> events</td>
                    <td><?= date('M d, Y', strtotime($category['created_at'] ?? 'now')) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Activity -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Recent Users -->
    <div class="admin-card">
        <h2>👤 Recent Registrations</h2>
        <?php if (isset($recent_users) && $recent_users->num_rows > 0): ?>
            <div style="space-y: 10px;">
                <?php while ($user = $recent_users->fetch_assoc()): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #333;">
                    <div>
                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                        <br><small style="color: #aaa;"><?= htmlspecialchars($user['email']) ?></small>
                    </div>
                    <small style="color: #aaa;"><?= date('M d', strtotime($user['created_at'] ?? 'now')) ?></small>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: #aaa; text-align: center; padding: 20px;">No recent registrations</p>
        <?php endif; ?>
    </div>
    
    <!-- Recent Bookings -->
    <div class="admin-card">
        <h2>🎫 Recent Bookings</h2>
        <?php if (isset($recent_bookings) && $recent_bookings->num_rows > 0): ?>
            <div style="space-y: 10px;">
                <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #333;">
                    <div>
                        <strong><?= htmlspecialchars($booking['event_title']) ?></strong>
                        <br><small style="color: #aaa;">by <?= htmlspecialchars($booking['username']) ?></small>
                    </div>
                    <small style="color: #aaa;"><?= date('M d', strtotime($booking['booking_date'])) ?></small>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color: #aaa; text-align: center; padding: 20px;">No recent bookings</p>
        <?php endif; ?>
    </div>
</div>

<style>
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid;
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border-color: #28a745;
}

.alert-error {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border-color: #dc3545;
}

.admin-role-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.role-admin {
    background: #ff4d4d;
    color: white;
}

.role-organizer {
    background: #ffc107;
    color: #000;
}

.role-user {
    background: #17a2b8;
    color: white;
}

@media (max-width: 768px) {
    .admin-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .admin-table {
        font-size: 0.9em;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 10px 8px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
