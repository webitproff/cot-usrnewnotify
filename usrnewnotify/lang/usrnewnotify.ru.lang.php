<?php
/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.ru.lang.php
 * Purpose: Russian localization for the User New Notify plugin. Defines email templates for registration and validation, and admin interface strings.
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
$L['cfg_notify_enabled'] = 'Включить уведомления по электронной почте';
$L['cfg_notify_email'] = 'Адрес(а) электронной почты администратора для уведомлений (через запятую)';
$L['cfg_notify_format'] = 'Формат уведомлений (text,html)';
$L['cfg_notify_log'] = 'Включить ведение журнала в базе данных, таблица usrnewnotify_logs';

/**
 * Plugin Info
 */
$L['info_name'] = 'User New Notify';
$L['info_desc'] = 'Уведомляет администратора сайта по электронной почте о регистрации или подтверждении регистрации нового пользователя';
$L['info_notes'] = 'Требуются PHP 8.4+, MySQL 8.0+, Cotonti Siena v.0.9.26. Включает поддержку HTML-почты, ведение журнала базы данных, инструменты администрирования и определение страны.';

$L['usrnewnotify_subject'] = 'Регистрация нового пользователя';
$L['usrnewnotify_admin_title'] = 'Логи регистраций пользователей';

// Текстовый шаблон для регистрации (без подтверждения email)
$L['send_to_adminmail_message'] =
"Здравствуйте, Administrator! пользователь зарегистрировался, но еще не подтвердил регистрацию через свою почту
На сайте MAINTITLE (MAINURL) зарегистрировался новый пользователь: USER_NAME.
**Детали регистрации:**
- **Контактный email**: USER_EMAIL
- **Дата регистрации**: USER_REGDATE
- **Профиль пользователя**: USER_PROFILE_URL
- **IP-адрес**: IP_VISITOR
- **Страна**: USER_COUNTRY
- **Устройство**: USER_DEVICE
- **Браузер**: USER_BROWSER";

// Текстовый шаблон для подтверждения email
$L['send_to_adminmail_message_validated'] =
"Здравствуйте, Administrator! пользователь успешно зарегистрировался и подтвердил свою почту!
На сайте MAINTITLE (MAINURL) зарегистрировался и подтвердил email новый пользователь: USER_NAME.
**Детали регистрации:**
- **Контактный email**: USER_EMAIL
- **Дата регистрации**: USER_REGDATE
- **Профиль пользователя**: USER_PROFILE_URL
- **IP-адрес**: IP_VISITOR
- **Страна**: USER_COUNTRY
- **Устройство**: USER_DEVICE
- **Браузер**: USER_BROWSER";

// HTML-шаблон для регистрации (без подтверждения email)
$L['send_to_adminmail_html_message'] =
'<!DOCTYPE html>
<html lang="ru">
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
    <h2>Регистрация нового пользователя</h2>
    <p>Здравствуйте, Administrator! пользователь зарегистрировался, но еще не подтвердил регистрацию через свою почту</p>
    <p>На сайте <a href="MAINURL">MAINTITLE</a> зарегистрировался новый пользователь: <strong>USER_NAME</strong>.</p>
    <table>
        <tr><th>Контактный email</th><td>USER_EMAIL</td></tr>
        <tr><th>Дата регистрации</th><td>USER_REGDATE</td></tr>
        <tr><th>Профиль пользователя</th><td><a href="USER_PROFILE_URL">Перейти к профилю</a></td></tr>
        <tr><th>IP-адрес</th><td>IP_VISITOR</td></tr>
        <tr><th>Страна</th><td>USER_COUNTRY</td></tr>
        <tr><th>Устройство</th><td>USER_DEVICE</td></tr>
        <tr><th>Браузер</th><td>USER_BROWSER</td></tr>
    </table>
</body>
</html>';

// HTML-шаблон для письма после подтверждения email
$L['send_to_adminmail_html_message_validated'] =
'<!DOCTYPE html>
<html lang="ru">
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
    <h2>Подтверждение регистрации пользователя</h2>
    <p>Здравствуйте, Administrator! пользователь успешно зарегистрировался и подтвердил свою почту!</p>
    <p>На сайте <a href="MAINURL">MAINTITLE</a> зарегистрировался и подтвердил email новый пользователь: <strong>USER_NAME</strong>.</p>
    <table>
        <tr><th>Контактный email</th><td>USER_EMAIL</td></tr>
        <tr><th>Дата регистрации</th><td>USER_REGDATE</td></tr>
        <tr><th>Профиль пользователя</th><td><a href="USER_PROFILE_URL">Перейти к профилю</a></td></tr>
        <tr><th>IP-адрес</th><td>IP_VISITOR</td></tr>
        <tr><th>Страна</th><td>USER_COUNTRY</td></tr>
        <tr><th>Устройство</th><td>USER_DEVICE</td></tr>
        <tr><th>Браузер</th><td>USER_BROWSER</td></tr>
    </table>
</body>
</html>';

$L['user_profile_link'] = 'Ссылка на профиль';
$L['Success'] = 'Успешно';
$L['Error'] = 'Ошибка';
$L['ID'] = 'ID';
$L['User'] = 'Пользователь';
$L['Email'] = 'Email';
$L['IP'] = 'IP';
$L['Country'] = 'Страна';
$L['Device'] = 'Устройство';
$L['Browser'] = 'Браузер';
$L['Date'] = 'Дата';
$L['Status'] = 'Статус';
$L['Message'] = 'Сообщение';
$L['All'] = 'Все';
$L['Filter'] = 'Фильтровать';
$L['DateFrom'] = 'Дата с';
$L['DateTo'] = 'Дата по';