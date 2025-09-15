<?php
// Add booking (Student/Guest)
session_start();
include '../includes/db_connect.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
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
    $connection->query("INSERT INTO bookings (event_id, user_id, status) VALUES ($event_id, $user_id, 'pending')");
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Event</title>
    <link rel="stylesheet" href="../assets/main.css">
</head>
<body>
    <h2>Book Event</h2>
    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <select name="event_id">
            <?php while ($row = $events->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"> <?= htmlspecialchars($row['title']) ?> (Seats: <?= $row['seats'] ?>)</option>
            <?php endwhile; ?>
        </select><br>
        <input type="submit" value="Book">
    </form>
    <a href="manage_bookings.php">Back</a>
</body>
</html>
