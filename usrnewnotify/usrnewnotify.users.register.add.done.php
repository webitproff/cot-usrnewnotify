<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=users.register.add.done
[END_COT_EXT]
==================== */
/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26, PHP v.8.4+, MySQL v.8.0
 * Filename: usrnewnotify.users.register.add.done.php
 * Purpose: Логирует регистрацию пользователя и отправляет уведомления администратору.
 *          Использует переменные $ruser (массив данных формы) и $userid (ID нового пользователя).
 *          Гарантирует корректный user_id для формирования ссылки на профиль с полным доменом.
 *          Поддерживает HTML-уведомления. Вызывается сразу после успешного создания пользователя
 *          через cot_add_user() в users.register.php — то есть, после отправки формы регистрации.
 * Date: 2025-10-30
 * @package usrnewnotify
 * @version 2.2.8
 * @author webitproff
 * @copyright Copyright (c) webitproff 2025
 * @license BSD
 */

/* === ЗАЩИТА ОТ ПРЯМОГО ДОСТУПА === */
// В Cotonti при загрузке любого файла через систему (например, через хук) автоматически определяется константа COT_CODE.
// Если файл открыть напрямую в браузере (например, http://site.com/plugins/usrnewnotify/usrnewnotify.users.register.add.done.php),
// константа COT_CODE не будет определена, и скрипт немедленно завершится с ошибкой "Wrong URL.".
// Это базовая защита от несанкционированного запуска PHP-файлов вне системы CMS.
// Для новичка: `defined('COT_CODE')` — проверяет, существует ли константа. `or die(...)` — если нет, останавливает выполнение.
defined('COT_CODE') or die('Wrong URL.');

/* === 1. РЕГИСТРАЦИЯ ТАБЛИЦЫ ЛОГОВ ПЛАГИНА === */
// `Cot::$db->registerTable('usrnewnotify_logs')` — сообщает Cotonti, что существует таблица с логическим именем
// `usrnewnotify_logs`. Теперь к ней можно обращаться через `Cot::$db->usrnewnotify_logs`,
// и Cotonti автоматически подставит префикс базы данных (обычно `cot_`), например: `cot_usrnewnotify_logs`.
// Это позволяет писать переносимый код, не зависящий от конкретного префикса БД.
// Без этого — при обращении к таблице будет ошибка "Table not found".
/* 
	в старых плагинах или модулях это можно было увидеть в таком виде (внимание на $db_x и конкатенацию)
	global $db_structure, $db_forum_stats, $db_forum_structure, $db_forum_sections, $db_x;
	// Old forum table names, required for update
	$db_forum_structure	= isset($db_forum_structure) ? $db_forum_structure : $db_x . 'forum_structure'; // ← ручное формирование имени
	$db_forum_sections = isset($db_forum_sections) ? $db_forum_sections : $db_x . 'forum_sections'; // ← ручное формирование имени
	или $db_pm = (isset($db_pm)) ? $db_pm : $db_x . 'pm'; // ← ручное формирование имени
	или $exists = $db->query("SELECT COUNT(*) FROM {$db_x}subscriptcatforum WHERE user_id = ? AND cat_forum_subs = ?", [$user_id, $category_id])->fetchColumn();
	В Cotonti 0.9+ — используем registerTable, чтобы не писать $db_x везде.
*/
Cot::$db->registerTable('usrnewnotify_logs');

/* === 2. ПОДКЛЮЧЕНИЕ ВСПОМОГАТЕЛЬНЫХ ФАЙЛОВ ПЛАГИНА === */
// `cot_incfile('usrnewnotify', 'plug')` — функция Cotonti, которая возвращает полный путь к файлу
// внутри папки плагина (обычно plugins/usrnewnotify/inc/usrnewnotify.functions.php).
// `require_once` — подключает этот файл один раз, даже если он будет вызван повторно.
// Здесь подключаются **пользовательские функции плагина**, например:
//   cot_usrnewnotify_detect_device(), cot_usrnewnotify_log_event() и др.
// Без этого — вызов этих функций вызовет ошибку "Call to undefined function".
require_once cot_incfile('usrnewnotify', 'plug');

