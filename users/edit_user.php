<?php
// Admin: Edit user
$csrf_token = null;
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
$pageTitle = "Edit User";
include '../includes/header.php';
include '../includes/db_connect.php';
// Fetch roles for dropdown
$roles = [];
$roleResult = $connection->query('SELECT id, role FROM roles');
while ($row = $roleResult->fetch_assoc()) {
    $roles[] = $row;
}
if (!isset($_SESSION['role']) || $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/login.php');
    exit;
}
$id = intval($_GET['id'] ?? 0);
if ($id) {
    $stmt = $connection->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
        $stmt = $connection->prepare("UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?");
        $stmt->bind_param('ssii', $username, $email, $role_id, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: manage_users.php');
        exit;
    }
}
?>
    <h2>Edit User</h2>
    <?php if ($user): ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required><br>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>
        <select name="role_id" required>
            <option value="">Select Role</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role['id'] ?>" <?= $user['role_id']==$role['id']?'selected':'' ?>><?= htmlspecialchars($role['role']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <input type="submit" value="Update User">
    </form>
    <?php endif; ?>
    <a href="manage_users.php">Back</a>
<?php include '../includes/footer.php'; ?>