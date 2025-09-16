<?php
// Admin: Manage users (CRUD) with admin layout
session_start();
$pageTitle = 'Manage Users';
include '../includes/db_connect.php';

// Security check - only admin users can access
if (!isset($_SESSION['role_id']) || intval($_SESSION['role_id']) !== 1) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle delete with prepared statement for security
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $connection->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch users with role name using prepared statement
$users = $connection->query('SELECT u.id, u.username, u.email, r.role FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id');

// Include admin layout
include '../includes/admin_layout.php';
?>

<!-- Admin content -->
<div class="admin-card">
    <h2>Manage Users</h2>
    <div style="margin-bottom: 20px; text-align: right;">
        <a href="../users/add_user.php" class="admin-btn admin-btn-small">
            <span style="margin-right: 8px;">➕</span> Add New User
        </a>
    </div>
    
    <?php if ($users && $users->num_rows > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php if ($user['role'] == 'admin'): ?>
                                <span style="color: #ff6b6b;"><?= ucfirst(htmlspecialchars($user['role'])) ?></span>
                            <?php elseif ($user['role'] == 'organizer'): ?>
                                <span style="color: #ffd43b;"><?= ucfirst(htmlspecialchars($user['role'])) ?></span>
                            <?php else: ?>
                                <span style="color: #69db7c;"><?= ucfirst(htmlspecialchars($user['role'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>N/A</td>
                        <td style="white-space: nowrap;">
                            <a href="../users/edit_user.php?id=<?= $user['id'] ?>" class="admin-btn admin-btn-small" style="margin-right: 5px;">Edit</a>
                            <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')" class="admin-btn admin-btn-small admin-btn-danger">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>
</div>

<?php include '../includes/admin_footer.php'; ?>