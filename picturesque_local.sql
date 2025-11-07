SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*==========================================================
  app_settings
==========================================================*/
CREATE TABLE `app_settings` (
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `app_settings` (`name`, `value`) VALUES
('brand_logo', ''),
('brand_name', 'PICTURESQUE');


/*==========================================================
  categories
==========================================================*/
CREATE TABLE `categories` (
  `category_id` int UNSIGNED NOT NULL,
  `category_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`, `category_name`, `slug`, `active`) VALUES
(1, 'Landscape', 'landscape', 1),
(2, 'Abstract', 'abstract', 1),
(5, 'Portrait', 'portrait', 1),
(6, 'English', 'english', 1);

/*==========================================================
  comments
==========================================================*/
CREATE TABLE `comments` (
  `comment_id` bigint UNSIGNED NOT NULL,
  `picture_id` bigint UNSIGNED NOT NULL,
  `profile_id` bigint UNSIGNED NOT NULL,
  `parent_comment_id` bigint UNSIGNED DEFAULT NULL,
  `comment_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `comments` (`comment_id`, `picture_id`, `profile_id`, `parent_comment_id`, `comment_content`, `created_at`) VALUES
(5, 12, 1, NULL, 'something', '2025-10-21 07:36:14'),
(6, 12, 1, 5, 'something', '2025-10-21 07:36:19'),
(20, 10, 3, NULL, 'ghng h', '2025-10-23 18:21:05'),
(21, 10, 3, NULL, 'hnhyyn', '2025-10-23 18:30:54'),
(22, 10, 3, NULL, 'dbrthtrh', '2025-10-23 18:36:04'),
(23, 10, 3, NULL, 'fbrthrthtyhtyj', '2025-10-23 18:40:25');

/*==========================================================
  contact_messages
==========================================================*/
CREATE TABLE `contact_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `profile_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `company` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subject` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `contact_messages` (`id`, `profile_id`, `name`, `email`, `company`, `subject`, `message`, `ip`, `created_at`) VALUES
(1, 1, 'test', 'sali_3006@abv.bg', 'test', 'test', 'Just a test', '::1', '2025-10-17 20:57:56'),
(2, 1, 'salinkaa', 'sali_3006@abv.bg', '', 'test2', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', '::1', '2025-10-17 21:01:11');

/*==========================================================
  featured_pictures
==========================================================*/
CREATE TABLE `featured_pictures` (
  `id` bigint UNSIGNED NOT NULL,
  `picture_id` bigint UNSIGNED NOT NULL,
  `week_start` date NOT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `featured_pictures` (`id`, `picture_id`, `week_start`, `created_by`, `created_at`) VALUES
(2, 12, '2025-10-20', 17, '2025-10-25 16:43:49'),
(4, 6, '2025-10-20', 17, '2025-10-25 16:49:50'),
(5, 24, '2025-10-20', 17, '2025-10-25 17:01:16'),
(6, 9, '2025-10-20', 1, '2025-10-26 11:17:34'),
(7, 8, '2025-10-20', 1, '2025-10-26 11:27:25'),
(8, 45, '2025-10-27', 1, '2025-10-27 16:05:31'),
(9, 29, '2025-10-27', 1, '2025-10-27 16:05:36'),
(10, 11, '2025-10-27', 1, '2025-10-27 16:05:37'),
(11, 7, '2025-10-27', 1, '2025-10-27 16:05:42'),
(12, 24, '2025-10-27', 1, '2025-10-27 16:05:44');

/*==========================================================
  likes
==========================================================*/
CREATE TABLE `likes` (
  `like_id` bigint UNSIGNED NOT NULL,
  `picture_id` bigint UNSIGNED NOT NULL,
  `profile_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `likes` (`like_id`, `picture_id`, `profile_id`, `created_at`) VALUES
(5, 3, 1, '2025-10-17 18:02:13'),
(6, 10, 1, '2025-10-17 18:51:58'),
(7, 8, 1, '2025-10-17 18:51:59'),
(8, 12, 1, '2025-10-18 21:46:55'),
(9, 5, 1, '2025-10-18 21:46:58'),
(11, 8, 3, '2025-10-21 15:23:24'),
(15, 9, 3, '2025-10-21 17:10:24'),
(16, 6, 3, '2025-10-21 17:10:25'),
(18, 12, 3, '2025-10-21 17:10:33'),
(22, 5, 3, '2025-10-21 19:09:23');

/*==========================================================
  pages
==========================================================*/
CREATE TABLE `pages` (
  `page_id` bigint UNSIGNED NOT NULL,
  `slug` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `content` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pages` (`page_id`, `slug`, `title`, `content`, `image_path`, `updated_by`, `updated_at`) VALUES
(1, 'about', 'About Picturesque', 'Picturesque is a community-driven platform where landscape photographers share breathtaking views from around the world!!fbdhthujykmyuk hnyn', 'about_1760727070_155390f5.jpg', 3, '2025-10-21 19:28:02'),
(3, 'rules', 'Rules & Regulations', 'Rules & Regulations\r\n\r\nWelcome to Picturesque, a creative space for sharing and appreciating landscape photography.\r\nTo keep our community inspiring, respectful, and safe for everyone, please read and follow the guidelines below.\r\n\r\n\r\n1. Respect and Conduct\r\nTreat all members with respect. Harassment, hate speech, or discrimination of any kind will not be tolerated.\r\nProvide constructive feedback, celebrate creativity, and avoid personal attacks or negativity.\r\nDo not impersonate others or post misleading information.\r\n\r\n2. Content Guidelines\r\nUpload only images that you have created or own the rights to.\r\nDo not post copyrighted material, offensive content, or images that violate privacy or depict violence.\r\nEach photo should include a suitable title, category, and if applicable, a short description.\r\nAvoid excessive watermarks or promotional overlays that distract from the artwork.\r\n\r\n3. Categories and Tagging\r\nUse accurate categories and tags to help others discover your work.\r\nMisleading or unrelated tagging may result in content removal.\r\n\r\n4. Community Interaction\r\nEngage positively through likes, comments, and discussions.\r\nSpamming, self-promotion, or mass posting for attention is discouraged.\r\nReport any inappropriate behavior or content through the contact or report options.\r\n\r\n5. Privacy and Security\r\nDo not share personal data publicly in comments, photos, or captions.\r\nRespect the privacy of other users and any subjects appearing in photos.\r\n\r\n\r\n6. Moderation and Enforcement\r\nThe Picturesque team reserves the right to review, edit, or remove content that violates these rules.\r\nRepeated or serious violations may result in account suspension or permanent removal.\r\n\r\n\r\n7. Updates\r\nThese Rules and Regulations may be updated periodically. Continued use of the platform after updates constitutes acceptance of the revised terms.', NULL, 1, '2025-10-31 08:11:05');

/*==========================================================
  pictures
==========================================================*/
CREATE TABLE `pictures` (
  `picture_id` bigint UNSIGNED NOT NULL,
  `profile_id` bigint UNSIGNED NOT NULL,
  `picture_title` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `picture_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `picture_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pictures` (`picture_id`, `profile_id`, `picture_title`, `picture_description`, `picture_url`, `category_id`, `created_at`, `updated_at`) VALUES
(3, 2, 'smth', '', '1760721452_6923836d.jpg', NULL, '2025-10-17 17:17:32', '2025-10-17 17:17:32'),
(4, 1, 'Picture', '', '1760725262_664f36ce.jpg', NULL, '2025-10-17 18:21:02', '2025-10-17 18:21:02'),
(5, 1, 'Nice', '', '1760725271_ad85bbb2.jpg', NULL, '2025-10-17 18:21:11', '2025-10-17 18:21:11'),
(6, 1, 'Autumn', '', '1760725287_b82a83f3.jpg', NULL, '2025-10-17 18:21:27', '2025-10-17 18:21:27'),
(7, 1, 'Waterfall', '', '1760725319_6b4d5bac.jpg', NULL, '2025-10-17 18:21:59', '2025-10-17 18:21:59'),
(8, 1, 'trees', '', '1760725336_1dc26765.jpg', NULL, '2025-10-17 18:22:16', '2025-10-17 18:22:16'),
(9, 1, 'clouds', '', '1760725350_45e713e4.jpg', NULL, '2025-10-17 18:22:30', '2025-10-17 18:22:30'),
(10, 1, 'waterfall2', '', '1760725374_b3b1e3e0.jpg', NULL, '2025-10-17 18:22:54', '2025-10-17 18:22:54'),
(11, 1, 'Autumn fall', '', '1760732987_f3ee9f9c.jpg', NULL, '2025-10-17 20:29:47', '2025-10-17 20:29:47'),
(12, 1, 'tram', '', '1760733108_67e5aa71.jpg', NULL, '2025-10-17 20:31:48', '2025-10-17 20:31:48'),
(22, 3, 'rtht', 'yjyuj', '1761241679_b1044247.png', 2, '2025-10-23 17:47:59', '2025-10-23 17:47:59'),
(23, 3, 'verbgtr', 'yjtyk', '1761292458_228f5855.png', 6, '2025-10-24 07:54:18', '2025-10-24 07:54:18'),
(24, 8, 'gnfntn', 'gnrtb', '1761302700_54a293eb.png', 6, '2025-10-24 10:45:00', '2025-10-24 10:45:00'),
(29, 17, 'gchv', 'test', '1761492341_06ad9169.png', 5, '2025-10-26 15:25:41', '2025-10-26 15:25:41'),
(31, 17, 'tntnytn', '', '1761492394_315cfbcc.png', 1, '2025-10-26 15:26:34', '2025-10-26 15:26:34'),
(35, 17, 'hey', '', '1761494551_e1fee5a6.png', 2, '2025-10-26 16:02:31', '2025-10-26 16:02:31'),
(42, 17, 'something', '', '/uploads/1761499998_5ef397b3.png', 6, '2025-10-26 17:33:18', '2025-10-26 17:33:18'),
(43, 17, 'test', '', '/uploads/1761501157_a643d29b.png', 5, '2025-10-26 17:52:37', '2025-10-26 17:52:37'),
(44, 17, 'yuy', '', '/uploads/1761505279_02b50140.png', 2, '2025-10-26 19:01:19', '2025-10-26 19:01:19'),
(45, 17, 'hj', '', '/uploads/1761505351_eeed111b.png', 1, '2025-10-26 19:02:31', '2025-10-26 19:02:31'),
(46, 19, 'bjbjb', '', '/uploads/1761645362_ad0cc8d6.png', 6, '2025-10-28 09:56:02', '2025-10-28 09:56:02'),
(47, 21, 'egrehy', 'vg', '/uploads/1761898269_04934fbe.png', 1, '2025-10-31 08:11:09', '2025-10-31 08:11:31'),
(49, 22, 'calmmmm', '', '/uploads/1762281257_f8738fd7.jpg', 5, '2025-11-04 18:34:17', '2025-11-04 18:34:17');


/*==========================================================
  picture_category (join)
==========================================================*/
CREATE TABLE `picture_category` (
  `picture_id` bigint UNSIGNED NOT NULL,
  `category_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*==========================================================
  profiles
==========================================================*/
CREATE TABLE `profiles` (
  `profile_id` bigint UNSIGNED NOT NULL,
  `login_email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profile_info` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `profile_bio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `avatar_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cover_photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('user','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `status` enum('active','blocked','banned') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `profiles` (`profile_id`, `login_email`, `password_hash`, `display_name`, `username`, `profile_info`, `profile_bio`, `avatar_photo`, `cover_photo`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'sali_3006@abv.bg', '$2y$10$QeB1WaEnjDBWId7u.WSPUeYG3pBAvtkh1lQBHPxckGw8eRDovw3cK', 'salinkaa', NULL, NULL, NULL, 'avt_1_1760725431.png', 'cover_1_1760725028.jpg', '', 'admin', 'active', '2025-10-11 17:53:38', '2025-10-17 18:23:51'),
(2, 'sali_3006@abv.bgg', '$2y$10$e8RTnhGusuDChaLJ1Bhnd.w8OZzgImYz426kznLMdWp/HJ4iH8ag6', 'salinotadmin', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-15 20:21:22', '2025-10-21 19:27:58'),
(3, 'madalinasirbu197@gmail.com', '$2y$10$Z5V5HU20K/eZxukE4p5yeO4VUDJTSBxi4dXtdNyM66jXx5q3LbNxW', 'Madalgogoina', NULL, NULL, NULL, 'avt_3_1761072446.png', NULL, NULL, 'admin', 'active', '2025-10-21 13:43:41', '2025-10-21 18:47:50'),
(7, 'elene2004@gmail.com', '$2y$10$U4n1f.JpNSjHsxxb3fODsON0pXiFWf7WMbqRPn1KVovFhyVEgTczS', 'elene', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-23 18:42:01', '2025-10-23 18:42:01'),
(8, 'elene12@gmail.com', '$2y$12$nYksbYiGimu6lr4.4WJ8y.NKZJLd5EnvpzeO/VjwPgRI43eBePr5q', 'Elene', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 10:43:45', '2025-10-24 10:43:45'),
(9, 'ion@gmail.com', '$2y$12$gf/SctKVxYhzmxzdZ6RRSeQd8EmzWU84GtX4RTjiSN0vz5X82d.qG', 'Ion', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'active', '2025-10-24 10:48:34', '2025-10-24 10:48:48'),
(10, 'madaadmin@gmail.com', '$2y$12$4Y.gusARH59g9pmQumlDAOkzZu7DsJrb1g/M3PEbWNIT7z3/02s8u', 'Madalina', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 14:16:24', '2025-10-24 14:16:24'),
(11, 'test@gmail.com', '$2y$12$Uml8svfVtp494FXedFOGf.DqtamV34EttSM.2X2EPMj/wWt/LYOkO', 'test', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 14:35:20', '2025-10-24 14:35:20'),
(12, 'ionadmin1@gmail.com', '$2y$12$eyK9u2cPlKDgIioWovtRwumHU0zGeoFaDlhucdGLJ4Rnt73EUtwta', 'Ion', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 14:46:26', '2025-10-24 14:46:26'),
(13, 'test1@gmail.com', '$2y$12$fMS79AxVolfSomd2g5aCYeVF9u.2Nvtn.pbRm7Q9jnkh3Jw30oVp6', 'Test Mada', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 16:26:31', '2025-10-24 16:26:31'),
(14, 'test2@gmail.com', '$2y$12$4mr2CAxmX6sWypBjr9ObjO8ytS7CQr0EvTWQ9hzMWhk.LQcCwbGNq', 'test2', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 16:54:00', '2025-10-24 16:54:00'),
(15, 'sirbu@gmail.com', '$2y$12$wfg/0RysTlRXmczmHI28gOHDgbQSOcU93wFhn/Pq48D7xmmP5Etru', 'sribu', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 18:40:31', '2025-10-24 18:40:31'),
(16, 's@gmail.com', '$2y$12$xZtqkM3Q5FasNtgEXqGWKONIpoNJybd2BPQ8Md.PPRbcYY.WSeSna', 's', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'active', '2025-10-25 11:01:25', '2025-10-25 16:38:43'),
(17, 'admin@gmail.com', '$2y$12$jwclV1jE3UzbtuDBX2VN9eevNh876dGOaw8.M8DPWx5IWX12J1r42', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, 'admin', 'active', '2025-10-25 16:40:37', '2025-10-25 16:41:03'),
(18, 'madtes@gh.com', '$2y$12$.MYztTZ7xVgR7riyV9sZxuNOMs2DL4AIXwhcLJkaBzqV7e..i53FC', 'Test', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-27 15:58:00', '2025-10-27 15:58:00'),
(19, 'test33@g.com', '$2y$12$ty4Z6qDMINnn/xrDaK7QHun0xfuhQnxXQkLQsuULPj3hNP8S61X.S', 'test33', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-28 09:55:22', '2025-10-28 09:55:22'),
(20, 'mad@g.com', '$2y$12$BMHV77lHHSxumi4DiJZpQODgC83zs4/Dp3JCyv1IANiSPCzIARdNe', 'mad', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-28 12:45:07', '2025-10-28 12:45:07'),
(21, 'mad@c.com', '$2y$12$p/0sfhFUceMhrUv/nqVXzuzrv/5tbEDp.9b6.UissWzGwirctxH.6', 'mada', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-31 08:08:17', '2025-10-31 08:08:17'),
(22, 'Mada2@gmail.com', '$2y$12$rTo9GZxGKi39LiRU8zrf8u2jYyxPSOb287MH/Z3uyCOnP2qqP4ut.', 'Mada', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-11-04 18:33:59', '2025-11-04 18:33:59');


/*==========================================================
  Indexes
==========================================================*/
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`name`);

ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `ux_categories_name` (`category_name`),
  ADD UNIQUE KEY `ux_categories_slug` (`slug`);

ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `ix_comments_picture` (`picture_id`),
  ADD KEY `ix_comments_profile` (`profile_id`),
  ADD KEY `idx_comments_parent` (`parent_comment_id`);

ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cm_profile` (`profile_id`);

ALTER TABLE `featured_pictures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_picture_week` (`picture_id`,`week_start`),
  ADD KEY `idx_week` (`week_start`),
  ADD KEY `fk_fp_admin` (`created_by`);

ALTER TABLE `likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `ux_likes_unique` (`picture_id`,`profile_id`),
  ADD KEY `ix_likes_profile` (`profile_id`);

ALTER TABLE `pages`
  ADD PRIMARY KEY (`page_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_pages_updated_by` (`updated_by`);

ALTER TABLE `pictures`
  ADD PRIMARY KEY (`picture_id`),
  ADD KEY `ix_pictures_profile` (`profile_id`),
  ADD KEY `fk_pictures_category` (`category_id`);

ALTER TABLE `picture_category`
  ADD PRIMARY KEY (`picture_id`,`category_id`),
  ADD KEY `ix_pc_category` (`category_id`);

ALTER TABLE `profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `ux_profiles_login_email` (`login_email`),
  ADD UNIQUE KEY `ux_profiles_email` (`email`),
  ADD UNIQUE KEY `uq_profiles_username` (`username`),
  ADD KEY `ix_profiles_role_status` (`role`,`status`),
  ADD KEY `ix_profiles_created_at` (`created_at`),
  ADD KEY `idx_profiles_display` (`display_name`);
ALTER TABLE `profiles` ADD FULLTEXT KEY `ft_name` (`display_name`,`username`);


/*==========================================================
  AUTO_INCREMENT
==========================================================*/
ALTER TABLE `categories`
  MODIFY `category_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `comments`
  MODIFY `comment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

ALTER TABLE `contact_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `featured_pictures`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

ALTER TABLE `likes`
  MODIFY `like_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

ALTER TABLE `pages`
  MODIFY `page_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `pictures`
  MODIFY `picture_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

ALTER TABLE `profiles`
  MODIFY `profile_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;


/*==========================================================
  Foreign Keys
==========================================================*/
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comments_picture` FOREIGN KEY (`picture_id`) REFERENCES `pictures` (`picture_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `contact_messages`
  ADD CONSTRAINT `fk_contact_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`) ON DELETE SET NULL;

ALTER TABLE `featured_pictures`
  ADD CONSTRAINT `fk_fp_admin` FOREIGN KEY (`created_by`) REFERENCES `profiles` (`profile_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_fp_picture` FOREIGN KEY (`picture_id`) REFERENCES `pictures` (`picture_id`) ON DELETE CASCADE;

ALTER TABLE `likes`
  ADD CONSTRAINT `fk_likes_picture` FOREIGN KEY (`picture_id`) REFERENCES `pictures` (`picture_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_likes_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `pages`
  ADD CONSTRAINT `fk_pages_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `profiles` (`profile_id`);

ALTER TABLE `pictures`
  ADD CONSTRAINT `fk_pictures_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pictures_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `picture_category`
  ADD CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_picture` FOREIGN KEY (`picture_id`) REFERENCES `pictures` (`picture_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
