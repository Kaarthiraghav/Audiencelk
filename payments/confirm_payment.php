<?php
// Dummy payment confirmation page
session_start();
$pageTitle = 'Confirm Payment';
include '../includes/header.php';
include '../includes/db_connect.php';
$booking_id = intval($_GET['booking_id'] ?? 0);
if (!$booking_id) {
    die('Invalid booking.');
}
// Fetch payment info
$payment = $connection->query("SELECT * FROM payments WHERE booking_id = $booking_id")->fetch_assoc();
if (!$payment) {
    die('No payment found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'success') {
        $connection->query("UPDATE payments SET status='success' WHERE booking_id = $booking_id");
        header('Location: ../bookings/manage_bookings.php');
        exit;
    } elseif ($action === 'cancel') {
        $connection->query("UPDATE payments SET status='canceled' WHERE booking_id = $booking_id");
        header('Location: ../bookings/manage_bookings.php');
        exit;
    }
}
?>
    <div class="form-container">
        <h2>Confirm Payment</h2>
        <p>Amount: $<?= htmlspecialchars($payment['amount']) ?></p>
        <form method="post">
            <button type="submit" name="action" value="success">Pay Now</button>
            <button type="submit" name="action" value="cancel">Cancel</button>
        </form>
    </div>

<?php include '../includes/footer.php'; ?>