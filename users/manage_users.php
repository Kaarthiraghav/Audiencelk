<?php
// Admin: Manage users (CRUD)
session_start();
$pageTitle = 'Manage Users';
include '../includes/db_connect.php';
include '../includes/header.php';
if (!isset($_SESSION['role']) || $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/login.php');
    exit;
}
// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $connection->query("DELETE FROM users WHERE id = $id");
}
// Fetch users with role name
$result = $connection->query('SELECT u.id, u.username, u.email, r.role FROM users u LEFT JOIN roles r ON u.role_id = r.id');
?>

    <h2>Manage Users</h2>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['role']) ?></td>
            <td>
                <a href="edit_user.php?id=<?= $row['id'] ?>">Edit</a> |
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete user?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="add_user.php">Add User</a>
<?php include '../includes/footer.php'; ?>