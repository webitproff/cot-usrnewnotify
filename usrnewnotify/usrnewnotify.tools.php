<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=tools
[END_COT_EXT]
==================== */
/**
 * User New Notify plugin for CMF Cotonti Siena v.0.9.26
 * Filename: usrnewnotify.tools.php
 * Purpose: Админ-панель: просмотр и фильтрация логов регистраций
 * @package usrnewnotify
 * @version 2.2.8
 * @author webitproff
 * @copyright (c) webitproff 2025
 * @license BSD
 */

/* === ЗАЩИТА ОТ ПРЯМОГО ДОСТУПА === */
// В Cotonti при загрузке любого файла через систему (например, через admin.php) автоматически определяется константа COT_CODE.
// Если файл открыть напрямую в браузере (например, http://site.com/plugins/usrnewnotify/usrnewnotify.tools.php),
// константа COT_CODE не будет определена, и скрипт немедленно завершится с ошибкой "Wrong URL.".
// Это базовая защита от несанкционированного запуска PHP-файлов вне системы CMS.
// Для новичка: `defined('COT_CODE')` — проверяет, существует ли константа. `or die(...)` — если нет, останавливает выполнение.
defined('COT_CODE') or die('Wrong URL.');

/* === ПОДКЛЮЧЕНИЕ ВСПОМОГАТЕЛЬНЫХ ФУНКЦИЙ ПЛАГИНА === */
// `cot_incfile('usrnewnotify', 'plug')` — функция Cotonti, которая возвращает полный путь к файлу
// внутри папки плагина (обычно plugins/usrnewnotify/inc/usrnewnotify.functions.php или подобный).
// `require_once` — подключает этот файл один раз, даже если он будет вызван повторно.
// Это нужно, чтобы использовать пользовательские функции, определённые в плагине (например, обработка уведомлений).
require_once cot_incfile('usrnewnotify', 'plug');

/* === ПОДКЛЮЧЕНИЕ ЯЗЫКОВОГО ФАЙЛА === */
// `cot_langfile('usrnewnotify', 'plug')` — возвращает путь к файлу с переводами для плагина
// (например, plugins/usrnewnotify/lang/usrnewnotify.ru.lang.php).
// После подключения становится доступен массив `$L`, где ключи — это идентификаторы строк,
// а значения — переведённые тексты на текущем языке сайта.
// Например: $L['Success'] = 'Успешно'; — можно использовать в шаблонах и коде.
require_once cot_langfile('usrnewnotify', 'plug');

/* === 1. РЕГИСТРАЦИЯ ТАБЛИЦЫ В СИСТЕМЕ COTONTI === */
// `Cot::$db->registerTable('usrnewnotify_logs')` — сообщает Cotonti, что существует таблица с логическим именем
// `usrnewnotify_logs`. Теперь к ней можно обращаться через `Cot::$db->usrnewnotify_logs`,
// и Cotonti автоматически подставит префикс базы данных (обычно `cot_`), например: `cot_usrnewnotify_logs`.
// Это позволяет писать переносимый код, не зависящий от конкретного префикса БД.
Cot::$db->registerTable('usrnewnotify_logs');

/* === 2. БЕЗОПАСНЫЙ ИМПОРТ ФИЛЬТРОВ ИЗ URL (GET-ПАРАМЕТРОВ) === */
// `cot_import()` — безопасная функция Cotonti для получения данных из $_GET, $_POST и других источников.
// Она очищает входные данные от вредоносного кода (XSS, SQL-инъекций и т.п.).
// Третий параметр — тип данных: 'TXT' означает текст.
// 'G' — источник: GET (данные из адресной строки).
// Фильтр по имени пользователя. Если админ ввёл имя в форму — оно попадёт в URL как ?filter_username=Ivan
$filter_username = cot_import('filter_username', 'G', 'TXT'); // Имя пользователя для фильтрации

// Фильтр по email
$filter_email = cot_import('filter_email', 'G', 'TXT'); // Email для фильтрации

// Фильтр по IP-адресу
$filter_ip = cot_import('filter_ip', 'G', 'TXT'); // IP-адрес для фильтрации

// Фильтр по стране (определяется по IP)
$filter_country = cot_import('filter_country', 'G', 'TXT'); // Страна для фильтрации

// Фильтр по статусу регистрации: 'success' или 'error'
$filter_status = cot_import('filter_status', 'G', 'TXT'); // Статус (success/error)

