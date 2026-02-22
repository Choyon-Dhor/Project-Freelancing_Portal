CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('freelancer','client') NOT NULL,
  `company_name` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `job_type` enum('Full-Time','Part-Time','Contract','Internship') NOT NULL,
  `description` text NOT NULL,
  `skills` varchar(255) NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `deadline` date NOT NULL,
  `posted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Open','In Progress','Completed') NOT NULL DEFAULT 'Open',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `proposal` text NOT NULL,
  `bid_amount` decimal(10,2) DEFAULT NULL,
  `estimated_time` varchar(100) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','rejected','in_progress','completed') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `job_freelancer` (`job_id`,`freelancer_id`)
);

CREATE TABLE `freelancer_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `education` varchar(255) NOT NULL,
  `skills` varchar(255) NOT NULL,
  `experience_years` int(11) NOT NULL,
  `bio` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_id` (`user_id`)
);

CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('In Progress','Completed') NOT NULL DEFAULT 'In Progress',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);