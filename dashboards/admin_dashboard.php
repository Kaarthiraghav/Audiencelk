
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
    <h2>Admin Dashboard</h2>
    <a href="<?php echo BASE_URL ?>events/add_event_categories.php" style="margin-left:16px;">Add Event Category</a>
    <ul>
        <li>Total Users: <?= $total_users ?></li>
        <li>Total Events: <?= $total_events ?></li>
        <li>Total Bookings: <?= $total_bookings ?></li>
        <li>Total Revenue: $<?= $total_revenue ?></li>
    </ul>
    <h3>Most Popular Events</h3>
    <table border="1" cellpadding="5" style="width:100%;margin-bottom:24px;">
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
  </div>
</div>

<?php include '../includes/footer.php'; ?>
