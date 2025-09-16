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
    $seats = intval($_POST['seats'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $status = ($_SESSION['role'] === 'organizer') ? 'pending' : 'approved';
    $organizer_id = $_SESSION['user_id'];
    $image = '';
    
    // Handle image upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "../assets/";
        $image = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (limit to 5MB)
            if ($_FILES["image"]["size"] <= 5000000) {
                // Allow certain file formats
                if (in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        // File uploaded successfully
                    } else {
                        // Handle upload error
                        $image = '';
                    }
                }
            }
        }
    }
    
    if ($title && $category_id && $seats && $price) {
        // Get the category name from the selected category_id
        $cat_query = $connection->prepare("SELECT name FROM event_categories WHERE id = ?");
        $cat_query->bind_param("i", $category_id);
        $cat_query->execute();
        $cat_result = $cat_query->get_result();
        $category_name = "";
        if ($cat_row = $cat_result->fetch_assoc()) {
            $category_name = $cat_row['name'];
        }
        
        // Now insert with the category name rather than ID (based on create_full_db.sql schema)
        $stmt = $connection->prepare('INSERT INTO events (title, category, seats, price, status, organizer_id, image) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssidsss', $title, $category_name, $seats, $price, $status, $organizer_id, $image);
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
                <label for="seats">Seats</label>
                <input type="number" id="seats" name="seats" placeholder="Seats" required>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" placeholder="Price" step="0.01" min="0" required>
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