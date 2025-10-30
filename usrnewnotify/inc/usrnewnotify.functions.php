<?php
/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.functions.php
 * Purpose: Contains utility functions for the User New Notify plugin, including device, browser, and country detection, and logging functionality. Ensures new log entries for each event.
 * Date: 2025-10-30
 * @package usrnewnotify
 * @version 2.2.9
 * @author webitproff
 * @copyright Copyright (c) webitproff 2025
 * @license BSD
 */

// Безопасная проверка: убедимся, что файл подключён внутри Cotonti (COT_CODE определена).
// Если кто-то попытается открыть файл напрямую — выполнение прервётся (die()).
defined('COT_CODE') or die('Wrong URL.');

// Регистрируем таблицу логов плагина в глобальном объекте базы данных Cot::$db.
// Это позволяет в коде ссылаться на Cot::$db->usrnewnotify_logs вместо явного имени таблицы.
Cot::$db->registerTable('usrnewnotify_logs');
// Регистрируем в объекте базы данных псевдоним таблицы 'usrnewnotify_logs', чтобы позже
// обращаться к ней как Cot::$db->usrnewnotify_logs — это удобно и совместимо с системой.
// Cot::$db — объект базы данных в Cotonti. Метод registerTable говорит системе, что в плагине
// будет использоваться таблица с псевдонимом 'usrnewnotify_logs'. Это нужно, чтобы далее
// можно было обращаться к ней через Cot::$db->usrnewnotify_logs.

/**
 * Определяет тип устройства на основе user-agent
 * @param string $user_agent User-agent строка из $_SERVER['HTTP_USER_AGENT']
 * @return string Тип устройства: Mobile, Tablet, Desktop или N/A
 */
function cot_usrnewnotify_detect_device($user_agent) {
    // Если строка user-agent пустая — возвращаем 'N/A' (неизвестно).
    if (empty($user_agent)) {
        return 'N/A';
    }

    // Приводим user-agent к нижнему регистру для упрощения сравнения с шаблонами.
    $user_agent = strtolower($user_agent);

    // Ищем ключевые слова, характерные для мобильных устройств.
    // preg_match возвращает 1, если найдена хотя бы одна из поисковых подстрок.
    if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/', $user_agent)) {
        // Если нашли — считаем устройство мобильным.
        return 'Mobile';
    } elseif (preg_match('/ipad|tablet|kindle|playbook|surface|nexus|tab/', $user_agent)) {
        // Если обнаружились слова, часто встречающиеся в планшетных UA — помечаем как Tablet.
        return 'Tablet';
    }

    // По умолчанию, если не найдено ни то ни другое — полагаем, что это десктоп.
    return 'Desktop';
}

/**
 * Определяет браузер на основе user-agent
 * @param string $user_agent User-agent строка
 * @return string Название браузера или Unknown
 */
function cot_usrnewnotify_detect_browser($user_agent) {
    // Если user-agent отсутствует — невозможно определить браузер.
    if (empty($user_agent)) {
        return 'N/A';
    }

    // Унифицируем регистр для удобного поиска.
    $user_agent = strtolower($user_agent);

    // Проверяем по порядку наиболее специфичные подстроки — сначала Edge (edg/edge),
    // затем Chrome, Firefox, Safari и Opera. Порядок важен, потому что например UA Chrome
    // содержит слово 'safari', поэтому мы проверяем Safari только если не найден Chrome.
    if (preg_match('/edg|edge/', $user_agent)) {
        return 'Microsoft Edge';
    } elseif (preg_match('/chrome|crios/', $user_agent)) {
        return 'Google Chrome';
    } elseif (preg_match('/firefox|fxios/', $user_agent)) {
        return 'Mozilla Firefox';
    } elseif (preg_match('/safari/', $user_agent) && !preg_match('/chrome|crios/', $user_agent)) {
        // Тут важно исключить Chrome — у Chrome есть слово 'safari' в UA, поэтому проверяем, что Chrome не найден.
        return 'Safari';
    } elseif (preg_match('/opera|opr/', $user_agent)) {
        return 'Opera';
    }

    // Если ни один из известных браузеров не обнаружен — возвращаем 'Unknown'.
    return 'Unknown';
}

