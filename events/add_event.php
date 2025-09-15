<?php
// Add event (Organizer/Admin)
session_start();
$pageTitle = 'Add Event';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
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

<div class="HomeCards1">
    <div class="card">
        <form method="post" enctype="multipart/form-data" class="beautiful-form">
            <h2 style="margin-bottom: 18px; font-size: 2em; color: #FFD700; letter-spacing: 1px; text-align:center;">Add Event</h2>
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" placeholder="Event Title" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="cultural">Cultural</option>
                    <option value="academic">Academic</option>
                    <option value="sports">Sports</option>
                </select>
            </div>
            <div class="form-group">
                <label for="seats">Seats</label>
                <input type="number" id="seats" name="seats" placeholder="Seats" required>
            </div>
            <div class="form-group">
                <label for="image">Event Image</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            <button type="submit" class="button-exploreevents" style="width:100%;margin-top:18px;">Add Event</button>
            <a href="manage_events.php" class="button-backtohome" style="margin-top:18px;width:100%;text-align:center;">Back</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>