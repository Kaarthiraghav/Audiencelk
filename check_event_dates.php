<?php
$conn = new mysqli('localhost', 'root', '', 'audiencelk');
$result = $conn->query('SELECT id, title, event_date, status FROM events');
while ($row = $result->fetch_assoc()) {
    echo "Event ID: {$row['id']}, Title: {$row['title']}, Date: {$row['event_date']}, Status: {$row['status']}\n";
    echo "Current time: " . date('Y-m-d H:i:s') . "\n";
    echo "Event is in future: " . (($row['event_date'] > date('Y-m-d H:i:s')) ? 'Yes' : 'No') . "\n\n";
}
$conn->close();
?>