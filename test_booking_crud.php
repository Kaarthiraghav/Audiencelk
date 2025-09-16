<?php
// Test adding a booking directly
session_start();
$_SESSION['user_id'] = 1; // Admin user
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['csrf_token'] = 'test_token';

require_once 'includes/db_connect.php';

echo "Testing Booking CRUD Operations:\n\n";

// Test 1: Add a booking
echo "1. Testing add_booking:\n";

// Simulate POST data for adding a booking
$_POST = [
    'csrf_token' => 'test_token',
    'action' => 'add_booking',
    'user_id' => 10, // admin user
    'event_id' => 2, // test event
    'seats' => 3,
    'status' => 'confirmed'
];

// Include the add_booking handler logic
$user_id = intval($_POST['user_id'] ?? 0);
$event_id = intval($_POST['event_id'] ?? 0);
$seats = intval($_POST['seats'] ?? 0);
$status = $_POST['status'] ?? 'confirmed';

if ($user_id === 0 || $event_id === 0 || $seats <= 0) {
    echo "Error: All fields are required and seats must be greater than 0.\n";
} else {
    // Generate booking number
    $booking_number = 'BK' . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);

    // Check if event has enough available seats
    $checkStmt = $connection->prepare("SELECT total_seats FROM events WHERE id = ?");
    $checkStmt->bind_param('i', $event_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $event = $result->fetch_assoc();

    if ($event) {
        // Get current bookings for this event
        $bookedStmt = $connection->prepare("SELECT SUM(seats) as booked_seats FROM bookings WHERE event_id = ? AND status != 'cancelled'");
        $bookedStmt->bind_param('i', $event_id);
        $bookedStmt->execute();
        $bookedResult = $bookedStmt->get_result();
        $booked = $bookedResult->fetch_assoc();
        $bookedSeats = $booked['booked_seats'] ?? 0;

        echo "Event total seats: {$event['total_seats']}\n";
        echo "Currently booked seats: {$bookedSeats}\n";
        echo "Requesting seats: {$seats}\n";

        if (($bookedSeats + $seats) <= $event['total_seats']) {
            $stmt = $connection->prepare("INSERT INTO bookings (user_id, event_id, booking_number, seats, status, booking_date) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iisis", $user_id, $event_id, $booking_number, $seats, $status);

            if ($stmt->execute()) {
                echo "Success: Booking added successfully! Booking Number: " . $booking_number . "\n";
            } else {
                echo "Error adding booking: " . $connection->error . "\n";
            }
            $stmt->close();
        } else {
            echo "Error: Not enough available seats. Available: " . ($event['total_seats'] - $bookedSeats) . ", Requested: " . $seats . "\n";
        }
        $bookedStmt->close();
    } else {
        echo "Error: Event not found.\n";
    }
    $checkStmt->close();
}

echo "\n2. Testing all_bookings API:\n";
try {
    $result = $connection->query("SELECT b.*, u.username, u.email, e.title as event_title, e.price as event_price
                                FROM bookings b 
                                LEFT JOIN users u ON b.user_id = u.id 
                                LEFT JOIN events e ON b.event_id = e.id
                                ORDER BY b.booking_date DESC");
    if (!$result) {
        echo "Database error: " . $connection->error . "\n";
    } else {
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $row['total_amount'] = $row['seats'] * $row['event_price'];
            $bookings[] = $row;
        }
        echo "Found " . count($bookings) . " bookings:\n";
        foreach ($bookings as $booking) {
            echo "  - ID: {$booking['id']}, Number: {$booking['booking_number']}, Event: {$booking['event_title']}, Seats: {$booking['seats']}, Status: {$booking['status']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nBooking CRUD test completed.\n";
?>