// `cot_import_date()` — специальная версия для дат. Преобразует строку вида "2025-10-30" в формат MySQL: "2025-10-30"
$filter_date_from = cot_import_date('filter_date_from', 'G'); // Дата ОТ (начало диапазона)
$filter_date_to = cot_import_date('filter_date_to', 'G'); // Дата ДО (конец диапазона)

/* === 3. ПАГИНАЦИЯ: ОПРЕДЕЛЕНИЕ ТЕКУЩЕЙ СТРАНИЦЫ И СМЕЩЕНИЯ === */
// `cot_import_pagenav('d', $max)` — это **вспомогательная функция**, которая читает параметр `d` из URL
// (например: ?d=40) и возвращает:
//   $pg — номер текущей страницы (1, 2, 3...)
//   $d  — смещение в базе данных (0, 20, 40...) — с какой записи начинать выборку
//   $durl — готовая часть URL для ссылок (например: "d=40")
// Это **не сама пагинация**, а только **парсер текущего состояния**.
// Второй параметр — сколько записей на страницу (берём из глобальных настроек).
// Для новичка: представь, что у тебя 100 логов, по 20 на страницу — тогда:
//   страница 1 → d=0
//   страница 2 → d=20
//   страница 3 → d=40
list($pg, $d, $durl) = cot_import_pagenav('d', Cot::$cfg['maxrowsperpage']);

// Явно сохраняем, сколько логов показывать на одной странице.
// `(int)` — принудительно превращаем в число, чтобы избежать ошибок.
// `Cot::$cfg['maxrowsperpage']` — это глобальная настройка сайта.
// Путь в админке: Управление → Конфигурация → Настройки сайта → "Макс. количество элементов на страницу"
$logs_per_page = (int) Cot::$cfg['maxrowsperpage'];


/* === 4. ИНИЦИАЛИЗАЦИЯ ШАБЛОНА XTemplate === */
// `cot_tplfile('usrnewnotify.tools', 'plug')` — возвращает путь к файлу шаблона плагина
// (например, plugins/usrnewnotify/tpl/usrnewnotify.tools.tpl).
// `new XTemplate(...)` — создаёт объект шаблонизатора. Теперь можно заполнять теги {TAG} данными.
$t = new XTemplate(cot_tplfile('usrnewnotify.tools', 'plug'));

/* === 5. ФОРМИРОВАНИЕ УСЛОВИЯ WHERE ДЛЯ SQL-ЗАПРОСА === */
// Начинаем с условия, которое всегда истинно: `1 = 1`. Это нужно, чтобы можно было добавлять
// новые условия через `AND` без проверки — есть ли уже условие или нет.
// Переменная `$where` будет содержать строку вида: "1 = 1 AND log_user_name LIKE '%Ivan%' AND ..."
$where = '1 = 1';

// Если пользователь ввёл имя — добавляем фильтр по полю `log_user_name`.
// `Cot::$db->prep()` — экранирует значение для безопасной вставки в SQL (защита от инъекций).
// `LIKE '%...%'` — поиск по подстроке (содержит).
if (!empty($filter_username)) {
    $where .= " AND log_user_name LIKE '%" . Cot::$db->prep($filter_username) . "%'";
}

// Аналогично для email
if (!empty($filter_email)) {
    $where .= " AND log_user_email LIKE '%" . Cot::$db->prep($filter_email) . "%'";
}

// По IP
if (!empty($filter_ip)) {
    $where .= " AND log_ip LIKE '%" . Cot::$db->prep($filter_ip) . "%'";
}

// По стране
if (!empty($filter_country)) {
    $where .= " AND log_country LIKE '%" . Cot::$db->prep($filter_country) . "%'";
}

// По статусу — только если значение 'success' или 'error' (защита от подмены)
if (!empty($filter_status) && in_array($filter_status, ['success', 'error'])) {
    $where .= " AND log_status = '" . Cot::$db->prep($filter_status) . "'";
}

// По дате ОТ — включаем весь день с 00:00:00
if (!empty($filter_date_from)) {
    $where .= " AND log_date >= '" . $filter_date_from . " 00:00:00'";
}

// По дате ДО — включаем весь день до 23:59:59
if (!empty($filter_date_to)) {
    $where .= " AND log_date <= '" . $filter_date_to . " 23:59:59'";
}