/**
 * Определяет страну по IP-адресу через ip-api.com
 * @param string $ip IP-адрес пользователя
 * @return string Название страны или N/A
 */
function cot_usrnewnotify_detect_country($ip) {
    // Если IP не указан или помечен как 'N/A' — возвращаем 'N/A'.
    if (empty($ip) || $ip === 'N/A') {
        return 'N/A';
    }

    // Составляем URL запрос к внешнему сервису ip-api.com. Обратите внимание — это HTTP.
    // В реальных проектах рекомендуется использовать HTTPS или локальную базу GeoIP.
    $url = "http://ip-api.com/json/{$ip}?fields=country";

    // file_get_contents может выдавать предупреждение при недоступности сети, поэтому используем @
    // чтобы подавить PHP-ворнинги — обработаем ошибку проверкой на false.
    $response = @file_get_contents($url);

    // Если получили ответ — пытаемся распарсить JSON и вернуть поле country.
    if ($response !== false) {
        $data = json_decode($response, true);
        // Проверяем, есть ли поле 'country' и возвращаем его, экранируя для безопасности вывода.
        return !empty($data['country']) ? htmlspecialchars($data['country'], ENT_QUOTES, 'UTF-8') : 'N/A';
    }

    // В случае ошибки (сеть, превышен лимит запросов, временная недоступность API) — возвращаем 'N/A'.
    return 'N/A';
}

/**
 * Логирует событие регистрации или подтверждения в таблицу usrnewnotify_logs
 * @param array $ruser Данные пользователя (user_id, user_name, user_email)
 * @param string $status Статус события (success или error)
 * @param string $message Сообщение для лога
 */
