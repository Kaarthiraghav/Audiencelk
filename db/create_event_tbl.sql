CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(100),
  `seats` int NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `organizer_id` int,
  `price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
