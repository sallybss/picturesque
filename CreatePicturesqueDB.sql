DROP DATABASE IF EXISTS PicturesqueDB;
CREATE DATABASE PicturesqueDB;
USE PicturesqueDB;
CREATE TABLE app_settings (
  name  VARCHAR(64) NOT NULL,
  value TEXT        NOT NULL,
  PRIMARY KEY (name)
);

INSERT INTO app_settings (name, value) VALUES
('brand_logo', ''),
('brand_name', 'PICTURESQUE');

CREATE TABLE profiles (
  profile_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  login_email   VARCHAR(190)    NOT NULL,
  password_hash VARCHAR(255),
  display_name  VARCHAR(100)    NOT NULL,
  username      VARCHAR(60),
  profile_info  TEXT,
  profile_bio   TEXT,
  avatar_photo  VARCHAR(255),
  cover_photo   VARCHAR(255),
  email         VARCHAR(190),
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  status        ENUM('active','blocked','banned') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (profile_id),
  UNIQUE KEY ux_profiles_login_email (login_email),
  UNIQUE KEY ux_profiles_email       (email),
  UNIQUE KEY uq_profiles_username    (username),
  KEY ix_profiles_role_status        (role, status),
  KEY ix_profiles_created_at         (created_at),
  KEY idx_profiles_display           (display_name),
  FULLTEXT KEY ft_name               (display_name, username)
);

INSERT INTO profiles
(profile_id, login_email, password_hash, display_name, username,
 profile_info, profile_bio, avatar_photo, cover_photo, email,
 role, status, created_at, updated_at)
