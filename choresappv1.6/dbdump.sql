-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 24, 2025 at 05:24 PM
-- Server version: 8.0.43-34
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db2ygwbarhbyxk`
--

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `id` int NOT NULL,
  `parent_user_id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_pic_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `points` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`id`, `parent_user_id`, `name`, `profile_pic_url`, `points`, `created_at`, `updated_at`) VALUES
(19, 11, 'Mom', 'backend_chores_quest/uploads/img_sanitized_6869814a661c35.78855541.png', 10, '2025-07-05 19:47:22', '2025-07-05 20:05:52'),
(20, 11, 'Dad', 'backend_chores_quest/uploads/img_sanitized_686981554929d9.40461864.png', 0, '2025-07-05 19:47:33', '2025-07-05 19:47:33'),
(21, 11, 'Child1', 'backend_chores_quest/uploads/img_sanitized_6869815f7cbc62.31437658.png', 69, '2025-07-05 19:47:43', '2025-07-05 22:40:27'),
(22, 11, 'Child2', 'backend_chores_quest/uploads/img_sanitized_686981696074a2.26773993.png', 2, '2025-07-05 19:47:53', '2025-07-05 19:59:06'),
(23, 11, 'Child3', 'backend_chores_quest/uploads/img_sanitized_68698173f25093.44569469.png', 110, '2025-07-05 19:48:04', '2025-07-05 22:36:55');

-- --------------------------------------------------------

--
-- Table structure for table `claimed_rewards`
--

