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
 * @version 2.2.0
 * @author webitproff
 * @copyright (c) webitproff 2025
 * @license BSD
 */
defined('COT_CODE') or die('Wrong URL.');
// Подключаем функции плагина
require_once cot_incfile('usrnewnotify', 'plug');
// Загружаем языковой файл
require_once cot_langfile('usrnewnotify', 'plug');
/* === 1. РЕГИСТРАЦИЯ ТАБЛИЦЫ === */
Cot::$db->registerTable('usrnewnotify_logs'); // Регистрируем нашу таблицу, чтобы Cotonti знал её префикс.
/* === 2. Импорт фильтров === */
$filter_username = cot_import('filter_username', 'G', 'TXT');
$filter_email = cot_import('filter_email', 'G', 'TXT');
$filter_ip = cot_import('filter_ip', 'G', 'TXT');
$filter_country = cot_import('filter_country', 'G', 'TXT');
$filter_status = cot_import('filter_status', 'G', 'TXT');
$filter_date_from = cot_import_date('filter_date_from', 'G');
$filter_date_to = cot_import_date('filter_date_to', 'G');
/* === 3 == Пагинация === */
list($pg, $d, $durl) = cot_import_pagenav('d', Cot::$cfg['maxrowsperpage']);
$logs_per_page = (int) Cot::$cfg['maxrowsperpage'];
/* === 4. Инициализация шаблона === */
$t = new XTemplate(cot_tplfile('usrnewnotify.tools', 'plug'));
/* === 5. Формирование WHERE === */
$where = '1 = 1';
if (!empty($filter_username)) {
    $where .= " AND log_user_name LIKE '%" . Cot::$db->prep($filter_username) . "%'";
}
if (!empty($filter_email)) {
    $where .= " AND log_user_email LIKE '%" . Cot::$db->prep($filter_email) . "%'";
}
if (!empty($filter_ip)) {
    $where .= " AND log_ip LIKE '%" . Cot::$db->prep($filter_ip) . "%'";
}
if (!empty($filter_country)) {
    $where .= " AND log_country LIKE '%" . Cot::$db->prep($filter_country) . "%'";
}
if (!empty($filter_status) && in_array($filter_status, ['success', 'error'])) {
    $where .= " AND log_status = '" . Cot::$db->prep($filter_status) . "'";
}
if (!empty($filter_date_from)) {
    $where .= " AND log_date >= '" . $filter_date_from . " 00:00:00'";
}
if (!empty($filter_date_to)) {
    $where .= " AND log_date <= '" . $filter_date_to . " 23:59:59'";
}
/* === 6. Имя таблицы — ЧЕРЕЗ registerTable === */
$db_usrnewnotify_logs = Cot::$db->usrnewnotify_logs; // cot_...usrnewnotify_logs
/* === 7. Подсчёт общего количества === */
$totallines = (int) Cot::$db->query("SELECT COUNT(*) FROM $db_usrnewnotify_logs WHERE $where")->fetchColumn();
/* === 8. Выборка логов === */
$sql = Cot::$db->query("
    SELECT * FROM $db_usrnewnotify_logs
    WHERE $where
    ORDER BY log_date DESC
    LIMIT $d, $logs_per_page
");
/* === 9. Пагинация === */
$pagenav = cot_pagenav(
    'plug',
    [
        'r' => 'usrnewnotify',
        'filter_username' => $filter_username,
        'filter_email' => $filter_email,
        'filter_ip' => $filter_ip,
        'filter_country' => $filter_country,
        'filter_status' => $filter_status,
        'filter_date_from' => $filter_date_from,
        'filter_date_to' => $filter_date_to,
    ],
    $d,
    $totallines,
    $logs_per_page,
    'd',
    '',
    Cot::$cfg['jquery'] && Cot::$cfg['turnajax']
);
$i = 0;
foreach ($sql->fetchAll() as $log) {
    $i++;
    $t->assign([
        'LOG_ID' => (int)$log['log_id'],
        'LOG_USER_ID' => (int)$log['log_user_id'],
        'LOG_USER_NAME' => htmlspecialchars($log['log_user_name'] ?? '', ENT_QUOTES, 'UTF-8'),
        'LOG_USER_EMAIL' => htmlspecialchars($log['log_user_email'] ?? '', ENT_QUOTES, 'UTF-8'),
        'LOG_IP' => htmlspecialchars($log['log_ip'] ?? '', ENT_QUOTES, 'UTF-8'),
        'LOG_DEVICE' => htmlspecialchars($log['log_device'] ?: 'N/A', ENT_QUOTES, 'UTF-8'),
        'LOG_BROWSER' => htmlspecialchars($log['log_browser'] ?: 'N/A', ENT_QUOTES, 'UTF-8'),
        'LOG_COUNTRY' => htmlspecialchars($log['log_country'] ?: 'N/A', ENT_QUOTES, 'UTF-8'),
        'LOG_DATE' => cot_date('datetime_full', strtotime($log['log_date'])),
        'LOG_STATUS' => $log['log_status'] === 'success' ? $L['Success'] : $L['Error'],
        'LOG_MESSAGE' => htmlspecialchars($log['log_message'] ?? '', ENT_QUOTES, 'UTF-8'),
        'LOG_PROFILE_URL' => cot_url('users', ['m' => 'details', 'id' => $log['log_user_id']], '', true),
        'LOG_ODDEVEN' => cot_build_oddeven($i),
        'LOG_I' => $i,
    ]);
    $t->parse('MAIN.LOGS_ROW');
}
$sql->closeCursor();
/* === 10. Заполнение формы фильтров === */
$t->assign([
    'FILTER_ACTION_URL' => cot_url('admin', ['m' => 'other', 'p' => 'usrnewnotify'], '', true), // Исправлено: формируем URL для админки
    'FILTER_USERNAME' => htmlspecialchars($filter_username ?? '', ENT_QUOTES, 'UTF-8'),
    'FILTER_EMAIL' => htmlspecialchars($filter_email ?? '', ENT_QUOTES, 'UTF-8'),
    'FILTER_IP' => htmlspecialchars($filter_ip ?? '', ENT_QUOTES, 'UTF-8'),
    'FILTER_COUNTRY' => htmlspecialchars($filter_country ?? '', ENT_QUOTES, 'UTF-8'),
    'FILTER_STATUS' => $filter_status ?? '',
    'FILTER_DATE_FROM' => $filter_date_from ?? '',
    'FILTER_DATE_TO' => $filter_date_to ?? '',
]);
/* === 11. Пагинация === */
$t->assign([
    'PAGINATION' => $pagenav['main'],
    'PREVIOUS_PAGE' => $pagenav['prev'],
    'NEXT_PAGE' => $pagenav['next'],
    'TOTAL_ENTRIES' => $totallines,
    'ENTRIES_ON_CURRENT_PAGE' => $i,
]);

$t->assign(cot_generatePaginationTags($pagenav));


/* === 12. Нет логов === */
if ($i == 0) {
    $t->parse('MAIN.NO_LOGS');
}
/* === 13. Финализация === */
cot_display_messages($t);
$t->parse('MAIN');
$pluginBody = $t->text('MAIN');