/* === 3. ПОДКЛЮЧЕНИЕ ЯЗЫКОВОГО ФАЙЛА === */
// `cot_langfile('usrnewnotify', 'plug')` — возвращает путь к файлу с переводами для плагина
// (например, plugins/usrnewnotify/lang/usrnewnotify.ru.lang.php).
// После подключения становится доступен массив `$L`, где ключи — это идентификаторы строк,
// а значения — переведённые тексты на текущем языке сайта.
// Например: $L['usrnewnotify_subject'] = 'Новый пользователь зарегистрировался';
// Используется для темы письма и тела сообщения.
require_once cot_langfile('usrnewnotify', 'plug');

/* === 4. ПРОВЕРКА: ВКЛЮЧЕНЫ ЛИ УВЕДОМЛЕНИЯ? === */
// `Cot::$cfg['plugin']['usrnewnotify']['notify_enabled']` — настройка плагина в админке.
// Если уведомления отключены (пустое значение или false) — **ничего не делаем**.
// `return;` — немедленно завершает выполнение файла.
// Это экономит ресурсы: не будем делать запросы, отправлять письма и т.д.
if (empty(Cot::$cfg['plugin']['usrnewnotify']['notify_enabled'])) {
    return;
}

/* === 5. ПОЛУЧЕНИЕ ID НОВОГО ПОЛЬЗОВАТЕЛЯ === */
// `$userid` — переменная, которую **автоматически передаёт Cotonti** в хук `users.register.add.done`.
// Она содержит ID только что созданного пользователя (возвращается функцией `cot_add_user()`).
// `(int)$userid` — принудительно приводим к целому числу для безопасности.
// Если по какой-то причине $userid пустой — станет 0.
$user_id = (int)$userid;

/* === 6. ИЗВЛЕЧЕНИЕ ДАННЫХ ИЗ $ruser (ФОРМЫ РЕГИСТРАЦИИ) === */
// `$ruser` — массив с данными, которые пользователь ввёл в форму регистрации.
// Он передаётся из `users.register.php` через хук.
// Проверяем, есть ли нужные ключи, и **экранируем** значения для безопасного вывода в HTML.
$user_name = isset($ruser['user_name']) ? htmlspecialchars($ruser['user_name'], ENT_QUOTES, 'UTF-8') : 'N/A';
// `htmlspecialchars()` — защищает от XSS: превращает <script> в &lt;script&gt;.
// `ENT_QUOTES` — экранирует и одинарные, и двойные кавычки.
// Если имя не передано — ставим 'N/A'.
$user_email = isset($ruser['user_email']) ? htmlspecialchars($ruser['user_email'], ENT_QUOTES, 'UTF-8') : 'N/A';

$reg_date = isset($ruser['user_regdate']) 
    ? cot_date('datetime_full', $ruser['user_regdate']) 
    : cot_date('datetime_full', Cot::$sys['now']);
// `cot_date()` — форматирует дату по шаблону из настроек сайта.
// `Cot::$sys['now']` — текущее время сервера в формате timestamp.

