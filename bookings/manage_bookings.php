<?php
// Booking system: Manage bookings
session_start();
$pageTitle = 'Manage Bookings';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role'])) {
    header('Location: ../auth/login.php');
    exit;
}
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
// Handle cancel
if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    // Delete related payments first to avoid foreign key constraint error
    $connection->query("DELETE FROM payments WHERE booking_id = $id");
    // Remove booking record
    $connection->query("DELETE FROM bookings WHERE id = $id");
    // Free up seat
    $event_id = intval($_GET['event_id']);
    $connection->query("UPDATE events SET total_seats = total_seats + 1 WHERE id = $event_id");
}
// Fetch bookings
$where = ($role === 'admin') ? '' : "WHERE b.user_id = $user_id";
$sql = "SELECT b.*, e.title, e.price, p.status AS payment_status FROM bookings b LEFT JOIN events e ON b.event_id = e.id LEFT JOIN payments p ON b.id = p.booking_id ";
if ($where) {
    $sql .= $where;
}
$result = $connection->query($sql);
?>
    <h2>Manage Bookings</h2>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Event</th><th>Price</th><th>Status</th><th>Payment</th><th>Actions</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['price']) ?></td>
            <td><?= htmlspecialchars(isset($row['status']) ? $row['status'] : 'success') ?></td>
            <td><?= htmlspecialchars($row['payment_status']) ?></td>
            <td>
                <?php if (!isset($row['status']) || $row['status'] !== 'canceled'): ?>
                <a href="?cancel=<?= $row['id'] ?>&event_id=<?= $row['event_id'] ?>" onclick="return confirm('Cancel booking?')">Cancel</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
<?php include '../includes/footer.php'; ?>