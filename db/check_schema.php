<?php
try {
    $conn = new mysqli('localhost', 'root', '', 'audiencelk');
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error . "\n";
        exit;
    }
    echo "Connected successfully\n\n";
    
    $result = $conn->query('DESCRIBE events');
    if ($result) {
        echo "Events table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . " | " . $row['Default'] . "\n";
        }
    } else {
        echo "Error describing table: " . $conn->error . "\n";
    }
    
    echo "\n\nEvent categories table structure:\n";
    $result = $conn->query('DESCRIBE event_categories');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Key'] . " | " . $row['Default'] . "\n";
        }
    } else {
        echo "Error describing table: " . $conn->error . "\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>