<?php
// Admin/Organizer: Manage events (CRUD)
$csrf_token = null;
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
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
// Handle delete
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    $id = intval($_GET['delete']);
    $stmt = $connection->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}
// Fetch events
$result = null;
if ($role === 'organizer') {
    $organizer_id = $_SESSION['user_id'];
    $stmt = $connection->prepare("SELECT e.*, c.category FROM events e JOIN event_categories c ON e.category_id = c.id WHERE e.organizer_id = ?");
    $stmt->bind_param('i', $organizer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $connection->query("SELECT e.*, c.category FROM events e JOIN event_categories c ON e.category_id = c.id");
}
?>

    <h2>Manage Events</h2>
    <table border="1" cellpadding="5">
    <tr><th>ID</th><th>Title</th><th>Category</th><th>Total Seats</th><th>Status</th><th>Actions</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= $row['total_seats'] ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td>
                <a href="edit_event.php?id=<?= $row['id'] ?>">Edit</a> |
                <a href="?delete=<?= $row['id'] ?>&csrf_token=<?= $csrf_token ?>" onclick="return confirm('Delete event?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="add_event.php">Add Event</a>

<?php include '../includes/footer.php'; ?>
