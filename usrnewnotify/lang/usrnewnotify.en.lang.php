<?php
/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.en.lang.php
 * Purpose: English localization for the User New Notify plugin. Defines email templates for registration and validation, and admin interface strings.
 * Date: 2025-10-29
 * @package usrnewnotify
 * @version 2.2.8
 * @author webitproff
 * @copyright Copyright (c) webitproff 2025
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL.');

/**
 * Plugin Config
 */
$L['cfg_notify_enabled'] = 'Enable email notifications';
$L['cfg_notify_email'] = 'Administrator email address(es) for notifications (comma-separated)';
$L['cfg_notify_format'] = 'Notification format (text, html)';
$L['cfg_notify_log'] = 'Enable logging to the database, table usrnewnotify_logs';

/**
 * Plugin Info
 */
$L['info_name'] = 'User New Notify';
$L['info_desc'] = 'Notifies the site administrator via email about new user registration or confirmation';
$L['info_notes'] = 'Requires PHP 8.4+, MySQL 8.0+, Cotonti Siena v.0.9.26. Includes support for HTML emails, database logging, admin tools, and country detection.';
$L['usrnewnotify_subject'] = 'New User Registration';
$L['usrnewnotify_admin_title'] = 'User Registration Logs';

// Text template for registration (without email confirmation)
$L['send_to_adminmail_message'] =
"Hello, Administrator! A user has registered but has not yet confirmed their email.
A new user has registered on the site MAINTITLE (MAINURL): USER_NAME.
**Registration Details:**
- **Contact Email**: USER_EMAIL
- **Registration Date**: USER_REGDATE
- **User Profile**: USER_PROFILE_URL
- **IP Address**: IP_VISITOR
- **Country**: USER_COUNTRY
- **Device**: USER_DEVICE
- **Browser**: USER_BROWSER";

// Text template for email confirmation
$L['send_to_adminmail_message_validated'] =
"Hello, Administrator! A user has successfully registered and confirmed their email!
A new user has registered and confirmed their email on the site MAINTITLE (MAINURL): USER_NAME.
**Registration Details:**
- **Contact Email**: USER_EMAIL
- **Registration Date**: USER_REGDATE
- **User Profile**: USER_PROFILE_URL
- **IP Address**: IP_VISITOR
- **Country**: USER_COUNTRY
- **Device**: USER_DEVICE
- **Browser**: USER_BROWSER";

// HTML template for registration (without email confirmation)
$L['send_to_adminmail_html_message'] =
'<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        h2 { color: #2c3e50; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>New User Registration</h2>
    <p>Hello, Administrator! A user has registered but has not yet confirmed their email.</p>
    <p>A new user has registered on the site <a href="MAINURL">MAINTITLE</a>: <strong>USER_NAME</strong>.</p>
    <table>
        <tr><th>Contact Email</th><td>USER_EMAIL</td></tr>
        <tr><th>Registration Date</th><td>USER_REGDATE</td></tr>
        <tr><th>User Profile</th><td><a href="USER_PROFILE_URL">View Profile</a></td></tr>
        <tr><th>IP Address</th><td>IP_VISITOR</td></tr>
        <tr><th>Country</th><td>USER_COUNTRY</td></tr>
        <tr><th>Device</th><td>USER_DEVICE</td></tr>
        <tr><th>Browser</th><td>USER_BROWSER</td></tr>
    </table>
</body>
</html>';

// HTML template for email confirmation
$L['send_to_adminmail_html_message_validated'] =
'<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        h2 { color: #2c3e50; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h2>User Registration Confirmed</h2>
    <p>Hello, Administrator! A user has successfully registered and confirmed their email!</p>
    <p>A new user has registered and confirmed their email on the site <a href="MAINURL">MAINTITLE</a>: <strong>USER_NAME</strong>.</p>
    <table>
        <tr><th>Contact Email</th><td>USER_EMAIL</td></tr>
        <tr><th>Registration Date</th><td>USER_REGDATE</td></tr>
        <tr><th>User Profile</th><td><a href="USER_PROFILE_URL">View Profile</a></td></tr>
        <tr><th>IP Address</th><td>IP_VISITOR</td></tr>
        <tr><th>Country</th><td>USER_COUNTRY</td></tr>
        <tr><th>Device</th><td>USER_DEVICE</td></tr>
        <tr><th>Browser</th><td>USER_BROWSER</td></tr>
    </table>
</body>
</html>';

$L['user_profile_link'] = 'Link to profile';
$L['Success'] = 'Success';
$L['Error'] = 'Error';
$L['ID'] = 'ID';
$L['User'] = 'User';
$L['Email'] = 'Email';
$L['IP'] = 'IP';
$L['Country'] = 'Country';
$L['Device'] = 'Device';
$L['Browser'] = 'Browser';
$L['Date'] = 'Date';
$L['Status'] = 'Status';
$L['Message'] = 'Message';
$L['All'] = 'All';
$L['Filter'] = 'Filter';
$L['DateFrom'] = 'Date From';
$L['DateTo'] = 'Date To';