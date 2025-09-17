<?php
include 'includes/db_connect.php';

echo "Events Table Structure:\n";
$result = $connection->query('DESCRIBE events');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $connection->error . "\n";
}
?>