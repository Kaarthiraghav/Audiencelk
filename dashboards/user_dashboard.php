<?php
// User Dashboard: Show bookings, allow cancel, profile management
session_start();
$pageTitle = 'User Dashboard';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role']) || $_SESSION['role_id'] !== 3) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email) {
        $set = "email='$email'";
        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $set .= ", password='$hash'";
        }
    $connection->query("UPDATE users SET $set WHERE id=$user_id");
    }
}
// Fetch bookings
$bookings = $connection->query("SELECT b.*, e.title FROM bookings b JOIN events e ON b.event_id = e.id WHERE b.user_id = $user_id");
$user = $connection->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>
    <h2>Welcome, <?= htmlspecialchars($user['username']) ?></h2>
    <h3>Your Bookings</h3>
    <table border="1" cellpadding="5">
        <tr><th>Event</th><th>Status</th><th>Actions</th></tr>
        <?php while ($row = $bookings->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td>
                <?php if ($row['status'] !== 'canceled'): ?>
                <a href="../bookings/manage_bookings.php?cancel=<?= $row['id'] ?>&event_id=<?= $row['event_id'] ?>">Cancel</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <h3>Edit Profile</h3>
    <form method="post">
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>
        <input type="password" name="password" placeholder="New Password"><br>
        <input type="submit" value="Update Profile">
    </form>
<?php include '../includes/footer.php'; ?>