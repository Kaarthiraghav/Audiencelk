<?php
// Booking system: Manage bookings
$csrf_token = null;
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
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
if (isset($_GET['cancel']) && isset($_GET['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    $id = intval($_GET['cancel']);
    // Delete related payments first to avoid foreign key constraint error
    $stmt = $connection->prepare("DELETE FROM payments WHERE booking_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // Remove booking record
    $stmt = $connection->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // Free up seat
    $event_id = intval($_GET['event_id']);
    $stmt = $connection->prepare("UPDATE events SET total_seats = total_seats + 1 WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->close();
}
// Fetch bookings
$where = ($role === 'admin') ? '' : "WHERE b.user_id = ?";
$sql = "SELECT b.*, e.title, e.price, p.status AS payment_status FROM bookings b LEFT JOIN events e ON b.event_id = e.id LEFT JOIN payments p ON b.id = p.booking_id ";
if ($where) {
    $sql .= $where;
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $connection->query($sql);
}
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
                <a href="?cancel=<?= $row['id'] ?>&event_id=<?= $row['event_id'] ?>&csrf_token=<?= $csrf_token ?>" onclick="return confirm('Cancel booking?')">Cancel</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
<?php include '../includes/footer.php'; ?>