/* === 6. ПОЛУЧЕНИЕ РЕАЛЬНОГО ИМЕНИ ТАБЛИЦЫ С ПРЕФИКСОМ === */
// После `registerTable` свойство `Cot::$db->usrnewnotify_logs` содержит полное имя таблицы с префиксом.
// Например: `cot_usrnewnotify_logs`. Сохраняем в переменную для удобства.
$db_usrnewnotify_logs = Cot::$db->usrnewnotify_logs; // Например: cot_usrnewnotify_logs

/* === 7. ПОДСЧЁТ ОБЩЕГО КОЛИЧЕСТВА ЗАПИСЕЙ ПОД ФИЛЬТРЫ === */
// `COUNT(*)` — считает количество строк.
// `fetchColumn()` — возвращает значение из первой колонки первой строки (здесь — число).
// `(int)` — приводим к целому числу. Нужно для пагинации.
$totallines = (int) Cot::$db->query("SELECT COUNT(*) FROM $db_usrnewnotify_logs WHERE $where")->fetchColumn();

/* === 8. ВЫБОРКА ЛОГОВ ДЛЯ ТЕКУЩЕЙ СТРАНИЦЫ === */
// Формируем SQL-запрос:
// - SELECT * — все поля из таблицы
// - WHERE $where — применяем фильтры
// - ORDER BY log_date DESC — сортировка от новых к старым
// - LIMIT $d, $logs_per_page — пропустить $d записей, взять следующие $logs_per_page
$sql = Cot::$db->query("
    SELECT * FROM $db_usrnewnotify_logs
    WHERE $where
    ORDER BY log_date DESC
    LIMIT $d, $logs_per_page
");

/* === 9. ГЕНЕРАЦИЯ ПАГИНАЦИИ ДЛЯ АДМИНКИ === */
// `cot_pagenav()` — **основная функция построения пагинации в Cotonti**.
// Она **не просто считает страницы**, а **генерирует HTML-код ссылок** (назад, вперёд, номера страниц).
// Возвращает массив, например:
//   ['main'] — блок с номерами страниц: 1 .. 4 5 6 .. 10
//   ['prev'] — кнопка «Назад»
//   ['next'] — кнопка «Вперёд»
//   ['current'] — текущая страница
//   ['total'] — всего страниц
//   ['onpage'] — сколько записей показано на этой странице
//   и т.д.

// ВАЖНО: Первый параметр — **тип URL**:
//   'plug' — для фронтенда (plug.php?r=plugin)
//   'admin' — для админки (admin.php?m=...&p=...)
// Мы в админке → используем **'admin'**

// Второй параметр — **массив параметров URL**.
// Он нужен, чтобы **все фильтры и настройки сохранялись** при переходе по страницам.
// Без этого: нажмёшь "страница 2" → фильтр по имени пропадёт → покажет все логи.
// Пример итогового URL:
// admin.php?m=other&p=usrnewnotify&filter_username=Ivan&filter_status=success&d=20

// Почему не 'r', а 'p'? 
//   На фронте: plug.php?r=usrnewnotify
//   В админке: admin.php?m=other&p=usrnewnotify
//   → 'p' — это аналог 'r' для админки

// Динамическая пагинация:
//   Функция сама решает, сколько ссылок показывать (по 3 с каждой стороны от текущей).
//   Пример: текущая страница 5 → покажет 2 3 4 5 6 7 8
//   Если страниц мало — покажет все.
//   Если много — добавит "..." (пробелы).

// Кастомная пагинация:
//   В начале функции `cot_pagenav()` есть проверка:
//     if (function_exists('cot_pagenav_custom')) { return cot_pagenav_custom(...); }
//   → Это значит: если в плагине или теме есть своя версия `cot_pagenav_custom()`,
//     Cotonti использует **её вместо стандартной**.
//   → Это и есть **кастомная пагинация** — можно полностью переопределить внешний вид.
//   В нашем случае — **нет кастомной функции** → используется **стандартная динамическая**.

// AJAX-поддержка:
//   Если включён jQuery и `turnajax = true` → ссылки получают атрибут `rel="get-..."`
//   → При клике страница не перезагружается, а подгружается через AJAX.
//   Это ускоряет работу, но требует правильной настройки шаблона.

$pagenav = cot_pagenav(
    'admin', // ← Мы в АДМИНКЕ → используем admin.php
    [
        // === Обязательные параметры для админки ===
        'm' => 'other',           // Модуль админки: 'other' — это обработчик плагинов
        'p' => 'usrnewnotify',    // Имя плагина — аналог 'r' на фронте

        // === Сохраняем ВСЕ фильтры при переключении страниц ===
        // Без них — фильтры сбросятся при переходе на другую страницу
        'filter_username' => $filter_username,
        'filter_email' => $filter_email,
        'filter_ip' => $filter_ip,
        'filter_country' => $filter_country,
        'filter_status' => $filter_status,
        'filter_date_from' => $filter_date_from,
        'filter_date_to' => $filter_date_to,
    ],
    $d,               // Текущее смещение (0, 20, 40...)
    $totallines,      // Общее количество записей (из COUNT(*))
    $logs_per_page,   // Сколько на страницу
    'd',              // Имя параметра пагинации в URL (d=20)
    '',               // Хэш (#anchor) — не используется
    Cot::$cfg['jquery'] && Cot::$cfg['turnajax'] // Включить AJAX?
);

/* === 9.1. ОБРАБОТКА РЕЗУЛЬТАТОВ ЗАПРОСА === */
// `$sql->fetchAll()` — получает все строки результата в виде массива массивов.
// `foreach (... as $log)` — перебирает каждую запись.
// `$i = 0` — счётчик для нумерации строк и чередования цвета (odd/even).
$i = 0;
foreach ($sql->fetchAll() as $log) {
    $i++; // Увеличиваем счётчик

    // `$t->assign()` — заполняет теги в шаблоне значениями.
    // Пример: {LOG_USER_NAME} в шаблоне заменится на имя пользователя.
    $t->assign([
        'LOG_ID' => (int)$log['log_id'], // ID лога
        'LOG_USER_ID' => (int)$log['log_user_id'], // ID пользователя
        // `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` — экранирует HTML-теги, кавычки и т.д.
        // `?? ''` — если значение null, подставляем пустую строку
        'LOG_USER_NAME' => htmlspecialchars($log['log_user_name'] ?? '', ENT_QUOTES, 'UTF-8'),
        'LOG_USER_EMAIL' => htmlspecialchars($log['log_user_email'] ?? '', ENT_QUOTES, 'UTF-8'),
        'LOG_IP' => htmlspecialchars($log['log_ip'] ?? '', ENT_QUOTES, 'UTF-8'),
        // `?: 'N/A'` — если пусто, показываем "N/A"
        'LOG_DEVICE' => htmlspecialchars($log['log_device'] ?: 'N/A', ENT_QUOTES, 'UTF-8'),
        'LOG_BROWSER' => htmlspecialchars($log['log_browser'] ?: 'N/A', ENT_QUOTES, 'UTF-8'),
        'LOG_COUNTRY' => htmlspecialchars($log['log_country'] ?: 'N/A', ENT_QUOTES, 'UTF-8'),
        // `cot_date()` — форматирует дату по шаблону (из настроек)
        // `strtotime()` — превращает строку даты в timestamp
        'LOG_DATE' => cot_date('datetime_full', strtotime($log['log_date'])),
        // Тернарный оператор: если статус 'success' — $L['Success'], иначе $L['Error']
        'LOG_STATUS' => $log['log_status'] === 'success' ? $L['Success'] : $L['Error'],
        'LOG_MESSAGE' => htmlspecialchars($log['log_message'] ?? '', ENT_QUOTES, 'UTF-8'),
        // `cot_url()` — генерирует URL. Здесь — ссылка на профиль пользователя
        'LOG_PROFILE_URL' => cot_url('users', ['m' => 'details', 'id' => $log['log_user_id']], '', true),
        // `cot_build_oddeven($i)` — возвращает 'odd' или 'even' для чередования цвета строк
        'LOG_ODDEVEN' => cot_build_oddeven($i),
        'LOG_I' => $i, // Порядковый номер строки
    ]);
	
    // parse — обрабатывает указанный блок шаблона, чтобы вставить данные в HTML.
    // 'MAIN.LOGS_ROW' — это путь к блоку в шаблоне: MAIN — основной блок, LOGS_ROW — его часть.
    // `parse('MAIN.LOGS_ROW')` — обрабатывает блок LOGS_ROW внутри MAIN и добавляет его в вывод.
    // Это как "добавить одну строку таблицы".
    $t->parse('MAIN.LOGS_ROW');
}

/* === 9.2. ОСВОБОЖДЕНИЕ РЕСУРСОВ БАЗЫ ДАННЫХ === */
// После выполнения SQL-запроса через Cot::$db->query() возвращается объект типа PDOStatement.
// Этот объект — это "указатель" (cursor) на результат запроса.
// Он держит в памяти:
//   - данные о соединении с базой,
//   - состояние выполнения,
//   - возможно, временные буферы результата.

// $sql->fetchAll() — мы уже **получили все строки** в массив.
// Значит, больше работать с результатом не будем.

// $sql->closeCursor() — **явно закрывает курсор**.
// Что это даёт?
// 1. **Освобождает память** на сервере MySQL (если был большой результат).
// 2. **Разрешает выполнять новые запросы** к той же базе через это соединение.
//    Без closeCursor() PDO может "думать", что запрос ещё активен → ошибка:
//       "Cannot execute queries while other unbuffered queries are active"
// 3. **Хорошая практика** — особенно в циклах или при долгих скриптах.

// Для новичка:
// Представь, что ты взял книгу в библиотеке (открыл курсор).
// Прочитал её всю (fetchAll).
// $sql->closeCursor() — это как **вернуть книгу на полку**.
// Теперь можно взять другую.

// ВАЖНО: после fetchAll() это **не обязательно**, потому что PHP автоматически закроет курсор
// при уничтожении объекта $sql (в конце скрипта).
// Но **явное закрытие — надёжнее и чище**, особенно если дальше будут другие запросы.

// Пример плохого кода:
//   $res1 = $db->query("SELECT ..."); $res1->fetchAll();
//   $res2 = $db->query("SELECT ..."); // ← ОШИБКА! Первый запрос ещё "висит"
// Пример хорошего:
//   $res1 = $db->query("SELECT ..."); $res1->fetchAll(); $res1->closeCursor();
//   $res2 = $db->query("SELECT ..."); // ← Работает!

$sql->closeCursor();

/* === 10. ЗАПОЛНЕНИЕ ФОРМЫ ФИЛЬТРОВ ЗНАЧЕНИЯМИ === */
// Заполняем поля формы текущими значениями фильтров, чтобы они не сбрасывались при отправке.
$t->assign([
    // URL формы — отправка на эту же страницу в админке
    'FILTER_ACTION_URL' => cot_url('admin', ['m' => 'other', 'p' => 'usrnewnotify'], '', true),
    // Подставляем значения, экранируя их
    'FILTER_USERNAME' => htmlspecialchars($filter_username ?? '', ENT_QUOTES, 'UTF-8'),
    'FILTER_EMAIL' => htmlspecialchars($filter_email ?? '', ENT_QUOTES, 'UTF-8'),
    'FILTER_IP' => htmlspecialchars($filter_ip ?? '', ENT_QUOTES, 'UTF-8'),
    'FILTER_COUNTRY' => htmlspecialchars($filter_country ?? '', ENT_QUOTES, 'UTF-8'),
    // Для select и дат — можно не экранировать, если они уже в нужном формате
    'FILTER_STATUS' => $filter_status ?? '',
    'FILTER_DATE_FROM' => $filter_date_from ?? '',
    'FILTER_DATE_TO' => $filter_date_to ?? '',
]);

/* === 11. ПЕРЕДАЧА ДАННЫХ ПАГИНАЦИИ В ШАБЛОН === */
// `$t->assign()` — передаёт данные в шаблон XTemplate.
// Мы передаём только основные элементы пагинации:
$t->assign([
    'PAGINATION' => $pagenav['main'], // ← Весь блок с номерами страниц: 1 .. 4 5 6 .. 10
    'PREVIOUS_PAGE' => $pagenav['prev'], // ← Кнопка «Назад» (или пусто, если на 1-й странице)
    'NEXT_PAGE' => $pagenav['next'], // ← Кнопка «Вперёд»
    'TOTAL_ENTRIES' => $totallines, // ← Всего записей (например, 156)
    'ENTRIES_ON_CURRENT_PAGE' => $i, // ← Сколько показано на этой странице (макс. $logs_per_page)
]);

// `cot_generatePaginationTags($pagenav)` — **вспомогательная функция**.
// Она **дополняет** `$t->assign()` и создаёт **множество тегов** для гибкого использования в шаблоне:
//   {PAGINATION}, {PREVIOUS_PAGE}, {NEXT_PAGE}
//   {CURRENT_PAGE}, {TOTAL_PAGES}, {ENTRIES_PER_PAGE}
//   {PAGE_1}, {PAGE_2}, {PAGE_3} ... — ссылки на конкретные страницы
// Это нужно, если в шаблоне хочется **свой дизайн пагинации**.
// Например: вывести только номера, без "Назад/Вперёд".
// Функция **не обязательна**, но удобна.
// Внутри она просто перебирает массив `$pagenav` и формирует теги с префиксом.
$t->assign(cot_generatePaginationTags($pagenav));

/* === 12. Нет логов === */
// Если не найдено ни одной записи (i == 0), то парсим блок шаблона MAIN.NO_LOGS.
// Что значит 'MAIN.NO_LOGS'? — это путь к блоку внутри шаблона, где точка разделяет уровни.
// Например, MAIN — это главный раздел, а NO_LOGS — вложенный подблок.
if ($i == 0) {
    $t->parse('MAIN.NO_LOGS');
}

/* === 13. ФИНАЛИЗАЦИЯ: ВЫВОД СООБЩЕНИЙ И HTML === */
// `cot_display_messages($t)` — добавляет в шаблон системные уведомления (из сессии)
// Например: "Фильтры применены", "Ошибка доступа" и т.п.
cot_display_messages($t);

// Парсим основной блок — собираем весь HTML
$t->parse('MAIN');

// `$t->text('MAIN')` — возвращает готовый HTML-код как строку
// Эта строка будет выведена в админ-панели Cotonti как содержимое плагина

/* === ЧТО ТАКОЕ $pluginBody? ПОДРОБНО ИЗ system/plugin.php === */
// В Cotonti **все плагины** (включая наш `usrnewnotify`) загружаются через файл:
// system/plugin.php — это **центральный загрузчик плагинов**.

// Переменная **$pluginBody** — это **глобальная переменная**, в которую **плагин должен записать HTML-контент**.
// Она используется в `plugin.php` как **"резервный выход"**, если шаблон не используется или не удалось его вывести.

// === Как это работает в system/plugin.php ===
// 1. Сначала пытается загрузить **шаблон** (XTemplate):
//    $t = new XTemplate(...);
// 2. Затем выполняет код плагина (наш файл usrnewnotify.tools.php).
// 3. **Плагин может**:
//    - Заполнить шаблон `$t` → `$t->parse('MAIN'); $t->out('MAIN');`
//    - ИЛИ записать HTML в **$pluginBody** → `echo $pluginBody;`
// 4. В конце `plugin.php` делает:
//    if ($t) { $t->out('MAIN'); } else { echo $pluginBody; }

// === В НАШЕМ СЛУЧАЕ ===
// Мы **используем шаблон** (`usrnewnotify.tools.tpl`) → создаём `$t`.
// Мы **не выводим напрямую через echo**.
// Но **$pluginBody** — это **обязательная переменная для совместимости**.
// Даже если мы используем шаблон, **мы должны присвоить $pluginBody**.

// Почему?
// - В `plugin.php` есть проверка: `if (isset($t) && is_object($t)) { $t->out('MAIN'); } else { echo $pluginBody; }`
// - Если `$t` есть — выводит шаблон.
// - Если `$t` нет (например, ошибка) — выводит `$pluginBody` как fallback.

// === Наш код: ===
// $pluginBody = $t->text('MAIN');
// → Мы **собираем весь HTML из шаблона в строку** и кладём в `$pluginBody`.
// → Это **не обязательно для вывода** (шаблон выведется сам), но:
//   - Совместимо с legacy-режимом
//   - Работает как резерв, если `$t` по какой-то причине не сработает
//   - Используется в `plugin.php` для `BODY` тега в popup-режиме

// === Для новичка: ===
// Представь, что ты готовишь блюдо (HTML).
// У тебя есть красивая тарелка (XTemplate $t) — ты красиво выкладываешь еду.
// Но на всякий случай ты **фотографируешь блюдо** и сохраняешь фото в `$pluginBody`.
// Если тарелка разобьётся — покажешь фото.
// В реальности тарелка не разобьётся — но фото всё равно нужно сделать (по правилам Cotonti).

// === Итог: ===
// `$pluginBody` — это **HTML-контент плагина в виде строки**.
// Он **не обязателен при использовании XTemplate**, но **обязателен для совместимости**.
// В `plugin.php` он используется как **резервный вывод**.
// Мы присваиваем: `$pluginBody = $t->text('MAIN');` → **всё корректно**.

$pluginBody = $t->text('MAIN');
