<?php
// Public: View all events, book if logged in
$csrf_token = null;
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
$pageTitle = 'View Events';
include '../includes/header.php';
include '../includes/db_connect.php';
// Fetch approved events
$result = $connection->query("SELECT e.*, c.category AS category_name FROM events e LEFT JOIN event_categories c ON e.category_id = c.category WHERE e.status='approved'");
?>
<div class="HomeCards1">
<div class="card">
    <h2>All Events</h2>
    <?php if ($result && $result->num_rows > 0): ?>
        <table border="1" cellpadding="5">
            <tr><th>Title</th><th>Category</th><th>Seats</th><th>Action</th></tr>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['category_name'] ?? $row['category_name']) ?></td>
                <td><?= $row['total_seats'] ?></td>
                <td>
                    <?php if ($row['total_seats'] < 1): ?>
                        <span style="color:gray;">Sold Out</span>
                    <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role_id'] === 3): ?>
                        <form action="../bookings/add_booking.php" method="post" style="display:inline;">
                            <input type="hidden" name="event_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="submit" value="Book Now">
                        </form>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <button disabled>Book Now</button>
                    <?php else: ?>
                        <a href="../auth/login.php">Book Now</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No events available at the moment.</p>
    <?php endif; ?>
</div>
</div>

<?php include '../includes/footer.php'; ?>
