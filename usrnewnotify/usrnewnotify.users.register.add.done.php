<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=users.register.add.done
[END_COT_EXT]
==================== */

/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.users.register.add.done.php
 * Purpose: Logs user registration and sends email notifications using $ruser and $userid. Ensures valid user_id for profile URL with full domain and proper HTML rendering. Triggered after a new user is added via cot_add_user in users.register.php, immediately after form submission and user creation in cot_users.
 * Date: 2025-10-30
 * @package usrnewnotify
 * @version 2.2.8
 * @author webitproff
 * @copyright Copyright (c) webitproff 2025
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL.');

// Регистрируем таблицу логов плагина
Cot::$db->registerTable('usrnewnotify_logs');
// Подключаем функции плагина и языковой файл
require_once cot_incfile('usrnewnotify', 'plug');
require_once cot_langfile('usrnewnotify', 'plug');

// Проверяем, включены ли уведомления в настройках плагина
if (empty(Cot::$cfg['plugin']['usrnewnotify']['notify_enabled'])) {
    return;
}

// Получаем user_id из $userid, возвращаемого cot_add_user
$user_id = (int)$userid;
// Извлекаем имя и email из $ruser, переданного из users.register.php
$user_name = isset($ruser['user_name']) ? htmlspecialchars($ruser['user_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
$user_email = isset($ruser['user_email']) ? htmlspecialchars($ruser['user_email'], ENT_QUOTES, 'UTF-8') : 'N/A';
$reg_date = isset($ruser['user_regdate']) ? cot_date('datetime_full', $ruser['user_regdate']) : cot_date('datetime_full', Cot::$sys['now']);

// Проверяем данные через базу, если они отсутствуют
if ($user_name === 'N/A' || $user_email === 'N/A' || $user_id === 0) {
    $user = Cot::$db->query("SELECT user_id, user_name, user_email, user_regdate FROM " . Cot::$db->users . " WHERE user_id = ?", [$user_id])->fetch();
    if ($user) {
        $user_id = (int)$user['user_id'];
        $user_name = htmlspecialchars($user['user_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $user_email = htmlspecialchars($user['user_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $reg_date = cot_date('datetime_full', $user['user_regdate'] ?? Cot::$sys['now']);
    }
}

// Формируем полную ссылку на профиль пользователя
$user_profile_url = $user_id > 0 ? Cot::$cfg['mainurl'] . '/' . cot_url('users', ['m' => 'details', 'id' => $user_id], '', true) : 'N/A';

// Собираем данные о пользователе: IP, устройство, браузер, страна
$ip = filter_var($_SERVER['REMOTE_ADDR'] ?? 'N/A', FILTER_VALIDATE_IP) ?: 'N/A';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device = cot_usrnewnotify_detect_device($user_agent);
$browser = cot_usrnewnotify_detect_browser($user_agent);
$country = cot_usrnewnotify_detect_country($ip);

// Определяем статус регистрации
$log_status = Cot::$cfg['users']['regnoactivation'] ? 'success' : 'error';
$log_message = Cot::$cfg['users']['regnoactivation'] ? 'Registration completed' : 'Pending email validation';
if ($user_name === 'N/A' || $user_email === 'N/A' || $user_id === 0) {
    $log_status = 'error';
    $log_message = 'Missing or invalid user data';
    cot_log("UserNewNotify: $ruser is incomplete in users.register.add.done", 'plug', 'error');
}

// Формируем массив замен для шаблона письма
$replacements = [
    'USER_NAME' => $user_name,
    'MAINTITLE' => Cot::$cfg['maintitle'] ?? 'N/A',
    'MAINURL' => Cot::$cfg['mainurl'] ?? 'N/A',
    'USER_EMAIL' => $user_email,
    'USER_PROFILE_URL' => $user_profile_url,
    'IP_VISITOR' => $ip,
    'USER_REGDATE' => $reg_date,
    'USER_DEVICE' => $device,
    'USER_BROWSER' => $browser,
    'USER_COUNTRY' => $country,
];

// Собираем список email для отправки
$admin_emails = [Cot::$cfg['adminemail']];
if (!empty(Cot::$cfg['plugin']['usrnewnotify']['notify_email'])) {
    $admin_emails = array_merge($admin_emails, array_filter(array_map('trim', explode(',', Cot::$cfg['plugin']['usrnewnotify']['notify_email']))));
}
$admin_emails = array_unique(array_filter($admin_emails, 'strlen'));

// Проверяем наличие email для отправки
if (empty($admin_emails)) {
    cot_log('UserNewNotify: Admin email is not configured.', 'plug', 'error');
    cot_usrnewnotify_log_event(['user_id' => $user_id, 'user_name' => $user_name, 'user_email' => $user_email], 'error', 'Admin email not configured');
    return;
}

// Инициализируем флаг успешной отправки
$send_success = true;
$format = Cot::$cfg['plugin']['usrnewnotify']['notify_format'] ?? 'text';

// Настраиваем заголовки для письма
$headers = [
    'MIME-Version' => '1.0',
    'Content-Type' => $format === 'html' ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => '8bit',
];

// Выбираем шаблон для регистрации (без подтверждения)
$message = $format === 'html'
    ? str_replace(array_keys($replacements), array_values($replacements), $L['send_to_adminmail_html_message'])
    : str_replace(array_keys($replacements), array_values($replacements), $L['send_to_adminmail_message']);

// Удаляем фигурные скобки из сообщения
$message = preg_replace('/\{[A-Z_]+\}/', 'N/A', $message);

// Отправляем письмо каждому администратору
foreach ($admin_emails as $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        cot_log("UserNewNotify: Invalid email address skipped: {$email}", 'plug', 'error');
        $send_success = false;
        continue;
    }
    $result = cot_mail($email, $L['usrnewnotify_subject'], $message, $headers, false, null, true);
    if (!$result) {
        cot_log("UserNewNotify: Failed to send email to: {$email}", 'plug', 'error');
        $send_success = false;
    }
}

// Обновляем статус и сообщение лога
$log_status = $send_success && $log_status === 'success' ? 'success' : 'error';
$log_message = $send_success ? ($log_message === 'Pending email validation' ? $log_message : 'Notification sent successfully') : 'Failed to send notification';

// Логируем событие регистрации (новая запись)
cot_log("UserNewNotify: Notification for user '{$user_name}' — status: {$log_status}", 'plug', $log_status);
cot_usrnewnotify_log_event(['user_id' => $user_id, 'user_name' => $user_name, 'user_email' => $user_email], $log_status, $log_message);
