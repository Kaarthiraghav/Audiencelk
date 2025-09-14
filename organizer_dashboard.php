<?php
// Organizer Dashboard: Add/view events, view bookings for own events
session_start();
$pageTitle = 'Organizer Dashboard';
include '../includes/header.php';
include '../includes/db_connect.php';
if (!isset($_SESSION['role']) || $_SESSION['role_id'] !== 2) {
    header('Location: ../auth/login.php');
    exit;
}
$organizer_id = $_SESSION['user_id'];
// Fetch events by organizer
$events = $connection->query("SELECT * FROM events WHERE organizer_id = $organizer_id");
?>
    <h2>Your Events</h2>
    <a href="../events/add_event.php">Add Event</a>
    <table border="1" cellpadding="5">
        <tr><th>Title</th><th>Category</th><th>Seats</th><th>Status</th><th>Bookings</th></tr>
        <?php while ($event = $events->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($event['title']) ?></td>
            <td><?= htmlspecialchars($event['category']) ?></td>
            <td><?= $event['seats'] ?></td>
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
    <a href="../auth/logout.php">Logout</a>
<?php include '../includes/footer.php'; ?>
