<?php
try {
    $conn = new mysqli('localhost', 'root', '', 'audiencelk');
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error . "\n";
        exit;
    }
    echo "Connected successfully\n";
    
    // Drop the existing events table if it exists
    $conn->query("DROP TABLE IF EXISTS events");
    echo "Dropped old events table\n";
    
    // Create the new events table with correct schema
    $sql = "CREATE TABLE `events` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `organizer_id` INT NOT NULL,
      `category_id` INT NOT NULL,
      `title` VARCHAR(255) NOT NULL,
      `description` LONGTEXT,
      `venue` VARCHAR(255) NOT NULL,
      `event_date` DATETIME NOT NULL,
      `total_seats` INT NOT NULL,
      `price` DECIMAL(10,2) NOT NULL,
      `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `organizer_id_idx` (`organizer_id`),
      KEY `cat_id_idx` (`category_id`),
      CONSTRAINT `fk_events_categories` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,
      CONSTRAINT `fk_events_organizer` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    );";
    
    if ($conn->query($sql)) {
        echo "Events table created successfully with new schema\n";
    } else {
        echo "Error creating events table: " . $conn->error . "\n";
    }
    
    // Add some test event categories if they don't exist
    echo "Checking event_categories table...\n";
    $result = $conn->query("SHOW TABLES LIKE 'event_categories'");
    if ($result->num_rows == 0) {
        echo "Creating event_categories table...\n";
        $conn->query("CREATE TABLE event_categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, description TEXT)");
    }
    
    $categories = [
        ['Music', 'Musical concerts and performances'],
        ['Sports', 'Sporting events and competitions'],
        ['Arts', 'Art exhibitions and cultural events'],
        ['Technology', 'Tech conferences and meetups'],
        ['Business', 'Business conferences and networking']
    ];
    
    foreach ($categories as $cat) {
        $stmt = $conn->prepare("INSERT IGNORE INTO event_categories (name, description) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $cat[0], $cat[1]);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo "Added test categories\n";
    
    // Add a test event
    $stmt = $conn->prepare("INSERT INTO events (organizer_id, category_id, title, description, venue, event_date, total_seats, price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $organizer_id = 1; // Admin user
        $category_id = 1; // Music category
        $title = "Updated Test Music Concert";
        $description = "A fantastic musical concert with updated schema";
        $venue = "Main Concert Hall";
        $event_date = date('Y-m-d H:i:s', strtotime('+1 week'));
        $total_seats = 500;
        $price = 75.00;
        $status = 'approved';
        
        $stmt->bind_param("iisssiids", $organizer_id, $category_id, $title, $description, $venue, $event_date, $total_seats, $price, $status);
        
        if ($stmt->execute()) {
            echo "Added test event successfully\n";
        } else {
            echo "Error adding test event: " . $stmt->error . "\n";
        }
        $stmt->close();
    } else {
        echo "Error preparing test event statement: " . $conn->error . "\n";
    }
    
    $conn->close();
    echo "\nSchema update completed!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>