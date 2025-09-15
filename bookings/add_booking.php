<?php
// Add booking (Student/Guest)
session_start();
include '../includes/db_connect.php';
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 3) {
    header('Location: ../auth/login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    // Check seat availability
    $result = $connection->query("SELECT seats FROM events WHERE id = $event_id");
    $event = $result->fetch_assoc();
    if ($event && $event['seats'] > 0) {
        $booking_number = substr(str_shuffle(str_repeat('0123456789', 9)), 0, 9);
        $connection->query("INSERT INTO bookings (event_id, user_id, booking_number) VALUES ($event_id, $user_id, '$booking_number')");
        $booking_id = $connection->insert_id;
        $connection->query("UPDATE events SET seats = seats - 1 WHERE id = $event_id");
        // Dummy payment integration: if event is paid, create payment record
    $eventPriceResult = $connection->query("SELECT price FROM events WHERE id = $event_id");
        $eventPrice = $eventPriceResult->fetch_assoc();
        if ($eventPrice && $eventPrice['price'] > 0) {
            $amount = $eventPrice['price'];
            $connection->query("INSERT INTO payments (user_id, booking_id, amount, status) VALUES ($user_id, $booking_id, $amount, 'pending')");
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
$events = $connection->query("SELECT * FROM events WHERE status='approved' AND seats > 0");
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
