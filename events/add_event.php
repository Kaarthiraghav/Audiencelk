<?php
// Add event (Organizer/Admin)
$csrf_token = null;
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
$pageTitle = 'Add Event';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch categories for dropdown (always fetch, not just on POST)
$categories = $connection->query('SELECT id, category FROM event_categories');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $title = trim($_POST['title'] ?? '');
    $category_id = intval($_POST['category'] ?? 0);
    $venue = trim($_POST['venue'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $seats = intval($_POST['seats'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $status = ($_SESSION['role'] === 'organizer') ? 'pending' : 'approved';
    $organizer_id = $_SESSION['user_id'];
    if ($title && $category_id && $venue && $event_date && $seats && $price) {
        $stmt = $connection->prepare('INSERT INTO events (title, category_id, venue, event_date, total_seats, price, status, organizer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('sisssdsi', $title, $category_id, $venue, $event_date, $seats, $price, $status, $organizer_id);
        $stmt->execute();
        header('Location: manage_events.php');
        exit;
    }
}
?>

<div class="HomeCards1">
    <div class="card">
        <form method="post" enctype="multipart/form-data" class="beautiful-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <h2 style="margin-bottom: 18px; font-size: 2em; color: #FFD700; letter-spacing: 1px; text-align:center;">Add Event</h2>
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" placeholder="Event Title" required>
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php while ($row = $categories->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['category']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="venue">Venue</label>
                <input type="text" id="venue" name="venue" placeholder="Venue" required>
            </div>
            <div class="form-group">
                <label for="event_date">Event Date</label>
                <input type="datetime-local" id="event_date" name="event_date" required>
            </div>
            <div class="form-group">
                <label for="seats">Seats</label>
                <input type="number" id="seats" name="seats" placeholder="Seats" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" placeholder="Price" step="0.01" min="0" required>
            </div>
            <button type="submit" class="button-exploreevents" style="width:100%;margin-top:18px;">Add Event</button>
            <a href="manage_events.php" class="button-backtohome" style="margin-top:18px;width:100%;text-align:center;">Back</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>