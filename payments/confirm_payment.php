<?php
// Dummy payment confirmation page
session_start();
$csrf_token = null;
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
$pageTitle = 'Confirm Payment';
include '../includes/header.php';
include '../includes/db_connect.php';
// Validate booking_id
$booking_id = intval($_GET['booking_id'] ?? 0);
if (!$booking_id) {
    die('Invalid booking.');
}
// Fetch payment info using prepared statement
$stmt = $connection->prepare("SELECT * FROM payments WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$payment) {
    die('No payment found.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $action = $_POST['action'] ?? '';
    if ($action === 'success') {
        $stmt = $connection->prepare("UPDATE payments SET status='success' WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        header('Location: ../bookings/manage_bookings.php');
        exit;
    } elseif ($action === 'cancel') {
        $stmt = $connection->prepare("UPDATE payments SET status='canceled' WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        header('Location: ../bookings/manage_bookings.php');
        exit;
    }
}
?>
    <div class="form-container">
        <h2>Confirm Payment</h2>
        <p>Amount: $<?= htmlspecialchars($payment['amount']) ?></p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <button type="submit" name="action" value="success">Pay Now</button>
            <button type="submit" name="action" value="cancel">Cancel</button>
        </form>
    </div>

<?php include '../includes/footer.php'; ?>