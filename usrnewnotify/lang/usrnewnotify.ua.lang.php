<?php
/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.ua.lang.php
 * Purpose: Ukrainian localization for the User New Notify plugin. Defines email templates for registration and validation, and admin interface strings.
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
$L['cfg_notify_enabled'] = 'Увімкнути сповіщення електронною поштою';
$L['cfg_notify_email'] = 'Адреса(и) електронної пошти адміністратора для сповіщень (через кому)';
$L['cfg_notify_format'] = 'Формат сповіщень (text, html)';
$L['cfg_notify_log'] = 'Увімкнути ведення журналу в базі даних, таблиця usrnewnotify_logs';

/**
 * Plugin Info
 */
$L['info_name'] = 'User New Notify';
$L['info_desc'] = 'Сповіщає адміністратора сайту електронною поштою про реєстрацію або підтвердження реєстрації нового користувача';
$L['info_notes'] = 'Потрібні PHP 8.4+, MySQL 8.0+, Cotonti Siena v.0.9.26. Включає підтримку HTML-пошти, ведення журналу бази даних, інструменти адміністрування та визначення країни.';
$L['usrnewnotify_subject'] = 'Реєстрація нового користувача';
$L['usrnewnotify_admin_title'] = 'Журнал реєстрацій користувачів';

// Текстовый шаблон для реєстрації (без підтвердження email)
$L['send_to_adminmail_message'] =
"Доброго дня, Administrator! Користувач зареєструвався, але ще не підтвердив реєстрацію через свою пошту
На сайті MAINTITLE (MAINURL) зареєструвався новий користувач: USER_NAME.
**Деталі реєстрації:**
- **Контактна email**: USER_EMAIL
- **Дата реєстрації**: USER_REGDATE
- **Профіль користувача**: USER_PROFILE_URL
- **IP-адреса**: IP_VISITOR
- **Країна**: USER_COUNTRY
- **Пристрій**: USER_DEVICE
- **Браузер**: USER_BROWSER";

// Текстовый шаблон для підтвердження email
$L['send_to_adminmail_message_validated'] =
"Доброго дня, Administrator! Користувач успішно зареєструвався та підтвердив свою пошту!
На сайті MAINTITLE (MAINURL) зареєструвався та підтвердив email новий користувач: USER_NAME.
**Деталі реєстрації:**
- **Контактна email**: USER_EMAIL
- **Дата реєстрації**: USER_REGDATE
- **Профіль користувача**: USER_PROFILE_URL
- **IP-адреса**: IP_VISITOR
- **Країна**: USER_COUNTRY
- **Пристрій**: USER_DEVICE
- **Браузер**: USER_BROWSER";

// HTML-шаблон для реєстрації (без підтвердження email)
$L['send_to_adminmail_html_message'] =
'<!DOCTYPE html>
<html lang="uk">
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
    <h2>Реєстрація нового користувача</h2>
    <p>Доброго дня, Administrator! Користувач зареєструвався, але ще не підтвердив реєстрацію через свою пошту</p>
    <p>На сайті <a href="MAINURL">MAINTITLE</a> зареєструвався новий користувач: <strong>USER_NAME</strong>.</p>
    <table>
        <tr><th>Контактна email</th><td>USER_EMAIL</td></tr>
        <tr><th>Дата реєстрації</th><td>USER_REGDATE</td></tr>
        <tr><th>Профіль користувача</th><td><a href="USER_PROFILE_URL">Перейти до профілю</a></td></tr>
        <tr><th>IP-адреса</th><td>IP_VISITOR</td></tr>
        <tr><th>Країна</th><td>USER_COUNTRY</td></tr>
        <tr><th>Пристрій</th><td>USER_DEVICE</td></tr>
        <tr><th>Браузер</th><td>USER_BROWSER</td></tr>
    </table>
</body>
</html>';

// HTML-шаблон для листа після підтвердження email
$L['send_to_adminmail_html_message_validated'] =
'<!DOCTYPE html>
<html lang="uk">
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
    <h2>Підтвердження реєстрації користувача</h2>
    <p>Доброго дня, Administrator! Користувач успішно зареєструвався та підтвердив свою пошту!</p>
    <p>На сайті <a href="MAINURL">MAINTITLE</a> зареєструвався та підтвердив email новий користувач: <strong>USER_NAME</strong>.</p>
    <table>
        <tr><th>Контактна email</th><td>USER_EMAIL</td></tr>
        <tr><th>Дата реєстрації</th><td>USER_REGDATE</td></tr>
        <tr><th>Профіль користувача</th><td><a href="USER_PROFILE_URL">Перейти до профілю</a></td></tr>
        <tr><th>IP-адреса</th><td>IP_VISITOR</td></tr>
        <tr><th>Країна</th><td>USER_COUNTRY</td></tr>
        <tr><th>Пристрій</th><td>USER_DEVICE</td></tr>
        <tr><th>Браузер</th><td>USER_BROWSER</td></tr>
    </table>
</body>
</html>';

$L['user_profile_link'] = 'Посилання на профіль';
$L['Success'] = 'Успішно';
$L['Error'] = 'Помилка';
$L['ID'] = 'ID';
$L['User'] = 'Користувач';
$L['Email'] = 'Email';
$L['IP'] = 'IP';
$L['Country'] = 'Країна';
$L['Device'] = 'Пристрій';
$L['Browser'] = 'Браузер';
$L['Date'] = 'Дата';
$L['Status'] = 'Статус';
$L['Message'] = 'Повідомлення';
$L['All'] = 'Усі';
$L['Filter'] = 'Фільтрувати';
$L['DateFrom'] = 'Дата з';
$L['DateTo'] = 'Дата по';