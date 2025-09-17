<?php
// Test the events API endpoints
session_start();
$_SESSION['user_id'] = 1; // Admin user
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

require_once 'includes/db_connect.php';

echo "Testing Events API:\n\n";

// Test categories
echo "1. Testing categories endpoint:\n";
$_GET['api'] = 'data';
$_GET['action'] = 'categories';

try {
    $result = $connection->query("SELECT id, name FROM event_categories ORDER BY name");
    if (!$result) {
        echo "Database error: " . $connection->error . "\n";
    } else {
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        echo "Found " . count($categories) . " categories:\n";
        foreach ($categories as $cat) {
            echo "  - {$cat['id']}: {$cat['name']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing all_events endpoint:\n";
try {
    $result = $connection->query("SELECT e.*, u.username as organizer_name, c.name as category_name
                                FROM events e 
                                LEFT JOIN users u ON e.organizer_id = u.id 
                                LEFT JOIN event_categories c ON e.category_id = c.id
                                ORDER BY e.created_at DESC");
    if (!$result) {
        echo "Database error: " . $connection->error . "\n";
    } else {
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        echo "Found " . count($events) . " events:\n";
        foreach ($events as $event) {
            echo "  - {$event['id']}: {$event['title']} ({$event['status']})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nAPI tests completed.\n";
?>