<?php
// Add event (Organizer/Admin)
session_start();
include '../includes/db_connect.php';
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    header('Location: ../auth/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $seats = intval($_POST['seats'] ?? 0);
    $status = ($_SESSION['role'] === 'organizer') ? 'pending' : 'approved';
    $organizer_id = $_SESSION['user_id'];
    // Image upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $target = '../assets/' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $image = basename($_FILES['image']['name']);
        }
    }
    if ($title && $category && $seats) {
    $stmt = $connection->prepare('INSERT INTO events (title, category, seats, status, organizer_id, image) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssisis', $title, $category, $seats, $status, $organizer_id, $image);
        $stmt->execute();
        header('Location: manage_events.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Event</title>
    <link rel="stylesheet" href="../assets/main.css">
</head>
<body>
    <h2>Add Event</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Event Title" required><br>
        <select name="category">
            <option value="cultural">Cultural</option>
            <option value="academic">Academic</option>
            <option value="sports">Sports</option>
        </select><br>
        <input type="number" name="seats" placeholder="Seats" required><br>
        <input type="file" name="image" accept="image/*"><br>
        <input type="submit" value="Add Event">
    </form>
    <a href="manage_events.php">Back</a>
</body>
</html>
