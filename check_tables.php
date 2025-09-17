<?php
include 'includes/db_connect.php';

echo "Database Tables:\n";
$result = $connection->query('SHOW TABLES');
while ($row = $result->fetch_row()) {
    echo "- " . $row[0] . "\n";
}

echo "\nEvent Categories Table Structure:\n";
$result = $connection->query('DESCRIBE event_categories');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $connection->error . "\n";
}
?>