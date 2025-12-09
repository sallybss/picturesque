DROP DATABASE IF EXISTS picturesque_dk_db;
CREATE DATABASE picturesque_dk_db;
USE picturesque_dk_db;



-- 1. CREATE TABLES 

CREATE TABLE profiles (
    profile_id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login_email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash       VARCHAR(255) NULL,
    display_name        VARCHAR(100) NOT NULL,
    username            VARCHAR(60) UNIQUE,
    profile_info        TEXT NULL,
    profile_bio         TEXT NULL,
    avatar_photo        VARCHAR(255) NULL,
    cover_photo         VARCHAR(255) NULL,
    email               VARCHAR(190) UNIQUE,
    role                ENUM('user','admin') NOT NULL DEFAULT 'user',
    status              ENUM('active','blocked','banned') NOT NULL DEFAULT 'active',
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: categories
CREATE TABLE categories (
    category_id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_name       VARCHAR(100) NOT NULL UNIQUE,
    slug                VARCHAR(40) NOT NULL UNIQUE,
    active              TINYINT(1) NOT NULL DEFAULT '1'
);

-- Table: pictures
CREATE TABLE pictures (
    picture_id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    profile_id          BIGINT UNSIGNED NOT NULL, 
    picture_title       VARCHAR(150) NOT NULL,
    picture_description TEXT NULL,
    picture_url         VARCHAR(255) NOT NULL,
    category_id         INT UNSIGNED NULL, 
    likes_count         INT UNSIGNED NOT NULL DEFAULT '0',
    visibility          ENUM('public','hidden') NOT NULL DEFAULT 'public',
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (profile_id) REFERENCES profiles (profile_id),
    FOREIGN KEY (category_id) REFERENCES categories (category_id)
);

-- Table: comments
CREATE TABLE comments ( 
    comment_id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    picture_id          BIGINT UNSIGNED NOT NULL, 
    profile_id          BIGINT UNSIGNED NOT NULL, 
    parent_comment_id   BIGINT UNSIGNED DEFAULT NULL, 
    comment_content     TEXT NOT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (picture_id) REFERENCES pictures (picture_id),
    FOREIGN KEY (profile_id) REFERENCES profiles (profile_id),
    FOREIGN KEY (parent_comment_id) REFERENCES comments (comment_id)
);

-- Table: likes 
CREATE TABLE likes (
    like_id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    picture_id          BIGINT UNSIGNED NOT NULL, 
    profile_id          BIGINT UNSIGNED NOT NULL, 
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE ux_likes_unique (picture_id, profile_id),
    FOREIGN KEY (picture_id) REFERENCES pictures (picture_id),
    FOREIGN KEY (profile_id) REFERENCES profiles (profile_id)
);

-- Table: featured_pictures
CREATE TABLE featured_pictures (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    picture_id          BIGINT UNSIGNED NOT NULL, 
    week_start          DATE NOT NULL,
    created_by          BIGINT UNSIGNED NOT NULL, 
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE uk_picture_week (picture_id, week_start),
    FOREIGN KEY (picture_id) REFERENCES pictures (picture_id),
    FOREIGN KEY (created_by) REFERENCES profiles (profile_id)
);

-- Table: picture_category 
CREATE TABLE picture_category (
    picture_id          BIGINT UNSIGNED NOT NULL,
    category_id         INT UNSIGNED NOT NULL, 

    PRIMARY KEY (picture_id, category_id),
    FOREIGN KEY (picture_id) REFERENCES pictures (picture_id),
    FOREIGN KEY (category_id) REFERENCES categories (category_id)
);

-- Table: contact_messages
CREATE TABLE contact_messages (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    profile_id          BIGINT UNSIGNED DEFAULT NULL, 
    name                VARCHAR(120) NOT NULL,
    email               VARCHAR(190) NOT NULL,
    company             VARCHAR(190) DEFAULT NULL,
    subject             VARCHAR(190) NOT NULL,
    message             TEXT NOT NULL,
    ip                  VARCHAR(45) DEFAULT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (profile_id) REFERENCES profiles (profile_id)
);

-- Table: login_attempts
CREATE TABLE login_attempts (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(190) NOT NULL,
    ip_address          VARCHAR(45) DEFAULT NULL,
    success             TINYINT(1) NOT NULL DEFAULT '0',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Table: pages
CREATE TABLE pages (
    page_id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug                VARCHAR(64) NOT NULL UNIQUE,
    title               VARCHAR(200) NOT NULL,
    content             MEDIUMTEXT NOT NULL,
    image_path          VARCHAR(255) DEFAULT NULL,
    updated_by          BIGINT UNSIGNED DEFAULT NULL,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (updated_by) REFERENCES profiles (profile_id)
);

-- Table: password_resets
CREATE TABLE password_resets (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL, 
    token_hash          CHAR(64) NOT NULL,
    expires_at          DATETIME NOT NULL,
    used_at             DATETIME DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES profiles (profile_id)
);

-- Table: app_settings
CREATE TABLE app_settings (
    name                VARCHAR(64) NOT NULL PRIMARY KEY,
    value               TEXT NOT NULL
);


-- 2. TRIGGERS 

-- Trigger 1: trg_likes_after_insert (Updates likes_count on picture insert)
DELIMITER $$
CREATE TRIGGER trg_likes_after_insert
AFTER INSERT ON likes FOR EACH ROW
BEGIN
  UPDATE pictures
  SET likes_count = likes_count + 1
  WHERE picture_id = NEW.picture_id;
END$$
DELIMITER ;


-- Trigger 2: trg_likes_after_delete (Updates likes_count on picture delete/unlike)
DELIMITER $$
CREATE TRIGGER trg_likes_after_delete
AFTER DELETE ON likes FOR EACH ROW
BEGIN
  UPDATE pictures
  SET likes_count = IF(likes_count > 0, likes_count - 1, 0)
  WHERE picture_id = OLD.picture_id;
END$$
DELIMITER ;

-- Trigger 3: trg_profiles_after_update (Hides pictures when profile is blocked/banned)
DELIMITER $$
CREATE TRIGGER trg_profiles_after_update
AFTER UPDATE ON profiles FOR EACH ROW
BEGIN
  IF NEW.status IN ('blocked','banned')
     AND OLD.status <> NEW.status THEN
    UPDATE pictures
    SET visibility = 'hidden'
    WHERE profile_id = NEW.profile_id;
  END IF;
END$$
DELIMITER ;


-- 3. VIEWS 

-- View: view_active_profiles
CREATE VIEW view_active_profiles AS 
SELECT
    pr.profile_id,
    pr.display_name,
    pr.role,
    pr.status,
    COUNT(DISTINCT pic.picture_id) AS picture_count,
    COUNT(DISTINCT com.comment_id) AS comment_count
FROM
    profiles pr
LEFT JOIN
    pictures pic ON pic.profile_id = pr.profile_id AND pic.visibility = 'public'
LEFT JOIN
    comments com ON com.profile_id = pr.profile_id
WHERE
    pr.status = 'active'
GROUP BY
    pr.profile_id;


-- View: view_picture_overview
CREATE VIEW view_picture_overview AS 
SELECT
    p.picture_id,
    p.picture_title,
    p.picture_url,
    p.likes_count,
    COUNT(DISTINCT c.comment_id) AS comment_count,
    pr.display_name AS author_name,
    pr.profile_id AS author_id,
    cat.category_name
FROM
    pictures p
JOIN
    profiles pr ON pr.profile_id = p.profile_id
LEFT JOIN
    categories cat ON cat.category_id = p.category_id
LEFT JOIN
    comments c ON c.picture_id = p.picture_id
WHERE
    p.visibility = 'public'
GROUP BY
    p.picture_id;