VALUES
(1,  'sali_3006@abv.bg',       '$2y$12$DLqQEhl8f4Pd7csudVVzdewTbIw9D7lRUn2j5p0MNf2AdtJrTqucS', 'salinkaa',  NULL, NULL, NULL, 'avt_1_1762521439.png', 'cover_1_1760725028.jpg', NULL, 'admin', 'active', '2025-10-11 17:53:38', '2025-11-26 18:31:54'),
(2,  'sali_3006@abv.bgg',      '$2y$10$e8RTnhGusuDChaLJ1Bhnd.w8OZzgImYz426kznLMdWp/HJ4iH8ag6', 'salinotadmin', NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-15 20:21:22', '2025-10-21 19:27:58'),
(3,  'madalinasirbu197@gmail.com', '$2y$10$Z5V5HU20K/eZxukE4p5yeO4VUDJTSBxi4dXtdNyM66jXx5q3LbNxW', 'Madalgogoina', NULL, NULL, NULL, 'avt_3_1761072446.png', NULL, NULL, 'admin', 'active', '2025-10-21 13:43:41', '2025-10-21 18:47:50'),
(7,  'elene2004@gmail.com',    '$2y$10$U4n1f.JpNSjHsxxb3fODsON0pXiFWf7WMbqRPn1KVovFhyVEgTczS', 'elene',      NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-23 18:42:01', '2025-10-23 18:42:01'),
(8,  'elene12@gmail.com',      '$2y$12$nYksbYiGimu6lr4.4WJ8y.NKZJLd5EnvpzeO/VjwPgRI43eBePr5q', 'Elene',      NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 10:43:45', '2025-10-24 10:43:45'),
(9,  'ion@gmail.com',          '$2y$12$gf/SctKVxYhzmxzdZ6RRSeQd8EmzWU84GtX4RTjiSN0vz5X82d.qG', 'Ion',        NULL, NULL, NULL, NULL, NULL, NULL, 'admin','active', '2025-10-24 10:48:34', '2025-10-24 10:48:48'),
(10, 'madaadmin@gmail.com',    '$2y$12$4Y.gusARH59g9pmQumlDAOkzZu7DsJrb1g/M3PEbWNIT7z3/02s8u', 'Madalina',   NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 14:16:24', '2025-10-24 14:16:24'),
(11, 'test@gmail.com',         '$2y$12$Uml8svfVtp494FXedFOGf.DqtamV34EttSM.2X2EPMj/wWt/LYOkO', 'test',       NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 14:35:20', '2025-10-24 14:35:20'),
(12, 'ionadmin1@gmail.com',    '$2y$12$eyK9u2cPlKDgIioWovtRwumHU0zGeoFaDlhucdGLJ4Rnt73EUtwta', 'Ion',        NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 14:46:26', '2025-10-24 14:46:26'),
(13, 'test1@gmail.com',        '$2y$12$fMS79AxVolfSomd2g5aCYeVF9u.2Nvtn.pbRm7Q9jnkh3Jw30oVp6', 'Test Mada',  NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 16:26:31', '2025-10-24 16:26:31'),
(14, 'test2@gmail.com',        '$2y$12$4mr2CAxmX6sWypBjr9ObjO8ytS7CQr0EvTWQ9hzMWhk.LQcCwbGNq', 'test2',      NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 16:54:00', '2025-10-24 16:54:00'),
(15, 'sirbu@gmail.com',        '$2y$12$wfg/0RysTlRXmczmHI28gOHDgbQSOcU93wFhn/Pq48D7xmmP5Etru', 'sribu',      NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-24 18:40:31', '2025-10-24 18:40:31'),
(16, 's@gmail.com',            '$2y$12$xZtqkM3Q5FasNtgEXqGWKONIpoNJybd2BPQ8Md.PPRbcYY.WSeSna', 's',          NULL, NULL, NULL, NULL, NULL, NULL, 'admin','active', '2025-10-25 11:01:25', '2025-10-25 16:38:43'),
(17, 'admin@gmail.com',        '$2y$12$jwclV1jE3UzbtuDBX2VN9eevNh876dGOaw8.M8DPWx5IWX12J1r42', 'admin',      NULL, NULL, NULL, NULL, NULL, NULL, 'admin','active', '2025-10-25 16:40:37', '2025-10-25 16:41:03'),
(18, 'madtes@gh.com',          '$2y$12$.MYztTZ7xVgR7riyV9sZxuNOMs2DL4AIXwhcLJkaBzqV7e..i53FC', 'Test',       NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-27 15:58:00', '2025-10-27 15:58:00'),
(19, 'test33@g.com',           '$2y$12$ty4Z6qDMINnn/xrDaK7QHun0xfuhQnxXQkLQsuULPj3hNP8S61X.S', 'test33',     NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-28 09:55:22', '2025-10-28 09:55:22'),
(20, 'mad@g.com',              '$2y$12$BMHV77lHHSxumi4DiJZpQODgC83zs4/Dp3JCyv1IANiSPCzIARdNe', 'mad',        NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-28 12:45:07', '2025-10-28 12:45:07'),
(21, 'mad@c.com',              '$2y$12$p/0sfhFUceMhrUv/nqVXzuzrv/5tbEDp.9b6.UissWzGwirctxH.6', 'mada',       NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-10-31 08:08:17', '2025-10-31 08:08:17'),
(22, 'Mada2@gmail.com',        '$2y$12$rTo9GZxGKi39LiRU8zrf8u2jYyxPSOb287MH/Z3uyCOnP2qqP4ut.', 'Mada',       NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-11-04 18:33:59', '2025-11-04 18:33:59'),
(23, 'user@test.com',          '$2y$12$G51v7eA0bKGo.1gkdmXKBuNJ7i6xmMrl0dALn0Ph/Ze3HRndhZdHm', 'User',       NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-11-07 12:22:09', '2025-11-07 12:22:09'),
(24, 'katy@maill.com',         '$2y$12$cD8PbLM7SqRsOEmczytq6.TxVYmDv3kpkhqrpKxlXE1iYDsLv60g6', 'katy',       NULL, NULL, NULL, 'avt_24_1762521401.jpeg', NULL, NULL, 'user', 'active', '2025-11-07 13:12:33', '2025-11-07 13:16:41'),
(26, 'kt@easv.dk',             '$2y$12$5IS12/LxImuoDistNcQUHuDyYPvBWU4TpFkkHWMNRL80lJMdsF6v6', 'kim',        NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-11-11 08:02:28', '2025-11-11 08:02:28'),
(27, 'jim@gmail.com',          '$2y$12$ERBhEoDTfcxA60WjrMX8hucbnk9iVDyNHWdKuIIrlpigID2TCtXQu', 'Jim',        NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-11-18 11:25:34', '2025-11-18 11:25:34'),
(28, 'testlove@gmail.com',     '$2y$12$fMpbqDJCOE0VD6fE2/vG4eKmVMw4ZArsqq5lOX4dTlyhVdqKY1qde', 'testlive',   NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-11-22 09:32:20', '2025-11-22 09:32:20'),
(29, 'hey@gmail.com',          '$2y$12$mLyvQgv6Ei/WpqqXqcu2B.6qYFeQwxftKgm8K1O/AYeU8Js0.ZUx6', 'hey',        NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-11-22 09:34:58', '2025-11-22 09:34:58'),
(30, 'heyy@gmail.com',         '$2y$12$ESFdT4Gp19ci./W6Wi5W0ulYAnIiLhVnbK40uMc9k42Pwor/iSpa2', 'Heyyy',      NULL, NULL, NULL, NULL, NULL, NULL, 'user', 'active', '2025-11-23 16:21:42', '2025-11-23 16:21:42');


CREATE TABLE categories (
  category_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_name VARCHAR(100) NOT NULL,
  slug          VARCHAR(40)  NOT NULL,
  active        TINYINT(1)   NOT NULL DEFAULT 1,

  PRIMARY KEY (category_id),
  UNIQUE KEY ux_categories_name (category_name),
  UNIQUE KEY ux_categories_slug (slug)
);

INSERT INTO categories (category_id, category_name, slug, active) VALUES
(1, 'Landscape', 'landscape', 1),
(2, 'Abstract',  'abstract',  1),
(5, 'Portrait',  'portrait',  1),
(6, 'English',   'english',   1);


CREATE TABLE pages (
  page_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug       VARCHAR(64)  NOT NULL,
  title      VARCHAR(200) NOT NULL,
  content    MEDIUMTEXT   NOT NULL,
  image_path VARCHAR(255),
  updated_by BIGINT UNSIGNED,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
             ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (page_id),
  UNIQUE KEY ux_pages_slug (slug),
  KEY fk_pages_updated_by (updated_by),
  CONSTRAINT fk_pages_updated_by
    FOREIGN KEY (updated_by) REFERENCES profiles(profile_id)
);

INSERT INTO pages (page_id, slug, title, content, image_path, updated_by, updated_at) VALUES
(1, 'about', 'About Picturesque',
 'Picturesque is a community-driven platform where landscape photographers share breathtaking views from around the world!!fbdhthujykmyuk hnyn',
 'about_1760727070_155390f5.jpg', 3, '2025-10-21 19:28:02'),
(3, 'rules', 'Rules & Regulations',
 'Rules & Regulations\r\n\r\nWelcome to Picturesque, a creative space for sharing and appreciating landscape photography.\r\nTo keep our community inspiring, respectful, and safe for everyone, please read and follow the guidelines below.\r\n\r\n\r\n1. Respect and Conduct\r\nTreat all members with respect. Harassment, hate speech, or discrimination of any kind will not be tolerated.\r\nProvide constructive feedback, celebrate creativity, and avoid personal attacks or negativity.\r\nDo not impersonate others or post misleading information.\r\n\r\n2. Content Guidelines\r\nUpload only images that you have created or own the rights to.\r\nDo not post copyrighted material, offensive content, or images that violate privacy or depict violence.\r\nEach photo should include a suitable title, category, and if applicable, a short description.\r\nAvoid excessive watermarks or promotional overlays that distract from the artwork.\r\n\r\n3. Categories and Tagging\r\nUse accurate categories and tags to help others discover your work.\r\nMisleading or unrelated tagging may result in content removal.\r\n\r\n4. Community Interaction\r\nEngage positively through likes, comments, and discussions.\r\nSpamming, self-promotion, or mass posting for attention is discouraged.\r\nReport any inappropriate behavior or content through the contact or report options.\r\n\r\n5. Privacy and Security\r\nDo not share personal data publicly in comments, photos, or captions.\r\nRespect the privacy of other users and any subjects appearing in photos.\r\n\r\n\r\n6. Moderation and Enforcement\r\nThe Picturesque team reserves the right to review, edit, or remove content that violates these rules.\r\nRepeated or serious violations may result in account suspension or permanent removal.\r\n\r\n\r\n7. Updates\r\nThese Rules and Regulations may be updated periodically. Continued use of the platform after updates constitutes acceptance of the revised terms.',
 NULL, 1, '2025-10-31 08:11:05');


CREATE TABLE pictures (
  picture_id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id          BIGINT UNSIGNED NOT NULL,
  picture_title       VARCHAR(150) NOT NULL,
  picture_description TEXT,
  picture_url         VARCHAR(255) NOT NULL,
  category_id         INT UNSIGNED DEFAULT NULL,
  likes_count         INT UNSIGNED NOT NULL DEFAULT 0,
  visibility          ENUM('public','hidden') NOT NULL DEFAULT 'public',
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (picture_id),
  KEY ix_pictures_profile (profile_id),
  KEY fk_pictures_category (category_id),
  CONSTRAINT fk_pictures_profile
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id),
  CONSTRAINT fk_pictures_category
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

INSERT INTO pictures
(picture_id, profile_id, picture_title, picture_description,
 picture_url, category_id, likes_count, visibility,
 created_at, updated_at)
VALUES
(4,  1,  'Picture',      '', '1760725262_664f36ce.jpg',          NULL, 0, 'public', '2025-10-17 18:21:02', '2025-10-17 18:21:02'),
(5,  1,  'Nice',         '', '1760725271_ad85bbb2.jpg',          NULL, 2, 'public', '2025-10-17 18:21:11', '2025-10-17 18:21:11'),
(6,  1,  'Autumn',       '', '1760725287_b82a83f3.jpg',          NULL, 1, 'public', '2025-10-17 18:21:27', '2025-10-17 18:21:27'),
(7,  1,  'Waterfall',    '', '1760725319_6b4d5bac.jpg',          NULL, 0, 'public', '2025-10-17 18:21:59', '2025-10-17 18:21:59'),
(8,  1,  'trees',        '', '1760725336_1dc26765.jpg',          NULL, 2, 'public', '2025-10-17 18:22:16', '2025-10-17 18:22:16'),
(9,  1,  'clouds',       '', '1760725350_45e713e4.jpg',          NULL, 1, 'public', '2025-10-17 18:22:30', '2025-10-17 18:22:30'),
(10, 1,  'waterfall2',   '', '1760725374_b3b1e3e0.jpg',          NULL, 1, 'public', '2025-10-17 18:22:54', '2025-10-17 18:22:54'),
(11, 1,  'Autumn fall',  '', '1760732987_f3ee9f9c.jpg',          NULL, 0, 'public', '2025-10-17 20:29:47', '2025-10-17 20:29:47'),
(12, 1,  'tram',         '', '1760733108_67e5aa71.jpg',          NULL, 2, 'public', '2025-10-17 20:31:48', '2025-10-17 20:31:48'),
(50, 24, 'Nature',       '', '/uploads/1762521257_0dcd8236.jpg', 1,    0, 'public', '2025-11-07 13:14:17', '2025-11-07 13:14:17'),
(51, 24, 'Sea',          '', '/uploads/1762521271_898864cc.jpg', 1,    0, 'public', '2025-11-07 13:14:31', '2025-11-07 13:14:31'),
(52, 24, 'Endless Green','', '/uploads/1762521289_23522c43.jpg', 1,    0, 'public', '2025-11-07 13:14:49', '2025-11-07 13:14:49'),
(53, 24, 'Cloudy',       '', '/uploads/1762521312_9d1808ed.jpg', 1,    0, 'public', '2025-11-07 13:15:12', '2025-11-07 13:15:12');


CREATE TABLE picture_category (
  picture_id  BIGINT UNSIGNED NOT NULL,
  category_id INT UNSIGNED    NOT NULL,
  PRIMARY KEY (picture_id, category_id),
  KEY ix_pc_category (category_id),
  CONSTRAINT fk_pc_picture
    FOREIGN KEY (picture_id) REFERENCES pictures(picture_id),
  CONSTRAINT fk_pc_category
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

CREATE TABLE contact_messages (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  profile_id BIGINT UNSIGNED DEFAULT NULL,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(190) NOT NULL,
  company    VARCHAR(190),
  subject    VARCHAR(190) NOT NULL,
  message    TEXT NOT NULL,
  ip         VARCHAR(45),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_cm_profile (profile_id),
  CONSTRAINT fk_contact_profile
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id)
);

INSERT INTO contact_messages
(id, profile_id, name, email, company, subject, message, ip, created_at)
VALUES
(1, 1, 'test', 'sali_3006@abv.bg', 'test', 'test',
 'Just a test', '::1', '2025-10-17 20:57:56'),
(2, 1, 'salinkaa', 'sali_3006@abv.bg', '', 'test2',
 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
 '::1', '2025-10-17 21:01:11'),
(3, 1, 'salinkaa', '', 'Maersk', 'Test',
 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
 '91.101.59.75', '2025-11-25 17:00:31');


CREATE TABLE login_attempts (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email      VARCHAR(190) NOT NULL,
  ip_address VARCHAR(45),
  success    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_email_created (email, created_at),
  KEY idx_ip_created    (ip_address, created_at)
);

INSERT INTO login_attempts (id, email, ip_address, success, created_at) VALUES
(1, 'hey@gmail.com', '212.97.236.2', 1, '2025-11-28 11:12:00');


CREATE TABLE password_resets (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY fk_password_resets_user (user_id),
  KEY idx_password_resets_token (token_hash),
  CONSTRAINT fk_password_resets_user
    FOREIGN KEY (user_id) REFERENCES profiles(profile_id)
);

INSERT INTO password_resets
(id, user_id, token_hash, expires_at, used_at, created_at)
VALUES
(2, 1, 'efde06d9f150eef065d7aadd93dec4098848f2e9183d8dc1fe015d12b7dee535',
 '2025-11-27 15:12:04', NULL, '2025-11-27 14:12:04');


CREATE TABLE likes (
  like_id    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  picture_id BIGINT UNSIGNED NOT NULL,
  profile_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (like_id),
  UNIQUE KEY ux_likes_unique (picture_id, profile_id),
  KEY ix_likes_profile (profile_id),
  CONSTRAINT fk_likes_picture
    FOREIGN KEY (picture_id) REFERENCES pictures(picture_id),
  CONSTRAINT fk_likes_profile
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id)
);

INSERT INTO likes (like_id, picture_id, profile_id, created_at) VALUES
(6, 10, 1, '2025-10-17 18:51:58'),
(7, 8,  1, '2025-10-17 18:51:59'),
(8, 12, 1, '2025-10-18 21:46:55'),
(9, 5,  1, '2025-10-18 21:46:58'),
(11, 8,  3, '2025-10-21 15:23:24'),
(15, 9,  3, '2025-10-21 17:10:24'),
(16, 6,  3, '2025-10-21 17:10:25'),
(18, 12, 3, '2025-10-21 17:10:33'),
(22, 5,  3, '2025-10-21 19:09:23');

CREATE TABLE comments (
  comment_id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  picture_id        BIGINT UNSIGNED NOT NULL,
  profile_id        BIGINT UNSIGNED NOT NULL,
  parent_comment_id BIGINT UNSIGNED DEFAULT NULL,
  comment_content   TEXT NOT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (comment_id),
  KEY ix_comments_picture (picture_id),
  KEY ix_comments_profile (profile_id),
  KEY idx_comments_parent (parent_comment_id),

  CONSTRAINT fk_comments_picture
    FOREIGN KEY (picture_id) REFERENCES pictures(picture_id),
  CONSTRAINT fk_comments_profile
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id),
  CONSTRAINT fk_comments_parent
    FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id)
);

INSERT INTO comments
(comment_id, picture_id, profile_id, parent_comment_id,
 comment_content, created_at)
VALUES
(5,  12, 1, NULL, 'something', '2025-10-21 07:36:14'),
(6,  12, 1, 5,    'something', '2025-10-21 07:36:19'),
(20, 10, 3, NULL, 'ghng h',    '2025-10-23 18:21:05'),
(21, 10, 3, NULL, 'hnhyyn',    '2025-10-23 18:30:54'),
(22, 10, 3, NULL, 'dbrthtrh',  '2025-10-23 18:36:04'),
(23, 10, 3, NULL, 'fbrthrthtyhtyj', '2025-10-23 18:40:25'),
(31, 53, 1, NULL, '1', '2025-11-27 12:43:13'),
(32, 53, 1, NULL, '2', '2025-11-27 12:43:15'),
(35, 53, 1, NULL, '1', '2025-11-27 13:21:53'),
(36, 53, 1, NULL, '1', '2025-11-27 13:21:55');


CREATE TABLE featured_pictures (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  picture_id BIGINT UNSIGNED NOT NULL,
  week_start DATE NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uk_picture_week (picture_id, week_start),
  KEY idx_week   (week_start),
  KEY fk_fp_admin (created_by),
  CONSTRAINT fk_fp_admin
    FOREIGN KEY (created_by) REFERENCES profiles(profile_id),
  CONSTRAINT fk_fp_picture
    FOREIGN KEY (picture_id) REFERENCES pictures(picture_id)
);

INSERT INTO featured_pictures
(id, picture_id, week_start, created_by, created_at)
VALUES
(2,  12, '2025-10-20', 17, '2025-10-25 16:43:49'),
(4,   6, '2025-10-20', 17, '2025-10-25 16:49:50'),
(6,   9, '2025-10-20', 1,  '2025-10-26 11:17:34'),
(7,   8, '2025-10-20', 1,  '2025-10-26 11:27:25'),
(10, 11, '2025-10-27', 1,  '2025-10-27 16:05:37'),
(11,  7, '2025-10-27', 1,  '2025-10-27 16:05:42'),
(13, 53, '2025-11-03', 1,  '2025-11-07 13:17:23'),
(14,  5, '2025-11-03', 1,  '2025-11-07 13:17:26'),
(15, 51, '2025-11-03', 1,  '2025-11-07 13:17:29'),
(16,  4, '2025-11-03', 1,  '2025-11-07 13:17:34'),
(17,  8, '2025-11-03', 1,  '2025-11-07 13:17:40'),
(18, 53, '2025-11-24', 1,  '2025-11-25 16:49:32'),
(19,  9, '2025-11-24', 1,  '2025-11-25 16:49:34'),
(20, 52, '2025-11-24', 1,  '2025-11-25 16:49:37'),
(21, 11, '2025-11-24', 1,  '2025-11-25 16:49:39'),
(22, 51, '2025-11-24', 1,  '2025-11-25 16:49:41');


--  VIEWS
CREATE OR REPLACE VIEW view_picture_overview AS
SELECT
  p.picture_id,
  p.picture_title,
  p.picture_url,
  p.likes_count,
  COUNT(DISTINCT c.comment_id) AS comment_count,
  pr.display_name              AS author_name,
  pr.profile_id                AS author_id,
  cat.category_name
FROM pictures p
JOIN profiles pr      ON pr.profile_id = p.profile_id
LEFT JOIN categories cat ON cat.category_id = p.category_id
LEFT JOIN comments   c   ON c.picture_id = p.picture_id
WHERE p.visibility = 'public'
GROUP BY p.picture_id;

CREATE OR REPLACE VIEW view_active_profiles AS
SELECT
  pr.profile_id,
  pr.display_name,
  pr.role,
  pr.status,
  COUNT(DISTINCT pic.picture_id) AS picture_count,
  COUNT(DISTINCT com.comment_id) AS comment_count
FROM profiles pr
LEFT JOIN pictures pic
       ON pic.profile_id = pr.profile_id
      AND pic.visibility = 'public'
LEFT JOIN comments com
       ON com.profile_id = pr.profile_id
WHERE pr.status = 'active'
GROUP BY pr.profile_id;

--  TRIGGERS

DELIMITER $$
-- When a like is created, increment likes_count
CREATE TRIGGER trg_likes_after_insert
AFTER INSERT ON likes
FOR EACH ROW
BEGIN
  UPDATE pictures
  SET likes_count = likes_count + 1
  WHERE picture_id = NEW.picture_id;
END$$

-- When a like is removed, decrement likes_count (min 0)
CREATE TRIGGER trg_likes_after_delete
AFTER DELETE ON likes
FOR EACH ROW
BEGIN
  UPDATE pictures
  SET likes_count = IF(likes_count > 0, likes_count - 1, 0)
  WHERE picture_id = OLD.picture_id;
END$$

-- When a profile is blocked/banned, hide all their pictures
CREATE TRIGGER trg_profiles_after_update
AFTER UPDATE ON profiles
FOR EACH ROW
BEGIN
  IF NEW.status IN ('blocked','banned')
     AND OLD.status <> NEW.status THEN
    UPDATE pictures
    SET visibility = 'hidden'
    WHERE profile_id = NEW.profile_id;
  END IF;
END$$

DELIMITER ;
