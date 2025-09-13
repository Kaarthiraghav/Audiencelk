<?php
// Admin: Edit user
session_start();
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
    $result = $connection->query("SELECT * FROM users WHERE id = $id");
    $user = $result->fetch_assoc();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role_id = intval($_POST['role_id'] ?? 0);
    $connection->query("UPDATE users SET username='$username', email='$email', role_id=$role_id WHERE id=$id");
        header('Location: manage_users.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/main.css">
</head>
<body>
    <h2>Edit User</h2>
    <?php if ($user): ?>
    <form method="post">
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
</body>
</html>
