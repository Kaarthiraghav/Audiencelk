<?php
// Admin: Manage users (CRUD)
$csrf_token = null;
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
$pageTitle = 'Manage Users';
include '../includes/db_connect.php';
include '../includes/header.php';
if (!isset($_SESSION['role']) || $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/login.php');
    exit;
}
// Handle delete
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    $id = intval($_GET['delete']);
    $stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
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
                <a href="?delete=<?= $row['id'] ?>&csrf_token=<?= $csrf_token ?>" onclick="return confirm('Delete user?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <a href="add_user.php">Add User</a>
<?php include '../includes/footer.php'; ?>