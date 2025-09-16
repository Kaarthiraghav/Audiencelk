<?php
// Admin: Manage event categories with admin layout
session_start();
$pageTitle = 'Manage Categories';
include '../includes/db_connect.php';

// Security check - only admin users can access
if (!isset($_SESSION['role_id']) || intval($_SESSION['role_id']) !== 1) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle form submission for new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
    $stmt = $connection->prepare("INSERT INTO event_categories (category) VALUES (?)");
    $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $connection->prepare("DELETE FROM event_categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

// Fetch categories
$categories = $connection->query("SELECT * FROM event_categories ORDER BY category");

// Include admin layout
include '../includes/admin_layout.php';
?>

<!-- Admin content -->
<div class="admin-row" style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
    <div class="admin-card">
        <h2>Add Category</h2>
        <form method="post" action="">
            <div class="admin-form-group">
                <label for="name">Category Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <button type="submit" class="admin-btn">Add Category</button>
        </form>
    </div>
    
    <div class="admin-card">
        <h2>Event Categories</h2>
        <?php if ($categories && $categories->num_rows > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($category = $categories->fetch_assoc()): ?>
                        <tr>
                            <td><?= $category['id'] ?></td>
                            <td><?= htmlspecialchars($category['category']) ?></td>
                            <td>
                                <a href="?delete=<?= $category['id'] ?>" onclick="return confirm('Are you sure you want to delete this category? This may affect events using this category.')" class="admin-btn admin-btn-small admin-btn-danger">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No categories found.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>