/* === 7. РЕЗЕРВНАЯ ПРОВЕРКА ЧЕРЕЗ БАЗУ ДАННЫХ === */
// Если по какой-то причине данные из формы отсутствуют — делаем запрос в таблицу пользователей.
if ($user_name === 'N/A' || $user_email === 'N/A' || $user_id === 0) {
    // `Cot::$db->users` — полное имя таблицы пользователей (например, cot_users).
    // Используем **подготовленный запрос** (с ? и массивом значений) — защита от SQL-инъекций.
    $user = Cot::$db->query(
        "SELECT user_id, user_name, user_email, user_regdate FROM " . Cot::$db->users . " WHERE user_id = ?", 
        [$user_id]
    )->fetch();

    if ($user) {
        $user_id = (int)$user['user_id'];
        $user_name = htmlspecialchars($user['user_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $user_email = htmlspecialchars($user['user_email'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
        $reg_date = cot_date('datetime_full', $user['user_regdate'] ?? Cot::$sys['now']);
    }
}

/* === 8. ФОРМИРОВАНИЕ ССЫЛКИ НА ПРОФИЛЬ === */
// `cot_url()` — генерирует внутренний URL.
// `true` в конце — возвращает **относительный путь** (без домена).
// `Cot::$cfg['mainurl']` — полный адрес сайта (например, https://example.com).
// Получаем: https://example.com/users.php?m=details&id=123
$user_profile_url = $user_id > 0 
    ? Cot::$cfg['mainurl'] . '/' . cot_url('users', ['m' => 'details', 'id' => $user_id], '', true) 
    : 'N/A';

/* === 9. СБОР ИНФОРМАЦИИ О ПОСЕТИТЕЛЕ === */
$ip = filter_var($_SERVER['REMOTE_ADDR'] ?? 'N/A', FILTER_VALIDATE_IP) ?: 'N/A';
// `$_SERVER['REMOTE_ADDR']` — IP-адрес клиента.
// `filter_var(..., FILTER_VALIDATE_IP)` — проверяет, что это валидный IP.
// Если нет — 'N/A'.

$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
// Строка браузера (User-Agent): "Mozilla/5.0 (Windows NT 10.0; Win64; x64)..."

$device = cot_usrnewnotify_detect_device($user_agent);
// Пользовательская функция: определяет тип устройства (Mobile, Tablet, Desktop).
$browser = cot_usrnewnotify_detect_browser($user_agent);
// Определяет браузер и версию (например, "Chrome 128").
$country = cot_usrnewnotify_detect_country($ip);
// По IP определяет страну (через GeoIP или внешний сервис).

/* === 10. ОПРЕДЕЛЕНИЕ СТАТУСА РЕГИСТРАЦИИ === */
// Определяем статус регистрации
// Здесь читаем глобальную настройку Cotonti: если регистрация не требует подтверждения по email
// Управление сайтом -> Расширения -> Users -> Конфигурация
// поле "Утверждение новых учетных записей администратором"
// (regnoactivation = true), то считаем регистрацию успешной. Иначе — ставим 'error' (ожидание подтверждения).
// `Cot::$cfg['users']['regnoactivation']` — глобальная настройка:
//   true = регистрация без подтверждения по email
//   false = требуется подтверждение
$log_status = Cot::$cfg['users']['regnoactivation'] ? 'success' : 'error';
$log_message = Cot::$cfg['users']['regnoactivation'] 
    ? 'Registration completed' 
    : 'Pending email validation';
	
	
/* === 11. ПРОВЕРКА КРИТИЧЕСКИХ ОШИБОК === */
// Условие: если **хотя бы одно** из трёх значений — "плохое", то считаем ошибку.
// `||` — это **логическое ИЛИ** (OR)
// Читается так:
//   "Если имя = 'N/A' ИЛИ email = 'N/A' ИЛИ ID = 0 — тогда ошибка"
// `===` — **строгое сравнение** (не только значение, но и тип)
//   'N/A' — это строка, 0 — это число → всё чётко.
// Если после всех попыток какие-то данные всё ещё отсутствуют — переопределяем статус как ошибка
// и записываем системный лог (cot_log) с уровнем 'error'. Внимание: здесь в сообщении мы вставляем $ruser —
// если $ruser — массив, при приведении к строке это может дать "Array"; это используется для отладки.
if ($user_name === 'N/A' || $user_email === 'N/A' || $user_id === 0) {
    // `'error'` — это **строка текста**, просто значение, которое мы используем как **метку статуса**.
    // Это **не зарезервированное слово**, а просто договорённость:
    //   'success' — всё хорошо
    //   'error' — что-то пошло не так
    // Мы можем писать `'critical'`, `'warning'` — главное, чтобы везде одинаково.
    $log_status = 'error'; 

    // Текстовое описание ошибки — будет записано в лог
    $log_message = 'Missing or invalid user data';

    // `cot_log()` — функция Cotonti, пишет в системный лог.
    // Параметры:
    //   1. Текст сообщения
    //   2. Категория (plug — плагин)
    //   3. Уровень (error — ошибка)
    // В админке: Логи → увидим эту запись.
    cot_log("UserNewNotify: \$ruser is incomplete in users.register.add.done", 'plug', 'error');
}

/* === 12. ПОДГОТОВКА ШАБЛОНА ПИСЬМА === */
// Формируем массив замен для шаблона письма
// Массив $replacements содержит пары "тег => значение", которые будут подставлены в шаблон письма.
// Шаблон (строка с плейсхолдерами вроде {USER_NAME}) содержится в файлах локализации $L.
// `$replacements` — это **массив замен** (ключ → значение)
// Он нужен, чтобы в шаблоне письма писать `{USER_NAME}`, а не конкретные данные.
// Потом мы **одним махом** заменим все плейсхолдеры на реальные значения.
$replacements = [
    // Ключ 'USER_NAME' → будет заменён на значение $user_name
    // Откуда $user_name? → из строки выше: $user_name = ... (из формы или БД)
    'USER_NAME' => $user_name,

    // `Cot::$cfg['maintitle'] ?? 'N/A'`
    // `??` — **оператор нулевого слияния** (null coalescing)
    // Читается: "Возьми Cot::$cfg['maintitle'], но если его нет — возьми 'N/A'"
    // Cot::$cfg['maintitle'] — название сайта из настроек (например, "Мой сайт")
    'MAINTITLE' => Cot::$cfg['maintitle'] ?? 'N/A',

    // Аналогично: URL сайта
    'MAINURL' => Cot::$cfg['mainurl'] ?? 'N/A',

    // $user_email — берём из формы или БД (выше)
    'USER_EMAIL' => $user_email,

    // $user_profile_url — мы сформировали выше через cot_url()
    'USER_PROFILE_URL' => $user_profile_url,

    // $ip — из $_SERVER['REMOTE_ADDR'], проверен через filter_var
    'IP_VISITOR' => $ip,

    // $reg_date — дата регистрации, отформатирована через cot_date()
    'USER_REGDATE' => $reg_date,

    // $device, $browser, $country — из функций детекции (cot_usrnewnotify_...)
    'USER_DEVICE' => $device,
    'USER_BROWSER' => $browser,
    'USER_COUNTRY' => $country,
];
// Пример: в шаблоне письма написано: "Пользователь: {USER_NAME}" (дальше фигурные скобки будут очищенны - зависание плейсхолдеров)
// После замены → "Пользователь: ivan271superpupkin"

/* === 13. ФОРМИРОВАНИЕ СПИСКА ПОЛУЧАТЕЛЕЙ === */
// Берём основной email администратора из конфигурации Cotonti.
// `$admin_emails = [Cot::$cfg['adminemail']];`
// Квадратные скобки `[]` — это **короткий синтаксис создания массива** (с PHP 5.4+)
// Раньше писали: array(Cot::$cfg['adminemail'])
// Сейчас: [Cot::$cfg['adminemail']]
// → Создаём массив из одного элемента — email админа из настроек сайта.
$admin_emails = [Cot::$cfg['adminemail']]; // Например: ['admin@site.com']

// === РАЗБОР СЛОЖНОЙ СТРОКИ ПО ЧАСТЯМ ===
// Если в настройках плагина указаны дополнительные адреса через запятую, добавляем их к списку.
// explode разбивает строку по запятой, array_map('trim', ...) убирает пробелы, array_filter отбрасывает пустые элементы.
if (!empty(Cot::$cfg['plugin']['usrnewnotify']['notify_email'])) {
    // Допустим, в настройках плагина написано: "admin2@site.com, support@site.com ,  "
    // Мы хотим превратить эту строку в массив чистых email'ов.

    // 1. `explode(',', $string)` — разбивает строку по запятой
    //    Результат: ['admin2@site.com', ' support@site.com', '  ']
    // 2. `array_map('trim', $array)` — применяет функцию trim() к **каждому элементу**
    //    trim() — убирает пробелы в начале и в конце строки
    //    Результат: ['admin2@site.com', 'support@site.com', '']
    // 3. `array_filter($array)` — **убирает пустые элементы**
    //    (по умолчанию удаляет '', null, false, 0)
    //    Результат: ['admin2@site.com', 'support@site.com']
    $extra = array_filter(array_map('trim', explode(',', Cot::$cfg['plugin']['usrnewnotify']['notify_email'])));

    // `array_merge($a, $b)` — **объединяет два массива**
    // $admin_emails = ['admin@site.com']
    // $extra = ['admin2@site.com', 'support@site.com']
    // Результат: ['admin@site.com', 'admin2@site.com', 'support@site.com']
    $admin_emails = array_merge($admin_emails, $extra);
}


// Убираем дубликаты с помощью array_unique и снова отфильтровываем пустые строки (strlen > 0).
// Финальная очистка:
// `array_filter($admin_emails, 'strlen')` — оставляет только строки, где длина > 0
// `array_unique()` — убирает дубликаты
// → Гарантируем, что в массиве только уникальные, непустые email'ы
$admin_emails = array_unique(array_filter($admin_emails, 'strlen'));


/* === 14. ПРОВЕРКА: ЕСТЬ ЛИ КУДА ОТПРАВЛЯТЬ? === */
// `empty($admin_emails)` — проверяет, пустой ли массив.
// Если **ни одного валидного email** — значит, некуда отправлять уведомление.
// Что делаем:
// 1. Пишем в системный лог Cotonti (видно в админке → Логи)
// 2. Записываем событие в **свою таблицу логов** плагина
// 3. `return;` — **выходим из файла**, больше ничего не делаем
// Если получился пустой список email'ов — записываем об этом в системный лог и в собственный лог плагина
// (cot_usrnewnotify_log_event — функция плагина), затем выходим, потому что отправлять некуда.
if (empty($admin_emails)) {
    cot_log('UserNewNotify: Admin email is not configured.', 'plug', 'error');
    cot_usrnewnotify_log_event(
        ['user_id' => $user_id, 'user_name' => $user_name, 'user_email' => $user_email],
        'error',
        'Admin email not configured'
    );
    return;
}

/* === 15. НАСТРОЙКИ ОТПРАВКИ === */
// `$send_success = true;` — **флаг (переключатель)**: "всё ли прошло успешно?"
// Изначально думаем, что **да** — отправка пройдёт.
// Позже, если хоть одно письмо не уйдёт — поставим `false`.
// Инициализируем флаг успешной отправки
// Флаг, который будет указывать, были ли все отправки успешными. Изначально считаем успех.
$send_success = true;

// `$format = ... ?? 'text';` — **оператор нулевого слияния** (null coalescing operator)
// Читается так:
//   "Возьми значение из настроек, но если его нет или оно пустое — возьми 'text'"
// То есть: если админ не выбрал формат — будет обычный текст, а не HTML.
// Формат уведомления — читается из настроек плагина: 'html' или 'text'. По умолчанию — текстовый.
$format = Cot::$cfg['plugin']['usrnewnotify']['notify_format'] ?? 'text';

// `$headers` — массив заголовков письма.
// MIME-Version — стандарт
// Content-Type — **тип содержимого**:
//   Если $format === 'html' → 'text/html' (браузер покажет как страницу)
//   Иначе → 'text/plain' (простой текст)
// charset=UTF-8 — поддержка русских букв
// Настраиваем заголовки для письма
// Заголовки, которые будут переданы в функцию отправки почты. Если формат HTML — ставим соответствующий Content-Type.
$headers = [
    'MIME-Version' => '1.0',
    'Content-Type' => $format === 'html' ? 'text/html; charset=UTF-8' : 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => '8bit',
];

/* === 16. ФОРМИРОВАНИЕ ТЕЛА ПИСЬМА === */
// `$L['send_to_adminmail_html_message']` — строка из языкового файла:
//   "Новый пользователь: {USER_NAME}<br>Ссылка: {USER_PROFILE_URL}..."
// Мы заменяем **все плейсхолдеры** (типа {USER_NAME}) на реальные значения из $replacements.

// `array_keys($replacements)` → ['USER_NAME', 'USER_EMAIL', ...]
// `array_values($replacements)` → ['Иван', 'ivan@example.com', ...]
// `str_replace(ключи, значения, текст)` — заменяет все вхождения

// Тернарный оператор (?:) читается так:
//   ЕСЛИ $format === 'html' → бери HTML-шаблон
//   ИНАЧЕ → бери текстовый шаблон
// Выбираем шаблон для регистрации (без подтверждения)
// В зависимости от формата берём соответствующую локализационную строку ($L[...]) и заменяем плейсхолдеры
// ключами из массива $replacements. str_replace выполняет простую подстановку.

$message = $format === 'html'
    ? str_replace(array_keys($replacements), array_values($replacements), $L['send_to_adminmail_html_message'])
    : str_replace(array_keys($replacements), array_values($replacements), $L['send_to_adminmail_message']);


// Удаляем фигурные скобки из сообщения, что бы не было в письме типа 
// <tr><th>Профиль пользователя</th><td><a href="{https://example.com/users/1687?m=details}">Перейти к профилю</a></td></tr>
// внимание на фигурные скобки в ссылке href="{https://example.com/users/1687?m=details}"
// На всякий случай, если в шаблоне остались не заменённые плейсхолдеры вида {SOMETHING},
// заменяем их на 'N/A' чтобы не отправлять неполный шаблон.
// **Защита от "зависших" плейсхолдеров**
// Если в шаблоне остался {SOMETHING}, которого нет в $replacements — заменяем на 'N/A'
// `preg_replace('/\{[A-Z_]+\}/', 'N/A', $message)` — регулярное выражение:
//   Ищи {БОЛЬШИЕ_БУКВЫ_И_ПОДЧЁРКИВАНИЯ} → замени на 'N/A'
$message = preg_replace('/\{[A-Z_]+\}/', 'N/A', $message);

/* === 17. ОТПРАВКА ПИСЕМ КАЖДОМУ АДМИНУ === */
// Отправляем письмо каждому администратору
// `foreach ($admin_emails as $email)` — **цикл по массиву**:
//   $email по очереди принимает каждое значение из $admin_emails
//   Сначала — admin@site.com, потом — support@site.com и т.д.
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
    // cot_mail — стандартная функция Cotonti для отправки писем. Параметры: получатель, тема,
    // тело письма, заголовки и дополнительные флаги (здесь некоторые значения по умолчанию).
    // `cot_mail()` — безопасная отправка почты в Cotonti
    // Параметры:
    //   $email — кому
    //   $L['usrnewnotify_subject'] — тема (из языкового файла)
    //   $message — тело письма
    //   $headers — заголовки
    //   false, null, true — дополнительные флаги (необязательные)
    $result = cot_mail($email, $L['usrnewnotify_subject'], $message, $headers, false, null, true);

    // `$result` — true, если письмо ушло, false — если ошибка
    if (!$result) {
        cot_log("UserNewNotify: Failed to send email to: {$email}", 'plug', 'error');
        $send_success = false;
        // НЕ ставим continue — пытаемся отправить остальным
        // Если отправка вернула false — логируем ошибку и помечаем общий флаг как false.
    }
}

/* === 18. ФИНАЛЬНОЕ ОПРЕДЕЛЕНИЕ СТАТУСА === */
// `$send_success && $log_status === 'success' ? 'success' : 'error';`
// Это **тернарный оператор** — короткая форма if/else

// Читается так:
//   ЕСЛИ ($send_success === true) И ($log_status === 'success')
//      → ТОГДА $log_status = 'success'
//   ИНАЧЕ
//      → $log_status = 'error'

// Примеры:
//   $send_success = true, $log_status = 'success' → 'success'
//   $send_success = false, $log_status = 'success' → 'error'
//   $send_success = true, $log_status = 'error' → 'error'
// Обновляем статус и сообщение лога
// Если ранее регистрация считалась успешной и одновременно все письма отправлены успешно — статус success.
// Во всех остальных случаях — error.

$log_status = $send_success && $log_status === 'success' ? 'success' : 'error';

// Аналогично с сообщением
// Формируем человекочитаемое сообщение для логирования: если отправка прошла — говорим об успехе,
// иначе — указываем, что уведомление не было отправлено.
$log_message = $send_success 
    ? ($log_message === 'Pending email validation' ? $log_message : 'Notification sent successfully')
    : 'Failed to send notification';


/* === 19. ЛОГИРОВАНИЕ СОБЫТИЯ === */

// Логируем событие регистрации (новая запись) в системный лог Cotonti "Системный протокол" 
// Управление сайтом -> Прочее -> Лог
// Записываем запись в системный лог Cotonti. Уровень лога — $log_status (обычно 'success' или 'error').
cot_log("UserNewNotify: Notification for user '{$user_name}' — status: {$log_status}", 'plug', $log_status);

// В собственную таблицу логов плагина
// Вызов функции cot_usrnewnotify_log_event плагина для записи собственной записи в таблицу логов плагина.
// Передаём ассоциативный массив с информацией о пользователе, статус и сообщение.
cot_usrnewnotify_log_event(
    ['user_id' => $user_id, 'user_name' => $user_name, 'user_email' => $user_email],
    $log_status,
    $log_message
);
