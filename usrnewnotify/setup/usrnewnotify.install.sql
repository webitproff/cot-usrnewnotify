/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.install.sql
 * Purpose: Creates the database table for storing user registration logs.
 * Date: 2025-10-29
 * @package usrnewnotify
 * @version 2.2.2
 * @author webitproff
 * @copyright Copyright (c) webitproff 2025
 * @license BSD
 */

CREATE TABLE IF NOT EXISTS `cot_usrnewnotify_logs` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `log_user_id` INT NOT NULL,
    `log_user_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `log_user_email` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `log_ip` VARCHAR(45) COLLATE utf8mb4_unicode_ci NOT NULL,
    `log_user_agent` TEXT COLLATE utf8mb4_unicode_ci,
    `log_device` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `log_browser` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `log_country` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `log_date` DATETIME NOT NULL,
    `log_status` ENUM('success','error') COLLATE utf8mb4_unicode_ci NOT NULL,
    `log_message` TEXT COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`log_id`),
    KEY `idx_user_id` (`log_user_id`),
    KEY `idx_log_date` (`log_date`),
    KEY `idx_user_email` (`log_user_email`),
    KEY `idx_country` (`log_country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;