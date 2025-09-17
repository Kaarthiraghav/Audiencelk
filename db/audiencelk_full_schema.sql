-- Create database
USE if0_39624525_audiencelk;

-- Create roles table
CREATE TABLE `roles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
);

-- Create users table
CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id_idx` (`role_id`),
  CONSTRAINT `fk_users_roles` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Create event categories table
CREATE TABLE `event_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`)
);

-- Create events table
CREATE TABLE `events` (
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
);

-- Create bookings table
CREATE TABLE `bookings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `event_id` INT NOT NULL,
  `booking_number` VARCHAR(9) NOT NULL,
  `booking_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `seats` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_number_unique` (`booking_number`),
  KEY `user_id_idx` (`user_id`),
  KEY `event_id_idx` (`event_id`),
  CONSTRAINT `fk_bookings_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
);

-- Create payments table
CREATE TABLE `payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `booking_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('success','pending','canceled') DEFAULT 'pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
);

-- Insert default roles
INSERT INTO `roles` (`role`) VALUES ('Admin'), ('Organizer'), ('User');
