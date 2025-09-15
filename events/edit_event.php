<?php
// Edit event (Organizer/Admin)
session_start();
include '../includes/db_connect.php';
if (!isset($_SESSION['role_id']) || ($_SESSION['role_id'] !== 1 && $_SESSION['role_id'] !== 2)) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle quick approval/rejection from admin dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && isset($_POST['action'])) {
    $event_id = intval($_POST['event_id']);
    $action = $_POST['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $connection->prepare("UPDATE events SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $event_id);
        $stmt->execute();
        $stmt->close();
        header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '../dashboards/admin_dashboard.php');
        exit;
    }
}

// Regular event editing
$id = intval($_GET['id'] ?? 0);
if ($id) {
    $stmt = $connection->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // If it's not a quick action (approve/reject)
        if (!isset($_POST['action'])) {
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
            
            $updateStmt = $connection->prepare("UPDATE events SET title=?, category=?, seats=?, status=?, image=? WHERE id=?");
            $updateStmt->bind_param('ssissi', $title, $category, $seats, $status, $image, $id);
            $updateStmt->execute();
            $updateStmt->close();
            header('Location: manage_events.php');
            exit;
        }
    }
}
?>
<?php
$pageTitle = 'Edit Event';
include '../includes/header.php';
// Fetch categories for dropdown
$categories_result = $connection->query('SELECT name FROM event_categories');
$categories = [];
while ($cat = $categories_result->fetch_assoc()) {
    $categories[] = $cat['name'];
}
?>
    <div class="HomeCards1">
        <div class="card">
            <?php if ($event): ?>
            <form method="post" enctype="multipart/form-data" class="beautiful-form">
                <h2 style="margin-bottom: 18px; font-size: 2em; color: #FFD700; letter-spacing: 1px; text-align:center;">Edit Event</h2>
                
                <div class="form-group">
                    <label for="title">Event Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($event['title']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= $event['category']==$cat?'selected':'' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="seats">Available Seats</label>
                    <input type="number" id="seats" name="seats" value="<?= $event['seats'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="pending" <?= $event['status']=='pending'?'selected':'' ?>>Pending</option>
                        <option value="approved" <?= $event['status']=='approved'?'selected':'' ?>>Approved</option>
                        <option value="cancelled" <?= $event['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">Event Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                
                <?php if ($event['image']): ?>
                <div class="form-group">
                    <label>Current Image</label>
                    <div class="current-image" style="margin-top: 10px;">
                        <img src="../assets/<?= htmlspecialchars($event['image']) ?>" width="150" style="border-radius: 5px; border: 2px solid #FFD700;">
                    </div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="button-exploreevents" style="width:100%;margin-top:18px;">Update Event</button>
                <a href="manage_events.php" class="button-backtohome" style="margin-top:18px;width:100%;text-align:center;">Back to Events</a>
            </form>
            <?php else: ?>
            <div class="alert" style="background-color: #ff4d4d; color: white; padding: 15px; border-radius: 5px; text-align: center;">
                <p>Event not found</p>
                <a href="manage_events.php" class="button-backtohome" style="margin-top:18px;display:inline-block;padding:8px 16px;">Back to Events</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>
