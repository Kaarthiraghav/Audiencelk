<?php
// Admin: Manage events with admin layout
session_start();
$pageTitle = 'Manage Events';
include '../includes/db_connect.php';

// Security check - only admin users can access
if (!isset($_SESSION['role_id']) || intval($_SESSION['role_id']) !== 1) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle delete with prepared statement for security
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $connection->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

// Handle approve event (admin only)
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $stmt = $connection->prepare("UPDATE events SET status = 'approved' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch events with prepared statement
$stmt = $connection->prepare("SELECT e.*, c.category AS category FROM events e LEFT JOIN event_categories c ON e.category_id = c.id");
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();

// Include admin layout
include '../includes/admin_layout.php';
?>

<!-- Admin content -->
<div class="admin-card">
    <h2>Manage Events</h2>
    <div style="margin-bottom: 20px; text-align: right;">
        <a href="../events/add_event.php" class="admin-btn admin-btn-small">
            <span style="margin-right: 8px;">➕</span> Add New Event
        </a>
    </div>
    
    <?php if ($events && $events->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Seats</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($event = $events->fetch_assoc()): ?>
                    <tr>
                        <td><?= $event['id'] ?></td>
                        <td><?= htmlspecialchars($event['title']) ?></td>
                        <td><?= htmlspecialchars($event['category']) ?></td>
                        <td>
                            <?php if ($event['total_seats'] < 10): ?>
                                <span style="color: #ff6b6b; font-weight: bold;"><?= $event['total_seats'] ?></span>
                            <?php else: ?>
                                <span style="color: #69db7c; font-weight: bold;"><?= $event['total_seats'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>LKR <?= number_format($event['price'], 2) ?></td>
                        <td>
                            <?php if ($event['status'] == 'approved'): ?>
                                <span style="color: #69db7c; background-color: rgba(105, 219, 124, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Approved</span>
                            <?php elseif ($event['status'] == 'pending'): ?>
                                <span style="color: #ffd43b; background-color: rgba(255, 212, 59, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Pending</span>
                            <?php else: ?>
                                <span style="color: #ff6b6b; background-color: rgba(255, 107, 107, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="../events/edit_event.php?id=<?= $event['id'] ?>" class="admin-btn admin-btn-small" style="margin-right: 5px;">Edit</a>
                            <a href="?delete=<?= $event['id'] ?>" onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')" class="admin-btn admin-btn-small admin-btn-danger" style="margin-right: 5px;">Delete</a>
                            <?php if ($event['status'] == 'pending'): ?>
                                <a href="?approve=<?= $event['id'] ?>" onclick="return confirm('Approve this event?')" class="admin-btn admin-btn-small admin-btn-success">Approve</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No events found.</p>
    <?php endif; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>