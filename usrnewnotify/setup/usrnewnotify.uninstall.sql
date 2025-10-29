/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.uninstall.sql
 * Purpose: Drops the database table for user registration logs during plugin uninstallation.
 * Date: 2025-10-29
 * @package usrnewnotify
 * @version 2.2.2
 * @author webitproff
 * @copyright Copyright (c) webitproff 2025
 * @license BSD
 */

DROP TABLE IF EXISTS `cot_usrnewnotify_logs`;