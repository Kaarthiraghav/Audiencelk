<?php
// Add booking (Student/Guest)
$csrf_token = null;
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
include '../includes/db_connect.php';
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 3) {
    header('Location: ../auth/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $event_id = intval($_POST['event_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    // Check seat availability
    $stmt = $connection->prepare("SELECT total_seats FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    if ($event && $event['total_seats'] > 0) {
        $booking_number = substr(str_shuffle(str_repeat('0123456789', 9)), 0, 9);
        $stmt = $connection->prepare("INSERT INTO bookings (event_id, user_id, seats, booking_number) VALUES (?, ?, 1, ?)");
        $stmt->bind_param("iis", $event_id, $user_id, $booking_number);
        $stmt->execute();
        $booking_id = $connection->insert_id;
        $stmt->close();
        $stmt = $connection->prepare("UPDATE events SET total_seats = total_seats - 1 WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->close();
        // Dummy payment integration: if event is paid, create payment record
        $stmt = $connection->prepare("SELECT price FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $eventPriceResult = $stmt->get_result();
        $eventPrice = $eventPriceResult->fetch_assoc();
        $stmt->close();
        if ($eventPrice && $eventPrice['price'] > 0) {
            $amount = $eventPrice['price'];
            $stmt = $connection->prepare("INSERT INTO payments (booking_id, amount, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("id", $booking_id, $amount);
            $stmt->execute();
            $stmt->close();
            // Redirect to dummy payment page (to be implemented)
            header('Location: ../payments/confirm_payment.php?booking_id=' . $booking_id);
            exit;
        }
        header('Location: ../bookings/manage_bookings.php');
        exit;
    } else {
        $error = 'No seats available.';
    }
}
// Fetch events
$stmt = $connection->prepare("SELECT * FROM events WHERE status=? AND total_seats > 0");
$status = 'approved';
$stmt->bind_param("s", $status);
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();
?>
<?php
$pageTitle = 'Book Event';
include '../includes/header.php';
?>

<div class="HomeCards1">
    <div class="card">
        <form method="post" class="beautiful-form">
            <h2 style="margin-bottom: 18px; font-size: 2em; color: #FFD700; letter-spacing: 1px; text-align:center;">Book Event</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert" style="background-color: #ff4d4d; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="event_id">Select Event</label>
                <select id="event_id" name="event_id" required>
                    <?php while ($row = $events->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?> (Seats: <?= $row['seats'] ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" class="button-exploreevents" style="width:100%;margin-top:18px;">Book Now</button>
            <a href="manage_bookings.php" class="button-backtohome" style="margin-top:18px;width:100%;text-align:center;">Back to My Bookings</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
