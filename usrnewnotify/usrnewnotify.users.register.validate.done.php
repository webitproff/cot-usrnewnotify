<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=users.register.validate.done
[END_COT_EXT]
==================== */

/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.users.register.validate.done.php
 * Purpose: Sends email notification after user validates registration via email link and logs the event. Uses $row from users.register.php or user_lostpass for data. Triggered after user confirms email by clicking the validation link with code $v in users.register.php.
 * Date: 2025-10-29
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

// Проверяем, включены ли уведомления и требуется ли подтверждение email
if (empty(Cot::$cfg['plugin']['usrnewnotify']['notify_enabled']) || Cot::$cfg['users']['regnoactivation']) {
    return;
}

// Получаем код валидации из GET
$v = cot_import('v', 'G', 'ALP');
// Извлекаем данные пользователя из $row
$user_id = isset($row['user_id']) ? (int)$row['user_id'] : 0;
$user_name = isset($row['user_name']) ? htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
$user_email = isset($row['user_email']) ? htmlspecialchars($row['user_email'], ENT_QUOTES, 'UTF-8') : 'N/A';
$reg_date = isset($row['user_regdate']) ? cot_date('datetime_full', $row['user_regdate']) : cot_date('datetime_full', Cot::$sys['now']);

// Если данные отсутствуют, ищем по user_lostpass
if (($user_name === 'N/A' || $user_email === 'N/A' || $user_id === 0) && !empty($v) && mb_strlen($v) == 32) {
    $user = Cot::$db->query("SELECT user_id, user_name, user_email, user_regdate FROM " . Cot::$db->users . " WHERE user_lostpass = ? LIMIT 1", [$v])->fetch();
    if ($user) {
        $user_id = (int)$user['user_id'];
        $user_name = htmlspecialchars($user['user_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $user_email = htmlspecialchars($user['user_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $reg_date = cot_date('datetime_full', $user['user_valdate'] ?? Cot::$sys['now']);
    }
}

// Формируем полную ссылку на профиль пользователя
$user_profile_url = $user_id > 0 ? Cot::$cfg['mainurl'] . '/' . cot_url('users', ['m' => 'details', 'id' => $user_id], '', true) : 'N/A';

// Собираем данные о пользователе
$ip = filter_var($_SERVER['REMOTE_ADDR'] ?? 'N/A', FILTER_VALIDATE_IP) ?: 'N/A';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$device = cot_usrnewnotify_detect_device($user_agent);
$browser = cot_usrnewnotify_detect_browser($user_agent);
$country = cot_usrnewnotify_detect_country($ip);

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

// Проверяем наличие email
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

// Выбираем шаблон для подтверждения email
$message = $format === 'html'
    ? str_replace(array_keys($replacements), array_values($replacements), $L['send_to_adminmail_html_message_validated'])
    : str_replace(array_keys($replacements), array_values($replacements), $L['send_to_adminmail_message_validated']);

// Удаляем фигурные скобки
$message = preg_replace('/\{[A-Z_]+\}/', 'N/A', $message);

// Отправляем письмо
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

// Определяем статус и сообщение для лога
$log_status = $send_success ? 'success' : 'error';
$log_message = $send_success ? 'Notification sent successfully' : 'Failed to send notification';

// Логируем событие подтверждения (новая запись)
cot_log("UserNewNotify: Notification for user '{$user_name}' — status: {$log_status}", 'plug', $log_status);
cot_usrnewnotify_log_event(['user_id' => $user_id, 'user_name' => $user_name, 'user_email' => $user_email], $log_status, $log_message);