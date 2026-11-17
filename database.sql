-- ========================================================
-- Fuel Pump Availability Tracker (তেল দিচ্ছে?)
-- Database Schema for MySQL / MariaDB
-- Character Set: utf8mb4 / utf8mb4_unicode_ci
-- ========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Table structure for `settings`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT PRIMARY KEY DEFAULT 1,
    `site_name` VARCHAR(255) DEFAULT 'তেল দিচ্ছে?',
    `site_desc` VARCHAR(255) DEFAULT 'কোথায় তেল পাওয়া যাচ্ছে জানুন - জ্বালানি তথ্য সেবা',
    `contact_email` VARCHAR(255) DEFAULT 'hello@example.com',
    `copyright_year` VARCHAR(20) DEFAULT '২০২৬',
    `creator_name` VARCHAR(255) DEFAULT 'মুজাহিদুল ইসলাম',
    `facebook_link` VARCHAR(255) DEFAULT '',
    `promo_text` TEXT NULL,
    `promo_link` VARCHAR(255) NULL,
    `ip_restriction` TINYINT(1) DEFAULT 1,
    `daily_vote_limit` INT DEFAULT 5,
    `daily_comment_limit` INT DEFAULT 5,
    `total_visits` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`id`, `site_name`, `site_desc`) VALUES 
(1, 'তেল দিচ্ছে?', 'কোথায় তেল পাওয়া যাচ্ছে জানুন - জ্বালানি তথ্য সেবা');

-- --------------------------------------------------------
-- 2. Table structure for `pumps`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pumps` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `has_fuel` TINYINT(1) DEFAULT 1,
    `petrol` TINYINT(1) DEFAULT 0,
    `octane` TINYINT(1) DEFAULT 0,
    `diesel` TINYINT(1) DEFAULT 0,
    `fuel_limit` VARCHAR(100) DEFAULT NULL,
    `map_link` TEXT DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `added_by` VARCHAR(100) DEFAULT NULL,
    `likes` INT DEFAULT 0,
    `dislikes` INT DEFAULT 0,
    `report_count` INT DEFAULT 0,
    `view_count` INT DEFAULT 0,
    `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Table structure for `comments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pump_id` INT NOT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `author` VARCHAR(255) DEFAULT 'অজ্ঞাত',
    `comment_text` TEXT NOT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_comments_pump` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Table structure for `pump_votes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pump_votes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pump_id` INT NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `vote_type` VARCHAR(10) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_votes_pump` FOREIGN KEY (`pump_id`) REFERENCES `pumps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
