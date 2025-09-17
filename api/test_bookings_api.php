<?php
// Test the bookings API endpoints
session_start();
$_SESSION['user_id'] = 1; // Admin user
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

require_once 'includes/db_connect.php';

echo "Testing Bookings API:\n\n";

// Test all_bookings
echo "1. Testing all_bookings endpoint:\n";
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
            // Calculate total amount
            $row['total_amount'] = $row['seats'] * $row['event_price'];
            $bookings[] = $row;
        }
        echo "Found " . count($bookings) . " bookings:\n";
        foreach ($bookings as $booking) {
            echo "  - {$booking['id']}: {$booking['booking_number']} - {$booking['event_title']} ({$booking['seats']} seats)\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing if users exist for booking form:\n";
try {
    $result = $connection->query("SELECT id, username, email FROM users ORDER BY username");
    if (!$result) {
        echo "Database error: " . $connection->error . "\n";
    } else {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo "Found " . count($users) . " users:\n";
        foreach ($users as $user) {
            echo "  - {$user['id']}: {$user['username']} ({$user['email']})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing if events exist for booking form:\n";
try {
    $result = $connection->query("SELECT id, title, venue, event_date, total_seats, price FROM events WHERE status = 'approved' ORDER BY event_date");
    if (!$result) {
        echo "Database error: " . $connection->error . "\n";
    } else {
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        echo "Found " . count($events) . " approved events:\n";
        foreach ($events as $event) {
            echo "  - {$event['id']}: {$event['title']} at {$event['venue']} on {$event['event_date']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nBooking API tests completed.\n";
?>