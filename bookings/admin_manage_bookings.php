<?php
// Admin: Manage bookings with admin layout
session_start();
$pageTitle = 'Manage Bookings';
include '../includes/db_connect.php';

// Security check - only admin users can access
if (!isset($_SESSION['role_id']) || intval($_SESSION['role_id']) !== 1) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle cancel with prepared statements
if (isset($_GET['cancel'])) {
    $id = intval($_GET['cancel']);
    $event_id = intval($_GET['event_id'] ?? 0);
    
    // Use transactions to ensure all operations complete or none do
    $connection->begin_transaction();
    
    try {
        // Delete related payments first to avoid foreign key constraint error
        $stmt1 = $connection->prepare("DELETE FROM payments WHERE booking_id = ?");
        $stmt1->bind_param('i', $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Remove booking record
        $stmt2 = $connection->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt2->bind_param('i', $id);
        $stmt2->execute();
        $stmt2->close();
        
        // Free up seat if event_id provided
        if ($event_id) {
            $stmt3 = $connection->prepare("UPDATE events SET seats = seats + 1 WHERE id = ?");
            $stmt3->bind_param('i', $event_id);
            $stmt3->execute();
            $stmt3->close();
        }
        
        // Commit the transaction
        $connection->commit();
    } catch (Exception $e) {
        // An error occurred; rollback the transaction
        $connection->rollback();
    }
}

// Fetch all bookings with prepared statement
$stmt = $connection->prepare("SELECT b.*, e.title, e.price, p.status AS payment_status, u.username 
                            FROM bookings b 
                            LEFT JOIN events e ON b.event_id = e.id 
                            LEFT JOIN payments p ON b.id = p.booking_id
                            LEFT JOIN users u ON b.user_id = u.id
                            ORDER BY b.id DESC");
$stmt->execute();
$bookings = $stmt->get_result();
$stmt->close();

// Include admin layout
include '../includes/admin_layout.php';
?>

<!-- Admin content -->
<div class="admin-card">
    <h2>Manage Bookings</h2>
    
    <?php if ($bookings && $bookings->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($booking = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td><?= $booking['id'] ?></td>
                        <td><?= htmlspecialchars($booking['username'] ?? 'Unknown User') ?></td>
                        <td><?= htmlspecialchars($booking['title'] ?? 'Unknown Event') ?></td>
                        <td>LKR <?= number_format($booking['price'] ?? 0, 2) ?></td>
                        <td>
                            <?php if (isset($booking['status']) && $booking['status'] == 'canceled'): ?>
                                <span style="color: #ff6b6b; background-color: rgba(255, 107, 107, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Cancelled</span>
                            <?php else: ?>
                                <span style="color: #69db7c; background-color: rgba(105, 219, 124, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Confirmed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($booking['payment_status']) && $booking['payment_status'] == 'success'): ?>
                                <span style="color: #69db7c; background-color: rgba(105, 219, 124, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Paid</span>
                            <?php elseif (!empty($booking['payment_status']) && $booking['payment_status'] == 'pending'): ?>
                                <span style="color: #ffd43b; background-color: rgba(255, 212, 59, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Pending</span>
                            <?php else: ?>
                                <span style="color: #adb5bd; background-color: rgba(173, 181, 189, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Free</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($booking['created_at'])) ?></td>
                        <td style="white-space: nowrap;">
                            <?php if (!isset($booking['status']) || $booking['status'] !== 'canceled'): ?>
                                <a href="?cancel=<?= $booking['id'] ?>&event_id=<?= $booking['event_id'] ?>" onclick="return confirm('Are you sure you want to cancel this booking?')" class="admin-btn admin-btn-small admin-btn-danger">Cancel</a>
                            <?php else: ?>
                                <span style="color: #666; font-style: italic;">Cancelled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No bookings found.</p>
    <?php endif; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>