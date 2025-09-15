<?php
// Add event category (Admin)
session_start();
$pageTitle = 'Add Event Category';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 1) {
    header('Location: ../auth/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $stmt = $connection->prepare('INSERT INTO event_categories (name) VALUES (?)');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        header('Location: manage_event_categories.php');
        exit;
    }
}
?>

<div class="HomeCards1">
    <div class="card">
        <form method="post" class="beautiful-form">
            <h2 style="margin-bottom: 18px; font-size: 2em; color: #FFD700; letter-spacing: 1px; text-align:center;">Add Event Category</h2>
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" id="name" name="name" placeholder="Category Name" required>
            </div>
            <button type="submit" class="button-exploreevents" style="width:100%;margin-top:18px;">Add Category</button>
            <a href="<?php echo BASE_URL . 'index.php'; ?>" class="button-backtohome" style="margin-top:18px;width:100%;text-align:center;">Back</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>