function cot_usrnewnotify_log_event($ruser, $status, $message) {
    // Проверяем, включено ли логирование в настройках плагина.
    // Если опция notify_log выключена — функция немедленно возвращает управление.
    if (!Cot::$cfg['plugin']['usrnewnotify']['notify_log']) {
        return;
    }

    // Получаем имя таблицы логов из зарегистрированных псевдонимов Cot::$db.
    $table_name = Cot::$db->usrnewnotify_logs;

    // Берём user-agent и IP из глобального массива $_SERVER — они будут записаны в лог.
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? 'N/A', FILTER_VALIDATE_IP) ?: 'N/A';

    // Собираем данные о пользователе — приводим ID к integer и экранируем строки для безопасности.
    // Получаем и подготавливаем ключевые данные из массива $ruser — это массив с информацией о пользователе,
	// который передаётся в функцию cot_usrnewnotify_log_event при вызове.
	// Внимание: ниже мы НЕ изменяем логику — только подробно подписываем каждую операцию для новичка.

	// 1) $user_id — проверяем, существует ли ключ 'user_id' в массиве $ruser.
	//    isset($ruser['user_id']) возвращает true, если элемент существует и не равен null.
	//    Оператор тернарного вида (?:) выбирает значение: если isset() истина — приводим значение к integer
	//    с помощью (int) для безопасности (чтобы строка или другой тип привёлся к числу),
	//    иначе (если ключа нет) присваиваем 0 — это безопасное значение по умолчанию.
	$user_id = isset($ruser['user_id']) ? (int)$ruser['user_id'] : 0;

	// 2) $user_name — проверяем, есть ли в массиве 'user_name'.
	//    Если есть — берём его и сразу применяем htmlspecialchars().
	//    htmlspecialchars защищает от внедрения HTML/JS при выводе (предотвращает XSS):
	//    заменяет символы <, >, & и кавычки на безопасные HTML-сущности.
	//    ENT_QUOTES говорит, что будут экранированы и одинарные, и двойные кавычки;
	//    'UTF-8' указывает кодировку, которая должна использоваться при экранировании.
	//    Если ключ отсутствует — ставим строку 'N/A' (Not Available) как читаемое значение по умолчанию.
	$user_name = isset($ruser['user_name']) ? htmlspecialchars($ruser['user_name'], ENT_QUOTES, 'UTF-8') : 'N/A';

	// 3) $user_email — аналогично user_name: если есть ключ 'user_email' — экранируем его через htmlspecialchars(),
	//    чтобы безопасно сохранять/показывать в логах или в письмах. Если нет — 'N/A'.
	//    Примечание: htmlspecialchars не валидирует email как корректный адрес — для проверки формата нужно
	//    использовать filter_var($email, FILTER_VALIDATE_EMAIL) там, где это необходимо.
	$user_email = isset($ruser['user_email']) ? htmlspecialchars($ruser['user_email'], ENT_QUOTES, 'UTF-8') : 'N/A';

    // Нормализуем статус — если передан не 'success' или не 'error', по умолчанию считаем 'error'.
    $status = in_array($status, ['success', 'error']) ? $status : 'error';

    // Формируем массив данных для вставки в таблицу логов. Ключи соответствуют столбцам таблицы.
    $data = [
        'log_user_id' => $user_id, // ID пользователя (число).
        'log_user_name' => $user_name, // Имя пользователя (экранированная строка).
        'log_user_email' => $user_email, // Email пользователя.
        'log_ip' => $ip, // IP адрес посетителя.
        'log_user_agent' => htmlspecialchars($user_agent, ENT_QUOTES, 'UTF-8') ?: 'N/A', // User-Agent (экранированный).
        'log_device' => cot_usrnewnotify_detect_device($user_agent) ?: 'N/A', // Тип устройства (Mobile/Tablet/Desktop).
        'log_browser' => cot_usrnewnotify_detect_browser($user_agent) ?: 'N/A', // Название браузера или 'Unknown'.
        'log_country' => cot_usrnewnotify_detect_country($ip) ?: 'N/A', // Страна по IP или 'N/A'.
        'log_date' => date('Y-m-d H:i:s', Cot::$sys['now']), // Текущее время в формате MySQL DATETIME.
        'log_status' => $status, // Статус события ('success' или 'error').
        'log_message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?: 'N/A', // Сообщение лога, экранированное.
    ];

    // Блок try — это конструкция обработки исключений (exceptions) в PHP.
	// Всё, что находится внутри try { ... } — выполняется как обычно, но если внутри произойдёт ошибка,
	// которая бросит исключение (throw new Exception(...)), выполнение перейдёт к соответствующему блоку catch.
	// Это используется для безопасной работы с операциями, которые могут провалиться (например, работа с БД).
	try {
        // Проверяем, существует ли таблица в базе данных. Это защитит от ошибок при отсутствии таблицы.
        $table_exists = Cot::$db->query("SHOW TABLES LIKE '{$table_name}'")->rowCount() > 0;
        if (!$table_exists) {
            // Если таблицы нет — записываем системный лог и прекращаем попытку вставки.
            cot_log("UserNewNotify: Table {$table_name} does not exist.", 'plug', 'error');
            return;
        }

        // Пытаемся вставить новую запись в таблицу логов. Функция insert возвращает результат операции.
        $result = Cot::$db->insert($table_name, $data);
        if (!$result) {
            // Если вставка не удалась — записываем об этом системный лог.
            cot_log("UserNewNotify: Failed to insert log: Database error.", 'plug', 'error');
        }
    } // Блок catch перехватывает исключение, брошенное внутри try.
	// В catch (Exception $e) переменная $e — это объект исключения, у которого есть методы,
	// например getMessage(), возвращающий текст ошибки.
	// Здесь мы логируем сообщение об исключении в системный лог Cotonti, чтобы разработчик мог понять причину сбоя.
	catch (Exception $e) {
        // При выбросе исключения (например, проблемы с соединением к БД) — логируем текст ошибки.
        cot_log("UserNewNotify: Failed to log event: " . $e->getMessage(), 'plug', 'error');
    }
}
