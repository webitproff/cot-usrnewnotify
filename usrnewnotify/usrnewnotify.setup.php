<?php
/* ====================
[BEGIN_COT_EXT]
Code=usrnewnotify
Name=User New Notify
Category=auth-notifications
Description=Notifies the site administrator via email when a new user registers or validates registration, including detailed user information, profile link, device, browser, and country info. Supports HTML/text notifications, database logging, and an admin interface with filtering and search.
Version=2.2.8
Date=2025-10-29
Author=webitproff
Copyright=Copyright (c) webitproff 2025 https://github.com/webitproff/cot-usrnewnotify
Notes=Requires PHP 8.4+, MySQL 8.0+, Cotonti Siena v.0.9.26. Includes HTML email support, database logging, admin tools, and country detection. Uses dynamic hooks based on Cot::$cfg['users']['regnoactivation'].
SQL=usrnewnotify.install.sql
UninstallSQL=usrnewnotify.uninstall.sql
Auth_guests=R
Lock_guests=12345A
Auth_members=RW
Lock_members=12345A
Hooks=global,tools
[END_COT_EXT]
[BEGIN_COT_EXT_CONFIG]
notify_enabled=01:radio:0:1:Enable email notifications
notify_email=02:text:::Admin email(s) for notifications (comma-separated)
notify_format=03:select:text,html:html:Notification format
notify_log=04:radio:0:1:Enable logging to database
[END_COT_EXT_CONFIG]
==================== */

/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.setup.php
 * Purpose: Registers metadata and configuration for the User New Notify plugin in the Cotonti admin panel. Dynamically adjusts hooks based on user module settings.
 * Date: 2025-10-29
 * @package usrnewnotify
 * @version 2.2.2
 * @author webitproff
 * @copyright Copyright (c) webitproff 2025 https://github.com/webitproff/cot-usrnewnotify
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');