CREATE TABLE `claimed_rewards` (
  `id` int NOT NULL,
  `child_id` int NOT NULL,
  `reward_id` int NOT NULL,
  `points_spent` int NOT NULL,
  `claimed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `parent_user_id` int NOT NULL,
  `child_id` int DEFAULT NULL,
  `task_id` int DEFAULT NULL,
  `reward_id` int DEFAULT NULL,
  `notification_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `notification_for` enum('parent','admin') NOT NULL DEFAULT 'parent',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int NOT NULL,
  `parent_user_id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_points` int NOT NULL DEFAULT '0',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rewards`
--

INSERT INTO `rewards` (`id`, `parent_user_id`, `title`, `required_points`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(32, 11, 'Ice-cream Treat ', 50, 'backend_chores_quest/uploads/img_sanitized_6869866691ebe9.44001811.png', 1, '2025-07-05 20:09:10', '2025-07-05 20:09:10'),
(33, 11, '1 Hour Tablet', 75, 'backend_chores_quest/uploads/img_sanitized_68698688a74d23.27874258.png', 1, '2025-07-05 20:09:44', '2025-07-05 20:09:44');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `parent_user_id` int NOT NULL,
  `DEPRECATED_assigned_child_id` int DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `due_date` date DEFAULT NULL,
  `points` int NOT NULL DEFAULT '0',
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_family_task` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 if task is for the whole family, 0 otherwise',
  `repeat_type` enum('none','daily','weekly','monthly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `repeat_on_days` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Comma-separated days of the week (1=Mon, 7=Sun)',
  `original_task_id` int DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `parent_user_id`, `DEPRECATED_assigned_child_id`, `title`, `notes`, `due_date`, `points`, `image_url`, `status`, `is_family_task`, `repeat_type`, `repeat_on_days`, `original_task_id`, `completed_date`, `created_at`, `updated_at`) VALUES
(261, 11, NULL, 'Morning Brushing Teeth', '', '2025-07-05', 2, '../imgs/icons/dental-care.png', 'active', 0, 'daily', NULL, NULL, NULL, '2025-07-05 19:52:36', '2025-07-05 19:52:45'),
(262, 11, NULL, 'Night Pajamas', '', '2025-07-05', 2, '../imgs/icons/pajamas.png', 'active', 0, 'daily', NULL, NULL, NULL, '2025-07-05 19:53:29', '2025-07-05 19:53:29'),
(263, 11, NULL, 'Room Clean Up', 'Organize your room and vacuum floor', '2025-07-06', 15, '../imgs/icons/bedroom.png', 'active', 0, 'weekly', NULL, NULL, NULL, '2025-07-05 19:54:38', '2025-07-05 19:54:50'),
(264, 11, NULL, 'Change Kitchen Garbage', 'Take kitchen garbage to the garbage bin by the garage and put a new bag.', '2025-07-05', 10, '../imgs/icons/garbage1.png', 'active', 1, 'daily', NULL, NULL, NULL, '2025-07-05 19:56:07', '2025-07-05 19:56:16'),
(265, 11, NULL, 'Night Shower', '', '2025-07-05', 10, '../imgs/icons/bathtub.png', 'active', 0, 'daily', NULL, NULL, NULL, '2025-07-05 19:59:44', '2025-07-05 19:59:44');

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `child_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_completions`
--

CREATE TABLE `task_completions` (
  `id` int NOT NULL,
  `task_id` int NOT NULL,
  `child_id` int NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `points_awarded` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `task_completions`
--

INSERT INTO `task_completions` (`id`, `task_id`, `child_id`, `completed_at`, `points_awarded`) VALUES
(348, 261, 22, '2025-07-05 19:59:06', 2),
(349, 262, 21, '2025-07-05 20:05:04', 2),
(350, 261, 21, '2025-07-05 20:05:05', 2),
(352, 265, 23, '2025-07-05 22:36:55', 10);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('parent','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'parent',
  `pin_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hashed 4-digit PIN for parent dashboard access',
  `recovery_code_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enable_overdue_task_notifications` tinyint(1) DEFAULT '1' COMMENT 'Enable/disable notifications for overdue tasks',
  `auto_delete_completed_tasks` tinyint(1) DEFAULT '0' COMMENT 'Enable/disable auto-deletion of old completed tasks',
  `auto_delete_completed_tasks_days` int DEFAULT '30' COMMENT 'Number of days after which completed tasks are auto-deleted',
  `auto_delete_notifications` tinyint(1) DEFAULT '0' COMMENT 'Enable/disable auto-deletion of old notifications',
  `auto_delete_notifications_days` int DEFAULT '30' COMMENT 'Number of days after which notifications are auto-deleted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password_hash`, `user_type`, `pin_hash`, `recovery_code_hash`, `created_at`, `updated_at`, `enable_overdue_task_notifications`, `auto_delete_completed_tasks`, `auto_delete_completed_tasks_days`, `auto_delete_notifications`, `auto_delete_notifications_days`) VALUES
(11, 'Parent', 'Parent', '$2y$10$IHkcV8w4Zj0hmnNgNs76M.R84QF0QPrhK.iaQ3c.e6bkzUUJU/Mc6', 'parent', NULL, '$2y$10$HC99P1NS33WI4.1Po3OyV.PfhBRs0NNgjWcjo5GakevuKT6OKZKXS', '2025-07-05 19:42:25', '2025-07-05 19:42:25', 1, 0, 30, 0, 30);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_user_id` (`parent_user_id`);

--
-- Indexes for table `claimed_rewards`
--
ALTER TABLE `claimed_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `reward_id` (`reward_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `child_id` (`child_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `reward_id` (`reward_id`),
  ADD KEY `idx_notifications_parent_user_id` (`parent_user_id`),
  ADD KEY `idx_notifications_is_read` (`is_read`),
  ADD KEY `idx_notifications_created_at` (`created_at`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_user_id` (`parent_user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_user_id` (`parent_user_id`),
  ADD KEY `assigned_child_id` (`DEPRECATED_assigned_child_id`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `task_completions`
--
ALTER TABLE `task_completions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `child_id` (`child_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `claimed_rewards`
--
ALTER TABLE `claimed_rewards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1283;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=281;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=385;

--
-- AUTO_INCREMENT for table `task_completions`
--
ALTER TABLE `task_completions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=952;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `children`
--
ALTER TABLE `children`
  ADD CONSTRAINT `children_ibfk_1` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `claimed_rewards`
--
ALTER TABLE `claimed_rewards`
  ADD CONSTRAINT `claimed_rewards_ibfk_1` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `claimed_rewards_ibfk_2` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rewards`
--
ALTER TABLE `rewards`
  ADD CONSTRAINT `rewards_ibfk_1` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`DEPRECATED_assigned_child_id`) REFERENCES `children` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_completions`
--
ALTER TABLE `task_completions`
  ADD CONSTRAINT `task_completions_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_completions_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `children` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
