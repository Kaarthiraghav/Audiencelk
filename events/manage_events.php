<?php
// Admin/Organizer: Manage events (CRUD)
session_start();
$pageTitle = 'Manage Events';
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

// Handle delete with prepared statement
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteStmt = $connection->prepare("DELETE FROM events WHERE id = ?");
    $deleteStmt->bind_param('i', $id);
    $deleteStmt->execute();
    $deleteStmt->close();
}

// Fetch events with prepared statement
if ($role === 'organizer') {
    $stmt = $connection->prepare("SELECT e.*, c.name AS category_name FROM events e LEFT JOIN event_categories c ON e.category = c.name WHERE organizer_id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
} else {
    $stmt = $connection->prepare("SELECT e.*, c.name AS category_name FROM events e LEFT JOIN event_categories c ON e.category = c.name");
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
?>

    <div class="container" style="max-width: 1200px; margin: 30px auto; padding: 0 20px; animation: fadeIn 0.8s ease-out;">
        <h1 class="page-title" style="text-align: center; margin-bottom: 30px; color: #FFD700; text-shadow: 0 0 10px rgba(255, 215, 0, 0.3);">Manage Events</h1>
        
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="add_event.php" class="button-exploreevents" style="display: inline-block; padding: 10px 20px; font-size: 0.9em;">
                <span style="margin-right: 8px;">➕</span> Add New Event
            </a>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="events-table" style="background: #1e1e1e; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); border: 1px solid #333; margin-bottom: 30px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: rgba(0, 0, 0, 0.2);">
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #333; color: #FFD700;">ID</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #333; color: #FFD700;">Title</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 1px solid #333; color: #FFD700;">Category</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #333; color: #FFD700;">Seats</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #333; color: #FFD700;">Status</th>
                            <th style="padding: 15px; text-align: center; border-bottom: 1px solid #333; color: #FFD700;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #333; transition: background-color 0.3s;" onmouseover="this.style.backgroundColor='#272727'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 15px; color: #ddd;"><?= $row['id'] ?></td>
                            <td style="padding: 15px; color: #ddd;"><?= htmlspecialchars($row['title']) ?></td>
                            <td style="padding: 15px; color: #ddd;"><?= htmlspecialchars($row['category']) ?></td>
                            <td style="padding: 15px; text-align: center;">
                                <?php if ($row['seats'] < 10): ?>
                                    <span style="color: #ff6b6b; font-weight: bold;"><?= $row['seats'] ?></span>
                                <?php else: ?>
                                    <span style="color: #69db7c; font-weight: bold;"><?= $row['seats'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <?php if ($row['status'] == 'approved'): ?>
                                    <span style="color: #69db7c; background-color: rgba(105, 219, 124, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Approved</span>
                                <?php elseif ($row['status'] == 'pending'): ?>
                                    <span style="color: #ffd43b; background-color: rgba(255, 212, 59, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Pending</span>
                                <?php else: ?>
                                    <span style="color: #ff6b6b; background-color: rgba(255, 107, 107, 0.1); padding: 5px 10px; border-radius: 4px; font-size: 0.9em;">Cancelled</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px; text-align: center;">
                                <a href="edit_event.php?id=<?= $row['id'] ?>" style="color: #74c0fc; text-decoration: none; margin-right: 15px; display: inline-block; padding: 5px 15px; border: 1px solid #74c0fc; border-radius: 4px; transition: all 0.3s;">Edit</a>
                                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')" style="color: #ff6b6b; text-decoration: none; display: inline-block; padding: 5px 15px; border: 1px solid #ff6b6b; border-radius: 4px; transition: all 0.3s;">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-events" style="text-align: center; padding: 50px 0;">
                <div style="font-size: 4em; color: #333; margin-bottom: 20px;">📅</div>
                <h2 style="color: #FFD700; margin-bottom: 15px;">No Events Found</h2>
                <p style="color: #ddd; max-width: 600px; margin: 0 auto 30px;">You haven't created any events yet. Start by adding your first event!</p>
                <a href="add_event.php" class="button-exploreevents" style="display: inline-block;">Add Your First Event</a>
            </div>
        <?php endif; ?>
    </div>

<?php include '../includes/footer.php'; ?>

<?php include '../includes/footer.php'; ?>
