<?php
/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.functions.php
 * Purpose: Contains utility functions for the User New Notify plugin, including device, browser, and country detection, and logging functionality. Ensures new log entries for each event.
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

/**
 * Определяет тип устройства на основе user-agent
 * @param string $user_agent User-agent строка из $_SERVER['HTTP_USER_AGENT']
 * @return string Тип устройства: Mobile, Tablet, Desktop или N/A
 */
function cot_usrnewnotify_detect_device($user_agent) {
    if (empty($user_agent)) {
        return 'N/A';
    }
    $user_agent = strtolower($user_agent);
    if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/', $user_agent)) {
        return 'Mobile';
    } elseif (preg_match('/ipad|tablet|kindle|playbook|surface|nexus|tab/', $user_agent)) {
        return 'Tablet';
    }
    return 'Desktop';
}

/**
 * Определяет браузер на основе user-agent
 * @param string $user_agent User-agent строка
 * @return string Название браузера или Unknown
 */
function cot_usrnewnotify_detect_browser($user_agent) {
    if (empty($user_agent)) {
        return 'N/A';
    }
    $user_agent = strtolower($user_agent);
    if (preg_match('/edg|edge/', $user_agent)) {
        return 'Microsoft Edge';
    } elseif (preg_match('/chrome|crios/', $user_agent)) {
        return 'Google Chrome';
    } elseif (preg_match('/firefox|fxios/', $user_agent)) {
        return 'Mozilla Firefox';
    } elseif (preg_match('/safari/', $user_agent) && !preg_match('/chrome|crios/', $user_agent)) {
        return 'Safari';
    } elseif (preg_match('/opera|opr/', $user_agent)) {
        return 'Opera';
    }
    return 'Unknown';
}

/**
 * Определяет страну по IP-адресу через ip-api.com
 * @param string $ip IP-адрес пользователя
 * @return string Название страны или N/A
 */
function cot_usrnewnotify_detect_country($ip) {
    if (empty($ip) || $ip === 'N/A') {
        return 'N/A';
    }
    $url = "http://ip-api.com/json/{$ip}?fields=country";
    $response = @file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        return !empty($data['country']) ? htmlspecialchars($data['country'], ENT_QUOTES, 'UTF-8') : 'N/A';
    }
    return 'N/A';
}

/**
 * Логирует событие регистрации или подтверждения в таблицу usrnewnotify_logs
 * @param array $ruser Данные пользователя (user_id, user_name, user_email)
 * @param string $status Статус события (success или error)
 * @param string $message Сообщение для лога
 */
function cot_usrnewnotify_log_event($ruser, $status, $message) {
    // Проверяем, включено ли логирование в настройках плагина
    if (!Cot::$cfg['plugin']['usrnewnotify']['notify_log']) {
        return;
    }

    $table_name = Cot::$db->usrnewnotify_logs;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? 'N/A', FILTER_VALIDATE_IP) ?: 'N/A';
    
    // Собираем данные для лога
    $user_id = isset($ruser['user_id']) ? (int)$ruser['user_id'] : 0;
    $user_name = isset($ruser['user_name']) ? htmlspecialchars($ruser['user_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
    $user_email = isset($ruser['user_email']) ? htmlspecialchars($ruser['user_email'], ENT_QUOTES, 'UTF-8') : 'N/A';
    
    $status = in_array($status, ['success', 'error']) ? $status : 'error';
    
    $data = [
        'log_user_id' => $user_id,
        'log_user_name' => $user_name,
        'log_user_email' => $user_email,
        'log_ip' => $ip,
        'log_user_agent' => htmlspecialchars($user_agent, ENT_QUOTES, 'UTF-8') ?: 'N/A',
        'log_device' => cot_usrnewnotify_detect_device($user_agent) ?: 'N/A',
        'log_browser' => cot_usrnewnotify_detect_browser($user_agent) ?: 'N/A',
        'log_country' => cot_usrnewnotify_detect_country($ip) ?: 'N/A',
        'log_date' => date('Y-m-d H:i:s', Cot::$sys['now']),
        'log_status' => $status,
        'log_message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?: 'N/A',
    ];
    
    try {
        // Проверяем существование таблицы
        $table_exists = Cot::$db->query("SHOW TABLES LIKE '{$table_name}'")->rowCount() > 0;
        if (!$table_exists) {
            cot_log("UserNewNotify: Table {$table_name} does not exist.", 'plug', 'error');
            return;
        }
        // Вставляем новую запись в лог
        $result = Cot::$db->insert($table_name, $data);
        if (!$result) {
            cot_log("UserNewNotify: Failed to insert log: Database error.", 'plug', 'error');
        }
    } catch (Exception $e) {
        cot_log("UserNewNotify: Failed to log event: " . $e->getMessage(), 'plug', 'error');
    }
}