<?php
include '../includes/db_connect.php';

echo "Reading schema file...\n";
$schema = file_get_contents('audiencelk_full_schema.sql');

// Split by semicolons and execute each statement
$statements = explode(';', $schema);

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        if ($connection->query($statement)) {
            echo "Success\n";
        } else {
            echo "Error: " . $connection->error . "\n";
        }
    }
}

echo "Schema applied successfully!\n";
?>