
<?php
// Admin Dashboard: stats, analytics, full CRUD
session_start();
$pageTitle = 'Admin Dashboard';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/login.php');
    exit;
}
// Stats
$total_users = $connection->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
$total_events = $connection->query('SELECT COUNT(*) FROM events')->fetch_row()[0];
$total_bookings = $connection->query('SELECT COUNT(*) FROM bookings')->fetch_row()[0];
$total_revenue = $connection->query("SELECT SUM(amount) FROM payments WHERE status='success'")->fetch_row()[0] ?? 0;
// Popular events
$popular = $connection->query("SELECT e.title, COUNT(b.id) as bookings FROM events e LEFT JOIN bookings b ON e.id = b.event_id GROUP BY e.id ORDER BY bookings DESC LIMIT 5");
?>
<style>
body, h2, h3, ul, li, table, th, td, a {
    color: #FFD700 !important;
}
</style>


<div class="HomeCards1" style="justify-content:center; margin-top:40px;">
  <div class="card" style="width:100%; max-width:900px;">
    <h2 style="text-align:center;">Admin Dashboard</h2>
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
