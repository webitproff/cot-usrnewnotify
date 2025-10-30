<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=users.register.validate.done
[END_COT_EXT]
==================== */
/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.users.register.validate.done.php
 * Purpose: Отправляет уведомление администратору после того, как пользователь подтвердил регистрацию по ссылке из email.
 * Использует массив $row (данные пользователя) из users.register.php или из механизма восстановления пароля.
 * Срабатывает **после** клика по ссылке с кодом подтверждения $v.
 * Также логирует событие в системный лог и в свою таблицу логов.
 * Date: 2025-10-29
 * @package usrnewnotify
 * @version 2.2.8
 * @author webitproff
 * @copyright Copyright (c) webitproff 2025
 * @license BSD
 */
/* === ЗАЩИТА ОТ ПРЯМОГО ДОСТУПА === */
// В Cotonti при корректном запуске файла через систему (например, через хук) автоматически определяется константа COT_CODE.
// Если файл открыть напрямую в браузере (например, http://site.com/plugins/usrnewnotify/usrnewnotify.users.register.validate.done.php),
// константа COT_CODE не будет определена — выполнится `die('Wrong URL.')` и скрипт немедленно завершится.
// Это **базовая защита** от несанкционированного запуска PHP-файлов вне системы CMS.
// Для новичка: `defined('COT_CODE')` — проверяет, существует ли константа.
// `or die(...)` — если нет, останавливает выполнение и выводит сообщение "Wrong URL.".
defined('COT_CODE') or die('Wrong URL.');
/* === 1. РЕГИСТРАЦИЯ ТАБЛИЦЫ ЛОГОВ ПЛАГИНА === */
// `Cot::$db->registerTable('usrnewnotify_logs')` — сообщает Cotonti, что существует таблица с логическим именем `usrnewnotify_logs`.
// Теперь к ней можно обращаться через `Cot::$db->usrnewnotify_logs`, и Cotonti автоматически подставит префикс БД (обычно `cot_`).
// Пример: если префикс `cot_`, то реальное имя таблицы — `cot_usrnewnotify_logs`.
// Это позволяет писать **переносимый код**, не зависящий от конкретного префикса.
// Без этого — при обращении к таблице будет ошибка "Table not found".
/* Пример из старых модулей (для понимания эволюции):
   global $db_x;
   $db_forum_stats = $db_x . 'forum_stats'; // ← ручное формирование имени
   В Cotonti 0.9+ — используем registerTable, чтобы не писать $db_x везде.
*/
Cot::$db->registerTable('usrnewnotify_logs');
/* === 2. ПОДКЛЮЧЕНИЕ ВСПОМОГАТЕЛЬНЫХ ФАЙЛОВ ПЛАГИНА === */
// `cot_incfile('usrnewnotify', 'plug')` — функция Cotonti, возвращает полный путь к файлу внутри папки плагина.
// Обычно это: plugins/usrnewnotify/inc/usrnewnotify.functions.php
// `require_once` — подключает этот файл **один раз**, даже если он будет вызван повторно.
// Здесь подключаются **пользовательские функции плагина**, например:
// cot_usrnewnotify_detect_device(), cot_usrnewnotify_detect_browser(), cot_usrnewnotify_log_event() и др.
// Без этого — вызов этих функций вызовет ошибку "Call to undefined function".
require_once cot_incfile('usrnewnotify', 'plug');
/* === 3. ПОДКЛЮЧЕНИЕ ЯЗЫКОВОГО ФАЙЛА === */
// `cot_langfile('usrnewnotify', 'plug')` — возвращает путь к файлу с переводами плагина.
// Например: plugins/usrnewnotify/lang/usrnewnotify.ru.lang.php
// После подключения становится доступен массив `$L`, где ключи — это идентификаторы строк,
// а значения — переведённые тексты на текущем языке сайта.
// Пример:
// $L['usrnewnotify_subject'] = 'Пользователь подтвердил регистрацию';
// $L['send_to_adminmail_html_message_validated'] = '<h3>Пользователь {USER_NAME} подтвердил email</h3>...';
// Используется для темы письма и тела сообщения.
require_once cot_langfile('usrnewnotify', 'plug');
/* === 4. ПРОВЕРКА: НУЖНО ЛИ ОТПРАВЛЯТЬ УВЕДОМЛЕНИЕ? === */
// `Cot::$cfg['plugin']['usrnewnotify']['notify_enabled']` — настройка плагина в админке.
// `Cot::$cfg['users']['regnoactivation']` — глобальная настройка системы:
// true = регистрация **без подтверждения** по email
// false = требуется подтверждение (по ссылке с кодом)
// Этот хук `users.register.validate.done` срабатывает **только при подтверждении по email**.
// Поэтому:
// Если уведомления отключены — выходим.
// Если подтверждение не требуется (regnoactivation = true) — **этот хук не должен срабатывать**, но проверяем на всякий случай.
// `return;` — **немедленно завершает выполнение файла**. Ничего ниже не выполнится.
if (empty(Cot::$cfg['plugin']['usrnewnotify']['notify_enabled']) || Cot::$cfg['users']['regnoactivation']) {
    return;
}
/* === 5. ПОЛУЧЕНИЕ КОДА ПОДТВЕРЖДЕНИЯ ИЗ URL === */
// `cot_import('v', 'G', 'ALP')` — безопасно берёт параметр `v` из **GET** (`G` = $_GET).
// Фильтр `'ALP'` — разрешает только **буквы и цифры** (A-Z, a-z, 0-9), защищает от инъекций.
// Пример URL: https://site.com/users.php?m=register&a=validate&v=abc123xyz456def789ghi...
// $v = 'abc123xyz456def789ghi...'
// Это **код валидации**, который был отправлен пользователю в письме.
$v = cot_import('v', 'G', 'ALP');
/* === 6. ИЗВЛЕЧЕНИЕ ДАННЫХ ПОЛЬЗОВАТЕЛЯ ИЗ $row === */
// `$row` — **массив с данными пользователя**, который передаётся в хук из `users.register.php`.
// Он содержит запись из таблицы пользователей (или временную из механизма восстановления пароля).
$user_id = isset($row['user_id']) ? (int)$row['user_id'] : 0;
// Приводим к целому числу для безопасности. Если нет — 0.
$user_name = isset($row['user_name']) ? htmlspecialchars($row['user_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
// `htmlspecialchars()` — защита от XSS: <script> → &lt;script&gt;
// `ENT_QUOTES` — экранирует и одинарные, и двойные кавычки.
// Если имени нет — 'N/A'
$user_email = isset($row['user_email']) ? htmlspecialchars($row['user_email'], ENT_QUOTES, 'UTF-8') : 'N/A';
// То же самое для email.
$reg_date = isset($row['user_regdate'])
    ? cot_date('datetime_full', $row['user_regdate'])
    : cot_date('datetime_full', Cot::$sys['now']);
// `cot_date()` — форматирует timestamp по шаблону из настроек сайта.
// `Cot::$sys['now']` — текущее время сервера.
/* === 7. РЕЗЕРВНЫЙ ПОИСК ПО КОДУ ВАЛИДАЦИИ === */
// Если в $row нет нужных данных (например, хук вызван из механизма восстановления пароля), но есть код $v длиной 32 символа — ищем в БД.
// Поле `user_lostpass` в таблице пользователей хранит **хеш кода подтверждения** (обычно md5).
// Пример: пользователь получил письмо с ссылкой `?v=abc123...` → в БД сохраняется md5('abc123...').
if (($user_name === 'N/A' || $user_email === 'N/A' || $user_id === 0) && !empty($v) && mb_strlen($v) == 32) {
    // Подготовленный запрос — защита от SQL-инъекций
    // `LIMIT 1` — оптимизация: ищем только одну запись
    // ДОБАВЛЕНО: user_valdate в SELECT! Это поле содержит время подтверждения регистрации (когда пользователь кликнул по ссылке).
    $user = Cot::$db->query(
        "SELECT user_id, user_name, user_email, user_regdate, user_valdate FROM " . Cot::$db->users . " WHERE user_lostpass = ? LIMIT 1",
        [$v]
    )->fetch();
    if ($user) {
        $user_id = (int)$user['user_id'];
        $user_name = htmlspecialchars($user['user_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $user_email = htmlspecialchars($user['user_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        // Используем `user_valdate` — время подтверждения, если есть.
        // Если его нет — берём `user_regdate`, если и его нет — текущее время.
        $reg_date = cot_date('datetime_full', 
            $user['user_valdate'] ?? 
            $user['user_regdate'] ?? 
            Cot::$sys['now']
        );
    }
}
/* === 8. ФОРМИРОВАНИЕ ССЫЛКИ НА ПРОФИЛЬ === */
// `cot_url()` — генерирует внутренний URL.
// `true` в конце — возвращает **относительный путь** (без домена).
// `Cot::$cfg['mainurl']` — полный адрес сайта[](https://example.com).
// Результат: https://example.com/users.php?m=details&id=123
$user_profile_url = $user_id > 0
    ? Cot::$cfg['mainurl'] . '/' . cot_url('users', ['m' => 'details', 'id' => $user_id], '', true)
    : 'N/A';
/* === 9. СБОР ИНФОРМАЦИИ О ПОЛЬЗОВАТЕЛЕ === */
$ip = filter_var($_SERVER['REMOTE_ADDR'] ?? 'N/A', FILTER_VALIDATE_IP) ?: 'N/A';
// `$_SERVER['REMOTE_ADDR']` — IP клиента.
// `filter_var(..., FILTER_VALIDATE_IP)` — проверяет валидность IP.
// Если нет — 'N/A'.
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
// Строка браузера: "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."
$device = cot_usrnewnotify_detect_device($user_agent);
// Функция плагина: возвращает "Desktop", "Mobile", "Tablet"
$browser = cot_usrnewnotify_detect_browser($user_agent);
// Например: "Chrome 128"
$country = cot_usrnewnotify_detect_country($ip);
// По IP определяет страну (через GeoIP)
/* === 10. ПОДГОТОВКА ШАБЛОНА ПИСЬМА === */
// `$replacements` — **массив замен**: ключ → значение
// В шаблоне письма: {USER_NAME} → будет заменено на $user_name
// Это нужно, чтобы не писать конкретные данные в шаблоне, а подставлять их динамически.
// Формируем массив замен для шаблона письма
// Массив $replacements содержит пары "тег => значение", которые будут подставлены в шаблон письма.
// Шаблон (строка с плейсхолдерами вроде {USER_NAME}) содержится в файлах локализации $L.
$replacements = [
    // Ключ 'USER_NAME' → будет заменён на значение $user_name
    // Откуда $user_name? → из строки выше: $user_name = ... (из $row или БД)
    'USER_NAME' => $user_name, // Имя пользователя, который подтвердил регистрацию
    // `Cot::$cfg['maintitle'] ?? 'N/A'`
    // `??` — **оператор нулевого слияния** (null coalescing)
    // Читается: "Возьми Cot::$cfg['maintitle'], но если его нет — возьми 'N/A'"
    'MAINTITLE' => Cot::$cfg['maintitle'] ?? 'N/A', // Название сайта из настроек
    'MAINURL' => Cot::$cfg['mainurl'] ?? 'N/A', // URL сайта
    'USER_EMAIL' => $user_email, // Email
    'USER_PROFILE_URL' => $user_profile_url, // Ссылка на профиль
    'IP_VISITOR' => $ip, // IP
    'USER_REGDATE' => $reg_date, // Дата
    'USER_DEVICE' => $device, // Устройство
    'USER_BROWSER' => $browser, // Браузер
    'USER_COUNTRY' => $country, // Страна
];
// Пример: в шаблоне письма: "Пользователь: {USER_NAME}"
// После замены: "Пользователь: ivan271superpupkin"
/* === 11. ФОРМИРОВАНИЕ СПИСКА ПОЛУЧАТЕЛЕЙ === */
$admin_emails = [Cot::$cfg['adminemail']]; // Основной email админа
// Квадратные скобки `[]` — короткий синтаксис массива (PHP 5.4+)
// Раньше писали: array(Cot::$cfg['adminemail'])
if (!empty(Cot::$cfg['plugin']['usrnewnotify']['notify_email'])) {
    // Настройка: "admin2@site.com, support@site.com , "
    // 1. `explode(',', ...)` — разбивает строку по запятой
    // Результат: ['admin2@site.com', ' support@site.com', ' ']
    // 2. `array_map('trim', ...)` — применяет trim() к каждому элементу
    // trim() — убирает пробелы в начале и конце
    // Результат: ['admin2@site.com', 'support@site.com', '']
    // 3. `array_filter(...)` — убирает пустые элементы
    // Результат: ['admin2@site.com', 'support@site.com']
    $extra = array_filter(array_map('trim', explode(',', Cot::$cfg['plugin']['usrnewnotify']['notify_email'])));
    // `array_merge()` — объединяет два массива
    // $admin_emails = ['admin@site.com']
    // $extra = ['admin2@site.com', 'support@site.com']
    // Результат: ['admin@site.com', 'admin2@site.com', 'support@site.com']
    $admin_emails = array_merge($admin_emails, $extra);
}
// `array_filter(..., 'strlen')` — оставляет только строки длиной > 0
// `array_unique()` — убирает дубликаты
// → Гарантируем, что в массиве только уникальные, непустые email'ы
$admin_emails = array_unique(array_filter($admin_emails, 'strlen'));
/* === 12. ПРОВЕРКА: ЕСТЬ ЛИ КУДА ОТПРАВЛЯТЬ? === */
// `empty($admin_emails)` — проверяет, пустой ли массив.
// Если **ни одного валидного email** — значит, некуда отправлять уведомление.
// Что делаем:
// 1. Пишем в системный лог Cotonti (видно в админке → Логи)
// 2. Записываем событие в **свою таблицу логов** плагина
// 3. `return;` — **выходим из файла**, больше ничего не делаем
if (empty($admin_emails)) {
    cot_log('UserNewNotify: Admin email is not configured.', 'plug', 'error');
    cot_usrnewnotify_log_event(
        ['user_id' => $user_id, 'user_name' => $user_name, 'user_email' => $user_email],
        'error',
        'Admin email not configured'
    );
    return;
}
/* === 13. НАСТРОЙКИ ОТПРАВКИ === */
// `$send_success = true;` — **флаг (переключатель)**: "всё ли прошло успешно?"
// Изначально думаем, что **да** — отправка пройдёт.
// Позже, если хоть одно письмо не уйдёт — поставим `false`.
$send_success = true;
// `$format = ... ?? 'text';` — **оператор нулевого слияния** (null coalescing operator)
// Читается так:
// "Возьми значение из настроек, но если его нет или оно пустое — возьми 'text'"
// То есть: если админ не выбрал формат — будет обычный текст, а не HTML.
$format = Cot::$cfg['plugin']['usrnewnotify']['notify_format'] ?? 'text';
// `$headers` — массив заголовков письма.
// MIME-Version — стандарт
// Content-Type — **тип содержимого**:
// Если $format === 'html' → 'text/html' (браузер покажет как страницу)
// Иначе → 'text/plain' (простой текст)
// charset=UTF-8 — поддержка русских букв
$headers = [
    'MIME-Version' => '1.0',
    'Content-Type' => $format === 'html' ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => '8bit',
];
/* === 14. ФОРМИРОВАНИЕ ТЕЛА ПИСЬМА === */
// `$L['send_to_adminmail_html_message_validated']` — строка из языкового файла:
// "Пользователь {USER_NAME} подтвердил регистрацию!<br>Ссылка: {USER_PROFILE_URL}..."
// Мы заменяем **все плейсхолдеры** (типа {USER_NAME}) на реальные значения из $replacements.
// `array_keys($replacements)` → ['USER_NAME', 'USER_EMAIL', ...]
// `array_values($replacements)` → ['Иван', 'ivan@example.com', ...]
// `str_replace(ключи, значения, текст)` — заменяет все вхождения
// Тернарный оператор (?:) читается так:
// ЕСЛИ $format === 'html' → бери HTML-шаблон
// ИНАЧЕ → бери текстовый шаблон
$message = $format === 'html'
    ? str_replace(array_keys($replacements), array_values($replacements), $L['send_to_adminmail_html_message_validated'])
    : str_replace(array_keys($replacements), array_values($replacements), $L['send_to_adminmail_message_validated']);
// **Защита от "зависших" плейсхолдеров**
// Если в шаблоне остался {SOMETHING}, которого нет в $replacements — заменяем на 'N/A'
// `preg_replace('/\{[A-Z_]+\}/', 'N/A', $message)` — регулярное выражение:
// Ищи {БОЛЬШИЕ_БУКВЫ_И_ПОДЧЁРКИВАНИЯ} → замени на 'N/A'
$message = preg_replace('/\{[A-Z_]+\}/', 'N/A', $message);
/* === 15. ОТПРАВКА ПИСЕМ КАЖДОМУ АДМИНУ === */
// `foreach ($admin_emails as $email)` — **цикл по массиву**:
// $email по очереди принимает каждое значение из $admin_emails
// Сначала — admin@site.com, потом — support@site.com и т.д.
foreach ($admin_emails as $email) {
    // `filter_var($email, FILTER_VALIDATE_EMAIL)` — проверяет, **валидный ли email**
    // Возвращает email, если валидный, иначе — false
    // `!` — означает "НЕ"
    // То есть: если email НЕ валидный
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Пишем в лог: "Пропускаем некорректный email"
        cot_log("UserNewNotify: Invalid email address skipped: {$email}", 'plug', 'error');
        // `$send_success = false;` — помечаем, что **хотя бы одна ошибка**
        $send_success = false;
        // `continue;` — **перейти к следующему email**, этот пропустить
        continue;
    }
    // `cot_mail()` — безопасная отправка почты в Cotonti
    // Параметры:
    // $email — кому
    // $L['usrnewnotify_subject'] — тема (из языкового файла)
    // $message — тело письма
    // $headers — заголовки
    // false, null, true — дополнительные флаги (необязательные)
    $result = cot_mail($email, $L['usrnewnotify_subject'], $message, $headers, false, null, true);
    // `$result` — true, если письмо ушло, false — если ошибка
    if (!$result) {
        cot_log("UserNewNotify: Failed to send email to: {$email}", 'plug', 'error');
        $send_success = false;
        // НЕ ставим continue — пытаемся отправить остальным
    }
}
/* === 16. ФИНАЛЬНОЕ ОПРЕДЕЛЕНИЕ СТАТУСА === */
// `$send_success ? 'success' : 'error';`
// Это **тернарный оператор** — короткая форма if/else
// Читается так:
// ЕСЛИ $send_success === true → 'success'
// ИНАЧЕ → 'error'
$log_status = $send_success ? 'success' : 'error';
// Аналогично с сообщением
$log_message = $send_success ? 'Notification sent successfully' : 'Failed to send notification';
/* === 17. ЛОГИРОВАНИЕ СОБЫТИЯ === */
// В системный лог Cotonti
// Управление сайтом → Прочее → Лог
cot_log("UserNewNotify: Notification for user '{$user_name}' — status: {$log_status}", 'plug', $log_status);
// В собственную таблицу логов плагина
// Вызов функции плагина для записи собственной записи в таблицу логов плагина.
// Передаём ассоциативный массив с информацией о пользователе, статус и сообщение.
cot_usrnewnotify_log_event(
    ['user_id' => $user_id, 'user_name' => $user_name, 'user_email' => $user_email],
    $log_status,
    $log_message
);
