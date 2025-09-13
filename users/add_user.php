<?php
// Admin: Add user
session_start();
$pageTitle = "Add User";
include '../includes/db_connect.php';
include '../includes/header.php';
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = intval($_POST['role_id'] ?? 0);
    if ($username && $email && $password && $role_id) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $connection->prepare('INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $username, $email, $hash, $role_id);
        $stmt->execute();
        header('Location: manage_users.php');
        exit;
    }
}
?>

    <h2>Add User</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <select name="role_id" required>
            <option value="">Select Role</option>
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <input type="submit" value="Add User">
    </form>
    <a href="manage_users.php">Back</a>

<?php include '../includes/footer.php'; ?>