<?php
// Organizer Dashboard: Add/view events, view bookings for own events
session_start();
$pageTitle = 'Organizer Dashboard';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 2) {
    header('Location: ../auth/login.php');
    exit;
}
$organizer_id = $_SESSION['user_id'];
// Fetch events by organizer
$events = $connection->query("SELECT e.*, c.name AS category FROM events e JOIN event_categories c ON e.category = c.name WHERE e.organizer_id = $organizer_id");
?>
    <h2>Your Events</h2>
    <a href="<?php echo BASE_URL ?>events/add_event.php">Add Event</a>
    <table border="1" cellpadding="5">
        <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Venue</th>
            <th>Date & Time</th>
            <th>Seats</th>
            <th>Price</th>
            <th>Status</th>
            <th>Bookings</th>
        </tr>
        <?php while ($event = $events->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($event['title']) ?></td>
            <td><?= htmlspecialchars($event['category']) ?></td>
            <td><?= htmlspecialchars($event['venue']) ?></td>
            <td><?= date('d M Y, h:i A', strtotime($event['event_date'])) ?></td>
            <td><?= $event['total_seats'] ?></td>
            <td>₹<?= number_format($event['price'], 2) ?></td>
            <td><?= htmlspecialchars($event['status']) ?></td>
            <td>
                <a href="?bookings=<?= $event['id'] ?>">View Bookings</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>


    <?php
    // Show bookings for selected event
    if (isset($_GET['bookings'])) {
        $event_id = intval($_GET['bookings']);
        $bookings = $conn->query("SELECT b.*, u.name FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.event_id = $event_id");
        echo '<h3>Bookings for Event #' . $event_id . '</h3>';
        echo '<table border="1" cellpadding="5"><tr><th>User</th><th>Status</th></tr>';
        while ($row = $bookings->fetch_assoc()) {
            echo '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
        }
        echo '</table>';
    }
    ?>
<?php include '../includes/footer.php'; ?>
