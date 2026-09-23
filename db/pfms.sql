-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2026 at 06:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pfms`
--

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `budget_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `budget_month` date NOT NULL COMMENT 'Always the 1st of the month',
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`budget_id`, `user_id`, `category_id`, `budget_month`, `amount`, `created_at`, `updated_at`) VALUES
(1, 3, 31, '2026-09-01', 500.00, '2026-09-18 10:31:39', '2026-09-19 03:58:10'),
(2, 3, 32, '2026-09-01', 300.00, '2026-09-18 10:31:47', '2026-09-18 10:31:47'),
(3, 3, 33, '2026-09-01', 600.00, '2026-09-18 10:31:55', '2026-09-18 10:31:55'),
(4, 3, 32, '2026-08-01', 300.00, '2026-09-19 03:49:08', '2026-09-19 03:49:08'),
(5, 3, 31, '2026-08-01', 550.00, '2026-09-19 03:49:23', '2026-09-19 03:49:23'),
(6, 3, 33, '2026-08-01', 600.00, '2026-09-19 03:49:59', '2026-09-19 03:49:59');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `type` enum('income','expense','budget','investment') NOT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Retire a category without breaking old records',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `user_id`, `name`, `type`, `is_archived`, `created_at`) VALUES
(25, 3, 'Job', 'income', 0, '2026-09-18 10:24:13'),
(26, 3, 'Business', 'income', 0, '2026-09-18 10:24:18'),
(27, 3, 'Gift', 'income', 0, '2026-09-18 10:24:26'),
(28, 3, 'Food', 'expense', 0, '2026-09-18 10:25:32'),
(29, 3, 'Bills', 'expense', 0, '2026-09-18 10:25:37'),
(30, 3, 'Transport', 'expense', 0, '2026-09-18 10:25:45'),
(31, 3, 'Food', 'budget', 0, '2026-09-18 10:27:00'),
(32, 3, 'Bills', 'budget', 0, '2026-09-18 10:27:07'),
(33, 3, 'Transport', 'budget', 0, '2026-09-18 10:27:17'),
(34, 3, 'Property', 'investment', 0, '2026-09-18 10:27:26'),
(35, 3, 'Stocks', 'investment', 0, '2026-09-19 04:04:33');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `expense_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`expense_id`, `user_id`, `category_id`, `amount`, `expense_date`, `description`, `created_at`, `updated_at`) VALUES
(1, 3, 28, 300.00, '2026-09-10', 'Grocery', '2026-09-18 10:32:36', '2026-09-18 10:32:36'),
(2, 3, 28, 250.00, '2026-09-15', 'Grocery', '2026-09-18 10:33:00', '2026-09-18 10:33:00'),
(3, 3, 29, 250.00, '2026-09-18', 'Electricity + Gas', '2026-09-18 10:34:12', '2026-09-18 10:34:12'),
(4, 3, 30, 580.00, '2026-09-19', 'Fuel + Maintenance', '2026-09-19 03:46:14', '2026-09-19 03:51:05'),
(5, 3, 28, 400.00, '2026-08-29', 'Grocery', '2026-09-19 03:51:42', '2026-09-19 03:51:59'),
(6, 3, 29, 250.00, '2026-08-28', 'Electricity + Gas', '2026-09-19 03:52:29', '2026-09-19 03:52:29'),
(7, 3, 30, 400.00, '2026-08-30', 'Fuel + Maintenance', '2026-09-19 03:53:01', '2026-09-19 03:53:01');

-- --------------------------------------------------------

--
-- Table structure for table `income`
--

CREATE TABLE `income` (
  `income_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL if the category was deleted; the record itself survives',
  `source` varchar(120) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `income_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `income`
--

INSERT INTO `income` (`income_id`, `user_id`, `category_id`, `source`, `amount`, `income_date`, `description`, `created_at`, `updated_at`) VALUES
(1, 3, 25, 'Salary', 4000.00, '2026-09-01', 'two pays of august', '2026-09-18 10:28:38', '2026-09-18 10:29:12'),
(2, 3, 27, 'Junaid Khan', 50.00, '2026-09-02', 'Birthday gift', '2026-09-18 10:30:32', '2026-09-18 10:30:32'),
(3, 3, 25, 'Salary', 4000.00, '2026-08-01', 'two pays of july', '2026-09-19 03:48:24', '2026-09-19 03:48:24');

-- --------------------------------------------------------

--
-- Table structure for table `investments`
--

CREATE TABLE `investments` (
  `investment_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Investment type, from categories where type = investment',
  `name` varchar(120) NOT NULL,
  `amount_invested` decimal(12,2) NOT NULL,
  `current_value` decimal(12,2) NOT NULL,
  `purchase_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `gain_loss` decimal(13,2) GENERATED ALWAYS AS (`current_value` - `amount_invested`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `investments`
--

INSERT INTO `investments` (`investment_id`, `user_id`, `category_id`, `name`, `amount_invested`, `current_value`, `purchase_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 34, 'Sydney, Ranch', 2500.00, 2600.00, '2026-09-02', 'With partners', '2026-09-19 04:01:32', '2026-09-19 04:01:32'),
(2, 3, 35, 'CBA - Commonwealth', 2000.00, 2400.00, '2026-09-17', 'through binanace', '2026-09-19 04:07:12', '2026-09-19 04:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'SHA-256 of the emailed token',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Output of PHP password_hash(), never a plain password',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password_hash`, `is_active`, `last_login_at`, `created_at`, `updated_at`) VALUES
(3, 'Prachi Kiran Patil', 'prachi@pfms.com', '+6123456789', '$2y$10$0I.L0M55b1MvnWIJPEGqHOBC3L0hP8c8vBJsMFyOgcCQwQjUBKr/a', 1, NULL, '2026-09-17 14:45:14', '2026-09-19 03:57:11'),
(4, 'Junaid Khan', 'junaid@pfms.com', '+61444560621', '$2y$10$jzSASZNISQWNXpTrih7az.xCXSfoI7PvOjkk/mKVtVq53I4FtMuU.', 1, NULL, '2026-09-17 14:49:56', '2026-09-17 16:30:41');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_budget_status`
-- (See below for the actual view)
--
CREATE TABLE `v_budget_status` (
`budget_id` int(10) unsigned
,`user_id` int(10) unsigned
,`budget_month` date
,`category_name` varchar(60)
,`budget_amount` decimal(12,2)
,`spent` decimal(34,2)
,`remaining` decimal(35,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_investment_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_investment_summary` (
`user_id` int(10) unsigned
,`investment_type` varchar(60)
,`holdings` bigint(21)
,`total_invested` decimal(34,2)
,`total_current_value` decimal(34,2)
,`total_gain_loss` decimal(35,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_monthly_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_monthly_summary` (
`user_id` int(10) unsigned
,`month_start` varchar(10)
,`total_income` decimal(34,2)
,`total_expenses` decimal(34,2)
,`savings` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_transactions`
-- (See below for the actual view)
--
CREATE TABLE `v_transactions` (
`user_id` int(10) unsigned
,`txn_type` varchar(7)
,`txn_id` int(10) unsigned
,`txn_date` date
,`description` varchar(255)
,`category_name` varchar(60)
,`amount` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Structure for view `v_budget_status`
--
DROP TABLE IF EXISTS `v_budget_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_budget_status`  AS SELECT `b`.`budget_id` AS `budget_id`, `b`.`user_id` AS `user_id`, `b`.`budget_month` AS `budget_month`, `bc`.`name` AS `category_name`, `b`.`amount` AS `budget_amount`, coalesce(sum(`e`.`amount`),0) AS `spent`, `b`.`amount`- coalesce(sum(`e`.`amount`),0) AS `remaining` FROM (((`budgets` `b` join `categories` `bc` on(`bc`.`category_id` = `b`.`category_id`)) left join `categories` `ec` on(`ec`.`user_id` = `b`.`user_id` and `ec`.`type` = 'expense' and `ec`.`name` = `bc`.`name`)) left join `expenses` `e` on(`e`.`category_id` = `ec`.`category_id` and `e`.`user_id` = `b`.`user_id` and `e`.`expense_date` >= `b`.`budget_month` and `e`.`expense_date` < `b`.`budget_month` + interval 1 month)) GROUP BY `b`.`budget_id`, `b`.`user_id`, `b`.`budget_month`, `bc`.`name`, `b`.`amount` ;

-- --------------------------------------------------------

--
-- Structure for view `v_investment_summary`
--
DROP TABLE IF EXISTS `v_investment_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_investment_summary`  AS SELECT `i`.`user_id` AS `user_id`, `c`.`name` AS `investment_type`, count(0) AS `holdings`, sum(`i`.`amount_invested`) AS `total_invested`, sum(`i`.`current_value`) AS `total_current_value`, sum(`i`.`gain_loss`) AS `total_gain_loss` FROM (`investments` `i` left join `categories` `c` on(`c`.`category_id` = `i`.`category_id`)) GROUP BY `i`.`user_id`, `c`.`name` ;

-- --------------------------------------------------------

--
-- Structure for view `v_monthly_summary`
--
DROP TABLE IF EXISTS `v_monthly_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_monthly_summary`  AS SELECT `v_transactions`.`user_id` AS `user_id`, date_format(`v_transactions`.`txn_date`,'%Y-%m-01') AS `month_start`, sum(case when `v_transactions`.`txn_type` = 'income' then `v_transactions`.`amount` else 0 end) AS `total_income`, sum(case when `v_transactions`.`txn_type` = 'expense' then `v_transactions`.`amount` else 0 end) AS `total_expenses`, sum(case when `v_transactions`.`txn_type` = 'income' then `v_transactions`.`amount` else -`v_transactions`.`amount` end) AS `savings` FROM `v_transactions` GROUP BY `v_transactions`.`user_id`, date_format(`v_transactions`.`txn_date`,'%Y-%m-01') ;

-- --------------------------------------------------------

--
-- Structure for view `v_transactions`
--
DROP TABLE IF EXISTS `v_transactions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_transactions`  AS SELECT `i`.`user_id` AS `user_id`, 'income' AS `txn_type`, `i`.`income_id` AS `txn_id`, `i`.`income_date` AS `txn_date`, coalesce(`i`.`description`,`i`.`source`) AS `description`, `c`.`name` AS `category_name`, `i`.`amount` AS `amount` FROM (`income` `i` left join `categories` `c` on(`c`.`category_id` = `i`.`category_id`))union all select `e`.`user_id` AS `user_id`,'expense' AS `expense`,`e`.`expense_id` AS `expense_id`,`e`.`expense_date` AS `expense_date`,coalesce(`e`.`description`,`c`.`name`) AS `COALESCE(e.description, c.name)`,`c`.`name` AS `name`,`e`.`amount` AS `amount` from (`expenses` `e` left join `categories` `c` on(`c`.`category_id` = `e`.`category_id`))  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD UNIQUE KEY `uq_budget_month_category` (`user_id`,`budget_month`,`category_id`),
  ADD KEY `idx_budget_category` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uq_category_per_user` (`user_id`,`type`,`name`),
  ADD KEY `idx_category_lookup` (`user_id`,`type`,`is_archived`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`expense_id`),
  ADD KEY `idx_expense_user_date` (`user_id`,`expense_date`),
  ADD KEY `idx_expense_category` (`category_id`);

--
-- Indexes for table `income`
--
ALTER TABLE `income`
  ADD PRIMARY KEY (`income_id`),
  ADD KEY `idx_income_user_date` (`user_id`,`income_date`),
  ADD KEY `idx_income_category` (`category_id`);

--
-- Indexes for table `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`investment_id`),
  ADD KEY `idx_investment_user` (`user_id`,`purchase_date`),
  ADD KEY `idx_investment_category` (`category_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD UNIQUE KEY `uq_reset_token` (`token_hash`),
  ADD KEY `idx_reset_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `budget_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `expense_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `income`
--
ALTER TABLE `income`
  MODIFY `income_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `investments`
--
ALTER TABLE `investments`
  MODIFY `investment_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budget_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_budget_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_category_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expense_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_expense_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `income`
--
ALTER TABLE `income`
  ADD CONSTRAINT `fk_income_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_income_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `investments`
--
ALTER TABLE `investments`
  ADD CONSTRAINT `fk_investment_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_investment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
