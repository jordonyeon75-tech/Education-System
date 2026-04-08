-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 13, 2025 at 04:15 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `edu`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `stu_id` int(11) DEFAULT NULL,
  `present` varchar(50) DEFAULT NULL CHECK (`present` in ('absent','present','late')),
  `date` date DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `course_id`, `stu_id`, `present`, `date`, `updated_at`, `updated_by`) VALUES
(1, 1, 3, 'Late', '2024-12-23', '2024-12-23 19:39:15', NULL),
(2, 1, 3, 'present', '2025-01-13', '2025-01-13 14:27:12', NULL),
(3, 1, 4, 'absent', '2025-01-13', '2025-01-13 14:27:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `classroom`
--

CREATE TABLE `classroom` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `venue` varchar(255) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `class_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classroom`
--

INSERT INTO `classroom` (`id`, `course_id`, `venue`, `start_time`, `end_time`, `class_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Classroom 1', '09:00:00', '11:00:00', '2024-12-23', '2024-12-23 18:47:12', NULL),
(3, 1, 'c12', '10:07:00', '11:07:00', '2025-01-10', '2025-01-10 10:07:30', NULL),
(5, 1, 'c14', '14:00:00', '17:40:00', '2025-01-13', '2025-01-13 14:36:55', '2025-01-13 14:37:17');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `image` varchar(255) NOT NULL,
  `course_description` varchar(255) DEFAULT NULL,
  `course_fee` decimal(10,2) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL CHECK (`status` in ('active','inactive')),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`id`, `course_name`, `course_code`, `image`, `course_description`, `course_fee`, `teacher_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Mathematic', 'MAT01', 'Group-2.png', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 120.00, 2, 'active', '2024-12-23 18:33:59', '2025-01-09 10:13:26'),
(2, 'English', 'ENG01', 'Group-3.png', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 120.00, 2, 'active', '2024-12-23 18:38:38', '2025-01-09 10:13:42'),
(3, 'Art', 'ART01', 'Group 12.png', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 120.00, 2, 'active', '2024-12-23 18:38:55', '2025-01-09 10:13:49'),
(4, 'Science', 'SCI01', 'Group-1.png', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 120.00, 2, 'active', '2024-12-23 18:39:28', '2025-01-09 10:13:54'),
(5, 'Malay', 'MAL01', 'Group-4.png', 'Malay', 60.00, 2, 'active', '2025-01-08 09:06:05', '2025-01-09 10:14:01'),
(6, 'SPAT', 'SPA01', 'Group-4(1).png', 'SPAT Hackaton', 100.00, 3, 'active', '2025-01-13 14:38:25', '2025-01-13 14:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `course_material`
--

CREATE TABLE `course_material` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `file` varchar(255) DEFAULT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_material`
--

INSERT INTO `course_material` (`id`, `course_id`, `user_id`, `description`, `file`, `date`, `updated_date`, `updated_by`) VALUES
(1, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ', '1734952964_blank.pdf', '2024-12-23', '2024-12-23 19:19:38', 2),
(2, 1, 2, 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. ', '1734952964_blank.pdf', '2024-12-23', '2024-12-23 19:22:44', 2);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `stu_id` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`id`, `course_id`, `stu_id`, `status`, `created_at`) VALUES
(1, 1, 3, 'approved', '2024-12-23 19:33:00'),
(2, 1, 4, 'approved', '2025-01-06 12:51:43'),
(3, 3, 4, 'approved', '2025-01-06 12:51:43'),
(4, 2, 4, 'approved', '2025-01-08 08:58:33'),
(5, 5, 4, 'approved', '2025-01-08 09:06:25'),
(6, 4, 4, 'approved', '2025-01-13 14:00:43'),
(7, 6, 4, 'approved', '2025-01-13 14:45:31');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `lock_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `user_id`, `failed_attempts`, `lock_time`) VALUES
(1, 1, 0, NULL),
(2, 2, 0, NULL),
(3, 3, 0, NULL),
(4, 4, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notice_board`
--

CREATE TABLE `notice_board` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notice_board`
--

INSERT INTO `notice_board` (`id`, `title`, `message`, `image`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'Lorem ipsum', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'notice1.jpg', '2024-12-23 18:27:35', '2024-12-23 18:29:11', 1),
(2, 'Lorem ipsum', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'notice2.jpg', '2024-12-23 18:29:32', NULL, 1),
(3, 'Lorem ipsum', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 'notice3.jpg', '2024-12-23 18:31:33', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `stu_id` int(11) DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT NULL,
  `receipt` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `created_date` datetime DEFAULT current_timestamp(),
  `updated_date` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`id`, `course_id`, `stu_id`, `fee_amount`, `receipt`, `status`, `created_date`, `updated_date`) VALUES
(1, 1, 3, 120.00, '', 'approved', '2024-12-23 19:35:18', '2025-01-06 13:43:53'),
(2, 1, 4, 120.00, '1736139184_Attendance.png', 'approved', '2025-01-06 12:53:04', '2025-01-09 09:15:21'),
(3, 3, 4, 120.00, '1736139184_Attendance.png', 'approved', '2025-01-06 12:53:04', '2025-01-08 08:57:29'),
(4, 1, 4, 120.00, '1736139257_book.png', 'approved', '2025-01-06 12:54:17', '2025-01-08 08:57:01'),
(5, 3, 4, 120.00, '1736139257_book.png', 'approved', '2025-01-06 12:54:17', '2025-01-08 09:14:33'),
(6, 2, 4, 120.00, '1736298276_bag.png', 'approved', '2025-01-08 09:04:36', '2025-01-08 09:04:51'),
(7, 5, 4, 60.00, '1736298392_book.png', 'approved', '2025-01-08 09:06:32', '2025-01-08 09:13:54'),
(8, 4, 4, 120.00, '1736748077_receipt.png', 'approved', '2025-01-13 14:01:17', '2025-01-13 14:39:44'),
(9, 6, 4, 100.00, '1736750743_receipt.png', 'approved', '2025-01-13 14:45:43', '2025-01-13 14:46:20');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `email`, `role_id`, `first_name`, `last_name`, `address`, `phone_no`, `profile_picture`, `last_login`, `created_at`, `updated_at`, `status`) VALUES
(1, 'jordon', '$2y$10$JpZozhNwK7CYO/ng7I.vNuLH82T8rJvyGEKDV4QUMhDZii7Zqq2ui', 'jordon@gmail.com', 1, 'Jordonn', 'Teh', '123 Main St, City, Country', '555-1234', 'profile-image.png', '2024-12-23 18:04:08', '2024-12-23 18:04:08', '2025-01-03 19:50:32', 'active'),
(2, 'mathan', '$2y$10$mwyqOip.xx.wfrsUYamvc.oZchoDXK.EkdVQ9JqKdElGyQIv2F..y', 'mathan@gmail.com', 2, 'Mathan', 'Lee', '456 Oak St, Town, Country', '555-5678', 'profile_6784b308ecb963.75707931.png', '2024-12-23 18:04:08', '2024-12-23 18:04:08', '2025-01-13 14:30:32', 'active'),
(3, 'keith', '$2y$10$m49I41cq6FL.O/JDp6F3JuCe9mSUjwQWyW6YT.2YngSZ8zV7cRkVW', 'marcelinang748@gmail.com', 2, 'Keith', 'Goh', '789 Pine St, Village, Country', '555-9876', 'png-almonds-oil-isolated-white-background.jpg', '2024-12-23 18:04:08', '2024-12-23 18:04:08', '2025-01-06 14:55:54', 'active'),
(4, 'Student', '$2y$10$2BowBj4xavsYNxOoTkRLHu7sUuKjh/vKmbZIRyaHeppGuLO9R9n1i', 'marcelinang520@gmail.com', 3, 'Mei', 'Mei', 'jalan', '123-456-7890', 'profile_678487278560b6.68638906.png', NULL, '2025-01-06 12:49:20', '2025-01-13 14:47:42', 'active'),
(6, 'ahboy', '$2y$10$HQ4soVGiNVuJckeRR2mpEuqOPEoH7jv7iUR8Cy582Yet6gN0ivOUi', 'marcelina16ng@gmail.com', 3, 'Khai', 'Boy', 'jalan', '123-456-7890', 'boy(1).png', NULL, '2025-01-13 14:34:06', '2025-01-13 14:34:32', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_type`
--

CREATE TABLE `user_type` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) DEFAULT NULL CHECK (`role_name` in ('Admin','Teacher','Student')),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_type`
--

INSERT INTO `user_type` (`id`, `role_name`, `created_at`) VALUES
(1, 'Admin', '2024-12-11 17:19:11'),
(2, 'Teacher', '2024-12-11 17:19:11'),
(3, 'Student', '2024-12-11 17:19:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `stu_id` (`stu_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `classroom`
--
ALTER TABLE `classroom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `course_material`
--
ALTER TABLE `course_material`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `stu_id` (`stu_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notice_board`
--
ALTER TABLE `notice_board`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `stu_id` (`stu_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_type`
--
ALTER TABLE `user_type`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `classroom`
--
ALTER TABLE `classroom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `course_material`
--
ALTER TABLE `course_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notice_board`
--
ALTER TABLE `notice_board`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_type`
--
ALTER TABLE `user_type`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`stu_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`);

--
-- Constraints for table `classroom`
--
ALTER TABLE `classroom`
  ADD CONSTRAINT `classroom_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`);

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `course_material`
--
ALTER TABLE `course_material`
  ADD CONSTRAINT `course_material_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`),
  ADD CONSTRAINT `course_material_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `course_material_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`);

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`),
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`stu_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `notice_board`
--
ALTER TABLE `notice_board`
  ADD CONSTRAINT `notice_board_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`stu_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_type` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
