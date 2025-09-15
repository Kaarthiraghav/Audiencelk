
<?php
// Admin Dashboard: stats, analytics, full CRUD
session_start();
$pageTitle = 'Admin Dashboard';
include '../includes/db_connect.php';
include '../includes/admin_layout.php';

// Stats
$total_users = $connection->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
$total_events = $connection->query('SELECT COUNT(*) FROM events')->fetch_row()[0];
$total_bookings = $connection->query('SELECT COUNT(*) FROM bookings')->fetch_row()[0];
$total_revenue = $connection->query("SELECT SUM(amount) FROM payments WHERE status='success'")->fetch_row()[0] ?? 0;

// Recent Users
$recent_users = $connection->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");

// Pending Events
$pending_events = $connection->query("SELECT id, title, category, seats, price, organizer_id, created_at FROM events WHERE status='pending' LIMIT 5");

// Popular events
$popular_events = $connection->query("SELECT e.id, e.title, e.category, e.seats, COUNT(b.id) as bookings FROM events e LEFT JOIN bookings b ON e.id = b.event_id GROUP BY e.id ORDER BY bookings DESC LIMIT 5");

// Recent bookings
$recent_bookings = $connection->query("SELECT b.id, u.username, e.title, b.created_at FROM bookings b JOIN users u ON b.user_id = u.id JOIN events e ON b.event_id = e.id ORDER BY b.created_at DESC LIMIT 5");

// Get user roles for recent users
$recent_users_with_roles = $connection->query("SELECT u.id, u.username, u.email, u.created_at, r.role 
                                          FROM users u 
                                          JOIN roles r ON u.role_id = r.id 
                                          ORDER BY u.created_at DESC LIMIT 5");
?>

<<<<<<< Updated upstream
<<<<<<< Updated upstream
    <h2>Admin Dashboard</h2>
    <a href="<?php echo BASE_URL ?>events/add_event_categories.php" style="margin-left:16px;">Add Event Category</a>
    <ul>
        <li>Total Users: <?= $total_users ?></li>
        <li>Total Events: <?= $total_events ?></li>
        <li>Total Bookings: <?= $total_bookings ?></li>
        <li>Total Revenue: $<?= $total_revenue ?></li>
    </ul>
    <h3>Most Popular Events</h3>
    <table border="1" cellpadding="5">
        <tr><th>Event</th><th>Bookings</th></tr>
        <?php while ($row = $popular->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= $row['bookings'] ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <h3>Manage</h3>
    <ul>
        <li><a href="../users/manage_users.php">Users</a></li>
        <li><a href="../events/manage_events.php">Events</a></li>
        <li><a href="../bookings/manage_bookings.php">Bookings</a></li>
    </ul>
    <a href="../auth/logout.php">Logout</a>

    <?php include '../includes/footer.php'; ?>
=======
=======
>>>>>>> Stashed changes
<!-- Admin Dashboard Content -->
<div class="admin-stats">
    <div class="stat-card">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= $total_users ?></div>
        <div><a href="../users/admin_manage_users.php" class="admin-btn admin-btn-small" style="margin-top: 10px;">Manage Users</a></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Total Events</div>
        <div class="stat-value"><?= $total_events ?></div>
        <div><a href="../events/admin_manage_events.php" class="admin-btn admin-btn-small" style="margin-top: 10px;">Manage Events</a></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Total Bookings</div>
        <div class="stat-value"><?= $total_bookings ?></div>
        <div><a href="../bookings/admin_manage_bookings.php" class="admin-btn admin-btn-small" style="margin-top: 10px;">View Bookings</a></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value">LKR <?= number_format($total_revenue, 2) ?></div>
        <div style="color: #aaa; font-size: 12px; margin-top: 5px;">From successful payments</div>
    </div>
</div>

<div class="admin-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <!-- Recent Users -->
    <div class="admin-card">
        <h2>Recent Users</h2>
        <?php if ($recent_users_with_roles && $recent_users_with_roles->num_rows > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $recent_users_with_roles->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if ($user['role'] == 'admin'): ?>
                                    <span style="color: #ff6b6b;"><?= ucfirst(htmlspecialchars($user['role'])) ?></span>
                                <?php elseif ($user['role'] == 'organizer'): ?>
                                    <span style="color: #ffd43b;"><?= ucfirst(htmlspecialchars($user['role'])) ?></span>
                                <?php else: ?>
                                    <span style="color: #69db7c;"><?= ucfirst(htmlspecialchars($user['role'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="text-align: right; margin-top: 15px;">
                <a href="../users/manage_users.php" class="admin-btn admin-btn-small">View All Users</a>
            </div>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    </div>
    
    <!-- Pending Events -->
    <div class="admin-card">
        <h2>Pending Events</h2>
        <?php if ($pending_events && $pending_events->num_rows > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Seats</th>
                        <th>Price</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $pending_events->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($event['title']) ?></td>
                            <td><?= htmlspecialchars($event['category']) ?></td>
                            <td><?= $event['seats'] ?></td>
                            <td>LKR <?= number_format($event['price'], 2) ?></td>
                            <td><?= date('M j, Y', strtotime($event['created_at'])) ?></td>
                            <td style="white-space: nowrap;">
                                <form method="post" action="../events/edit_event.php" style="display:inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="admin-btn admin-btn-small admin-btn-success" style="margin-right: 5px;">✓</button>
                                </form>
                                <form method="post" action="../events/edit_event.php" style="display:inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="admin-btn admin-btn-small admin-btn-danger" onclick="return confirm('Are you sure you want to reject this event?')">✕</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="text-align: right; margin-top: 15px;">
                <a href="../events/manage_events.php" class="admin-btn admin-btn-small">Manage All Events</a>
            </div>
        <?php else: ?>
            <p>No pending events.</p>
        <?php endif; ?>
    </div>
</div>

<div class="admin-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Popular Events -->
    <div class="admin-card">
        <h2>Most Popular Events</h2>
        <?php if ($popular_events && $popular_events->num_rows > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Category</th>
                        <th>Available Seats</th>
                        <th>Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $popular_events->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($event['title']) ?></td>
                            <td><?= htmlspecialchars($event['category']) ?></td>
                            <td>
                                <?php if ($event['seats'] < 10): ?>
                                    <span style="color: #ff6b6b; font-weight: bold;"><?= $event['seats'] ?></span>
                                <?php else: ?>
                                    <span style="color: #69db7c;"><?= $event['seats'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span style="font-weight: bold; color: #FFD700;"><?= $event['bookings'] ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="text-align: right; margin-top: 15px;">
                <a href="../events/manage_events.php" class="admin-btn admin-btn-small">View All Events</a>
            </div>
        <?php else: ?>
            <p>No event bookings yet.</p>
        <?php endif; ?>
    </div>
    
    <!-- Recent Bookings -->
    <div class="admin-card">
        <h2>Recent Bookings</h2>
        <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Event</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($booking['username']) ?></td>
                            <td><?= htmlspecialchars($booking['title']) ?></td>
                            <td><?= date('M j, Y', strtotime($booking['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div style="text-align: right; margin-top: 15px;">
                <a href="../bookings/manage_bookings.php" class="admin-btn admin-btn-small">View All Bookings</a>
            </div>
        <?php else: ?>
            <p>No bookings found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
<<<<<<< Updated upstream
>>>>>>> Stashed changes
=======
>>>>>>> Stashed changes
