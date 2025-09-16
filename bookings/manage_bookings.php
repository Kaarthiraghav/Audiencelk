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
?>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<?php
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
    <div class="container" style="max-width: 1200px; margin: 30px auto; padding: 0 20px; animation: fadeIn 0.8s ease-out;">
        <h1 class="page-title" style="text-align: center; margin-bottom: 30px; color: #FFD700; text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);">My Bookings</h1>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="bookings-table" style="background: #1e1e1e; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); border: 1px solid #333; margin-bottom: 30px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: rgba(0, 0, 0, 0.2);">
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #333; color: #FFD700;">ID</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #333; color: #FFD700;">Event</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #333; color: #FFD700;">Price</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #333; color: #FFD700;">Status</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #333; color: #FFD700;">Payment</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #333; color: #FFD700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #333; transition: background-color 0.3s;">
                            <td style="padding: 15px; color: #ddd;"><?= $row['id'] ?></td>
                            <td style="padding: 15px; color: #ddd;"><?= htmlspecialchars($row['title']) ?></td>
                            <td style="padding: 15px; color: #FFD700;">₹<?= number_format(htmlspecialchars($row['price']), 2) ?></td>
                            <td style="padding: 15px;">
                                <?php if (isset($row['status']) && $row['status'] == 'canceled'): ?>
                                    <span style="color: #ff6b6b; background-color: rgba(255, 107, 107, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Cancelled</span>
                                <?php else: ?>
                                    <span style="color: #69db7c; background-color: rgba(105, 219, 124, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Confirmed</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;">
                                <?php if (!empty($row['payment_status']) && $row['payment_status'] == 'success'): ?>
                                    <span style="color: #69db7c; background-color: rgba(105, 219, 124, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Paid</span>
                                <?php elseif (!empty($row['payment_status']) && $row['payment_status'] == 'pending'): ?>
                                    <span style="color: #ffd43b; background-color: rgba(255, 212, 59, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Pending</span>
                                <?php else: ?>
                                    <span style="color: #adb5bd; background-color: rgba(173, 181, 189, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Free</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <?php if (!isset($row['status']) || $row['status'] !== 'canceled'): ?>
                                <a href="?cancel=<?= $row['id'] ?>&event_id=<?= $row['event_id'] ?>" onclick="return confirm('Are you sure you want to cancel this booking?')" style="color: #ff6b6b; text-decoration: none; display: inline-block; padding: 5px 15px; border: 1px solid #ff6b6b; border-radius: 4px; transition: all 0.3s;">Cancel</a>
                                <?php else: ?>
                                <span style="color: #666; font-style: italic;">Cancelled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="../events/view_events.php" class="button-exploreevents" style="display: inline-block; margin: 0 10px;">View More Events</a>
            </div>
            
        <?php else: ?>
            <div class="no-bookings" style="text-align: center; padding: 50px 0;">
                <div style="font-size: 4em; color: #333; margin-bottom: 20px;">📅</div>
                <h2 style="color: #FFD700; margin-bottom: 15px;">No Bookings Yet</h2>
                <p style="color: #ddd; max-width: 600px; margin: 0 auto 30px;">You haven't booked any events yet. Check out our available events and secure your spot today!</p>
                <a href="../events/view_events.php" class="button-exploreevents" style="display: inline-block;">Explore Events</a>
            </div>
        <?php endif; ?>
    </div>

<?php include '../includes/footer.php'; ?>