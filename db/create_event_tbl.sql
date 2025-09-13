CREATE TABLE `events` (
  `id` int NOT NULL,
  `organizer_id` int NOT NULL,
  `category_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext,
  `venue` varchar(255) NOT NULL,
  `event_date` datetime NOT NULL,
  `total_seats` varchar(45) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL,
  `creared_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `organizer_id_idx` (`organizer_id`),
  KEY `cat_id_idx` (`category_id`),
  CONSTRAINT `cat_id` FOREIGN KEY (`category_id`) REFERENCES `event_categories` (`id`),
  CONSTRAINT `organizer_id` FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`)
)
