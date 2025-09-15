<?php
// Admin/Organizer: Manage events (CRUD)
session_start();
$pageTitle = 'Manage Events';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role'])) {
    header('Location: ../auth/login.php');
    exit;
}
$role = $_SESSION['role'];
// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $connection->query("DELETE FROM events WHERE id = $id");
}
// Fetch events
$where = ($role === 'organizer') ? "WHERE organizer_id = {$_SESSION['user_id']}" : '';
$result = $connection->query("SELECT * FROM events $where");
?>

    <h2>Manage Events</h2>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Title</th><th>Category</th><th>Seats</th><th>Status</th><th>Actions</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= $row['seats'] ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td>
                <a href="edit_event.php?id=<?= $row['id'] ?>">Edit</a> |
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete event?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="add_event.php">Add Event</a>

<?php include '../includes/footer.php'; ?>
