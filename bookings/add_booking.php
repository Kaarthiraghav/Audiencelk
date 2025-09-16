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
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <select name="event_id">
            <?php while ($row = $events->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"> <?= htmlspecialchars($row['title']) ?> (Seats: <?= $row['total_seats'] ?>)</option>
            <?php endwhile; ?>
        </select><br>
        <input type="submit" value="Book">
    </form>
    <a href="manage_bookings.php">Back</a>
</body>
</html>
