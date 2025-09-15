<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$pageTitle = 'Booking';
include 'includes/header.php';
include 'includes/db_connect.php';

// Get selected event IDs from session (set from Events page)
$selected_events = isset($_SESSION['selected_events']) ? $_SESSION['selected_events'] : [];

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id && !empty($selected_events)) {
    $success = true;
    foreach ($selected_events as $event_id) {
        $seats = isset($_POST['seats'][$event_id]) ? intval($_POST['seats'][$event_id]) : 1;
        $booking_date = date('Y-m-d H:i:s');
        // Generate booking number: AUDxxxxxx (auto-increment id padded to 6 digits)
        // First, insert without booking_number to get the id
        $sql = "INSERT INTO bookings (user_id, event_id, booking_date, seats, booking_number) VALUES (?, ?, ?, ?, '')";
        $stmt = mysqli_prepare($connection, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'iisi', $user_id, $event_id, $booking_date, $seats);
            if (mysqli_stmt_execute($stmt)) {
                $insert_id = mysqli_insert_id($connection);
                $booking_number = 'AUD' . str_pad($insert_id, 6, '0', STR_PAD_LEFT);
                // Update booking_number
                $update_sql = "UPDATE bookings SET booking_number=? WHERE id=?";
                $update_stmt = mysqli_prepare($connection, $update_sql);
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, 'si', $booking_number, $insert_id);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
            } else {
                $success = false;
            }
            mysqli_stmt_close($stmt);
        } else {
            $success = false;
        }
    }
    if ($success) {
        echo '<div style="text-align:center;color:green;">Booking successful!</div>';
        unset($_SESSION['selected_events']);
    } else {
        echo '<div style="text-align:center;color:red;">Booking failed. Please try again.</div>';
    }
}

echo '<br><h1 style="text-align: center; color: #FFD700;">Booking Page</h1>';

if (!$user_id) {
    echo '<div style="text-align:center;color:red;">Please log in to book events.</div><br>';
    echo '<div style="text-align: center;"><a href="login.php"><button class="button-backtohome">Go to Login</button></a></div>';
} elseif (empty($selected_events)) {
    echo '<div style="text-align:center;color:orange;">No events selected for booking.</div>';
} else {
    // Fetch event details
    $ids = implode(',', array_map('intval', $selected_events));
    $result = mysqli_query($connection, "SELECT id, title, venue, event_date, price FROM events WHERE id IN ($ids)");
    if ($result && mysqli_num_rows($result) > 0) {
        echo '<form method="post" style="text-align:center;">';
        echo '<table style="margin:0 auto; border-collapse:collapse;">';
        echo '<tr style="background:#FFD700;color:#222;"><th>Event</th><th>Venue</th><th>Date</th><th>Price</th><th>Seats</th></tr>';
        while ($row = mysqli_fetch_assoc($result)) {
            $event_id = $row['id'];
            echo '<tr style="background:#222;color:#FFD700;">';
            echo '<td>' . htmlspecialchars($row['title']) . '</td>';
            echo '<td>' . htmlspecialchars($row['venue']) . '</td>';
            echo '<td>' . htmlspecialchars(date('Y-m-d H:i', strtotime($row['event_date']))) . '</td>';
            echo '<td>Rs. ' . htmlspecialchars($row['price']) . '</td>';
            echo '<td><input type="number" name="seats[' . $event_id . ']" value="1" min="1" max="10" style="width:60px;"></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<br><button type="submit" class="button-exploreevents">Confirm Booking</button>';
        echo '</form>';
    } else {
        echo '<div style="text-align:center;color:red;">Could not fetch event details.</div>';
    }
}

echo '<br><div style="text-align: center;"><a href="index.php"><button class="button-backtohome">Back to Home</button></a></div>';
include 'includes/footer.php';
?>