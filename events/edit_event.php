<?php
// Edit event (Organizer/Admin)
session_start();
include '../includes/db_connect.php';
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    header('Location: ../auth/login.php');
    exit;
}
$id = intval($_GET['id'] ?? 0);
if ($id) {
    $result = $connection->query("SELECT * FROM events WHERE id = $id");
    $event = $result->fetch_assoc();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title'] ?? '');
        $category = $_POST['category'] ?? '';
        $seats = intval($_POST['seats'] ?? 0);
        $status = $_POST['status'] ?? $event['status'];
        $image = $event['image'];
        if (!empty($_FILES['image']['name'])) {
            $target = '../assets/' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image = basename($_FILES['image']['name']);
            }
        }
    $connection->query("UPDATE events SET title='$title', category='$category', seats=$seats, status='$status', image='$image' WHERE id=$id");
        header('Location: manage_events.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Event</title>
    <link rel="stylesheet" href="../assets/main.css">
</head>
<body>
    <h2>Edit Event</h2>
    <?php if ($event): ?>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" required><br>
        <select name="category">
            <option value="cultural" <?= $event['category']=='cultural'?'selected':'' ?>>Cultural</option>
            <option value="academic" <?= $event['category']=='academic'?'selected':'' ?>>Academic</option>
            <option value="sports" <?= $event['category']=='sports'?'selected':'' ?>>Sports</option>
        </select><br>
        <input type="number" name="seats" value="<?= $event['seats'] ?>" required><br>
        <select name="status">
            <option value="pending" <?= $event['status']=='pending'?'selected':'' ?>>Pending</option>
            <option value="approved" <?= $event['status']=='approved'?'selected':'' ?>>Approved</option>
        </select><br>
        <input type="file" name="image" accept="image/*"><br>
        <?php if ($event['image']): ?>
            <img src="../assets/<?= htmlspecialchars($event['image']) ?>" width="100"><br>
        <?php endif; ?>
        <input type="submit" value="Update Event">
    </form>
    <?php endif; ?>
    <a href="manage_events.php">Back</a>
</body>
</html>
