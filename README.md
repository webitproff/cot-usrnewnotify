# User New Notify Plugin for Cotonti Siena
![License](https://img.shields.io/badge/license-BSD-blue.svg)
[![Version](https://img.shields.io/badge/version-2.2.8-green.svg)](https://github.com/webitproff/usrnewnotify/releases)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti_Siena-0.9.26-orange.svg)](https://www.cotonti.com/)
[![PHP](https://img.shields.io/badge/PHP-8.4-blueviolet.svg)](https://www.php.net/releases/8_4_0.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-blue.svg)](https://www.mysql.com/)

**User New Notify** is a plugin for Cotonti Siena CMF that sends email notifications to administrators about new user registrations and provides an admin panel interface for viewing registration logs with filtering and search capabilities. The plugin supports both text and HTML notification formats, database logging, and displays detailed user information, including device, browser, and country.

## Key Features
- Sends email notifications to administrator(s) upon new user registration and email confirmation.
- Supports text and HTML notification formats with modern design (tables, styles).
- Different email messages:
  - On registration: "Hello, Administrator! A user has registered but has not yet confirmed their email."
  - On email confirmation: "Hello, Administrator! A user has successfully registered and confirmed their email!!!"
- Notifications include: username, email, registration date, IP address, country, device, browser, and a clickable link to the user’s profile.
- Logs registration and email confirmation events in the database (`cot_usrnewnotify_logs`) with separate entries for each event.
- Admin panel interface for viewing logs with filtering by username, email, IP, country, status, and date.
- Supports multiple email addresses for notifications (comma-separated).
- Configurable settings via the Cotonti admin panel.
- Multilingual support (English, Russian, Ukrainian).
- Secure data handling with escaping and validation.

## Requirements
- **CMF Cotonti Siena**: Version 0.9.26 or higher.
- **PHP**: 8.4 or higher.
- **MySQL**: 8.0 or higher.
- **Permissions**:
  - Guests: Read (R).
  - Users: Read/Write (RW).
  - Lock: 12345A for guests and members.
- **External API**: Uses [ip-api.com](http://ip-api.com) for country detection by IP (free, up to 45 requests/min).

## Installation
1. Download the latest version of the plugin from the [GitHub repository](https://github.com/webitproff/cot-usrnewnotify).
2. Extract the archive to the `plugins/` folder of your Cotonti site.
3. Go to the admin panel: **Administration → Extensions → Install Plugin**.
4. Find `User New Notify` and click **Install**.
5. The plugin will create the `cot_usrnewnotify_logs` table in the database.

## Configuration
Configure the plugin in the admin panel: **Administration → Extensions → User New Notify → Settings**.

### Available Settings:
- **Enable Notifications (`notify_enabled`)**: Enable/disable email notifications (0 — disabled, 1 — enabled).
- **Notification Email Addresses (`notify_email`)**: Specify administrator email addresses, comma-separated (e.g., `admin1@example.com,admin2@example.com`).
- **Notification Format (`notify_format`)**: Choose `text` (plain text) or `html` (formatted HTML).
- **Database Logging (`notify_log`)**: Enable/disable logging to the database (0 — disabled, 1 — enabled).

## Usage
1. **Notifications**:
   - On user registration (via `users.register.add.done`):
     - Sends an email with the message: "Hello, Administrator! A user has registered but has not yet confirmed their email" (if `regnoactivation = 0`).
     - Logs the event in `cot_usrnewnotify_logs` with `log_status='error'` and `log_message='Pending email validation'`.
   - On email confirmation (via `users.register.validate.done`):
     - Sends an email with the message: "Hello, Administrator! A user has successfully registered and confirmed their email!"
     - Logs a new entry with `log_status='success'` and `log_message='Notification sent successfully'`.
   - Notifications include: username, email, registration date, IP address, country, device, browser, and a clickable profile link (e.g., `https://example.com/en/users/1687?m=details`).
2. **Log Viewing**:
   - In the admin panel: **Administration → Extensions → User New Notify**.
   - The log table includes:
     - Log ID.
     - Username.
     - Email.
     - IP address.
     - Country.
     - Device (Mobile, Tablet, Desktop).
     - Browser (Chrome, Firefox, Safari, etc.).
     - Registration date.
     - Notification status (success/error).
     - Message (e.g., "Pending email validation" or "Notification sent successfully").
     - Profile link.
   - Supports filtering by username, email, IP, country, status, and date range, with pagination.

## Plugin Files
| File | Description |
|------|-------------|
| `usrnewnotify.setup.php` | Plugin metadata and configuration. |
| `usrnewnotify.users.register.add.done.php` | Logic for sending notifications and logging on registration (triggers after `cot_add_user` in `users.register.php`). |
| `usrnewnotify.users.register.validate.done.php` | Logic for sending notifications and logging on email confirmation (triggers after confirmation link click in `users.register.php`). |
| `usrnewnotify.tools.php` | Admin panel interface for viewing and filtering logs. |
| `usrnewnotify.tools.tpl` | Admin panel interface template. |
| `inc/usrnewnotify.functions.php` | Functions for detecting device, browser, country, and logging. |
| `lang/usrnewnotify.en.lang.php` | English localization with email templates. |
| `lang/usrnewnotify.ru.lang.php` | Russian localization. |
| `lang/usrnewnotify.ua.lang.php` | Ukrainian localization. |
| `usrnewnotify.install.sql` | SQL script to create the `cot_usrnewnotify_logs` table. |
| `usrnewnotify.uninstall.sql` | SQL script to drop the table on uninstallation. |

## Database Structure
Table `cot_usrnewnotify_logs`:
- `log_id` (INT, PRIMARY KEY): Unique log entry ID.
- `log_user_id` (INT): User ID.
- `log_user_name` (VARCHAR): Username.
- `log_user_email` (VARCHAR): User email.
- `log_ip` (VARCHAR): IP address (IPv4/IPv6).
- `log_user_agent` (TEXT): User-Agent string.
- `log_device` (VARCHAR): Device (Mobile, Tablet, Desktop).
- `log_browser` (VARCHAR): Browser.
- `log_country` (VARCHAR): Country.
- `log_date` (DATETIME): Event date.
- `log_status` (ENUM): Status (`success`/`error`).
- `log_message` (TEXT): Message (e.g., "Pending email validation" or "Notification sent successfully").

## Security
- Data escaping using `htmlspecialchars` to prevent XSS.
- IP address validation using `filter_var`.
- Email address validation before sending.
- SQL queries with prepared statements to prevent injections.
- Safe use of the ip-api.com API with error handling.

## License
The plugin is distributed under the BSD License.

## Author
- **webitproff**
- GitHub: [https://github.com/webitproff](https://github.com/webitproff)
- Copyright © 2025 webitproff

## Known Issues
- HTML notifications require email client support for HTML.
- Device, browser, and country detection rely on User-Agent and ip-api.com, which may be inaccurate.
- ip-api.com API limitation: 45 requests/min (free version).

## Support
For questions or issues, create an Issue on [GitHub](https://github.com/webitproff/cot-usrnewnotify/issues).
Discuss the plugin on the [forum](https://abuyfile.com/en/forums/cotonti/custom/plugs).
**Propose a task or job to the developer [here](https://abuyfile.com/users/webitproff)**

10/29/2025
___

# Плагин User New Notify для Cotonti Siena

![License](https://img.shields.io/badge/license-BSD-blue.svg)
[![Version](https://img.shields.io/badge/version-2.2.8-green.svg)](https://github.com/webitproff/usrnewnotify/releases)
[![Cotonti Compatibility](https://img.shields.io/badge/Cotonti_Siena-0.9.26-orange.svg)](https://www.cotonti.com/)
[![PHP](https://img.shields.io/badge/PHP-8.4-blueviolet.svg)](https://www.php.net/releases/8_4_0.php)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-blue.svg)](https://www.mysql.com/)

**User New Notify** — плагин для CMF Cotonti Siena, который отправляет администратору уведомления о регистрации новых пользователей по электронной почте и предоставляет интерфейс в админ-панели для просмотра логов регистраций с фильтрацией и поиском. Плагин поддерживает текстовый и HTML-формат уведомлений, логирование в базу данных и отображение подробной информации о пользователе, включая устройство, браузер и страну.


## Основные возможности

- Отправка уведомлений администратору(ам) по email при регистрации нового пользователя и при подтверждении email.
- Поддержка текстового и HTML-формата уведомлений с современным дизайном (таблицы, стили).
- Разные сообщения в письмах:
  - При регистрации: «Здравствуйте, Administrator! пользователь зарегистрировался, но еще не подтвердил свою почту».
  - При подтверждении email: «Здравствуйте, Administrator! пользователь успешно зарегистрировался и подтвердил свою почту!!!».
- Уведомления содержат: имя пользователя, email, дату регистрации, IP-адрес, страну, устройство, браузер и кликабельную ссылку на профиль пользователя.
- Логирование регистраций и подтверждений email в базу данных (`cot_usrnewnotify_logs`) с отдельными записями для каждого события.
- Интерфейс админ-панели для просмотра логов с фильтрацией по имени, email, IP, стране, статусу и дате.
- Поддержка нескольких email-адресов для уведомлений (через запятую).
- Настраиваемые параметры через админ-панель Cotonti.
- Многоязычная поддержка (русский, английский, украинский).
- Безопасная обработка данных с экранированием и валидацией.


## Требования

- **CMF Cotonti Siena**: версия 0.9.26 или выше.
- **PHP**: 8.4 или выше.
- **MySQL**: 8.0 или выше.
- **Права доступа**:
  - Гости: чтение (R).
  - Пользователи: чтение/запись (RW).
  - Блокировка: 12345A для гостей и членов.
- **Внешний API**: Для определения страны по IP используется [ip-api.com](http://ip-api.com) (бесплатно, до 45 запросов/мин).


## Установка

1. Скачайте последнюю версию плагина из [репозитория GitHub](https://github.com/webitproff/cot-usrnewnotify).
2. Распакуйте архив в папку `plugins/` вашего сайта на Cotonti.
3. Перейдите в админ-панель: **Администрирование → Расширения → Установить плагин**.
4. Найдите `User New Notify` и нажмите **Установить**.
5. Плагин создаст таблицу `cot_usrnewnotify_logs` в базе данных.


## Настройка

Настройте плагин в админ-панели: **Администрирование → Расширения → User New Notify → Настройки**.

### Доступные параметры:

- **Включить уведомления (`notify_enabled`)**: Включение/отключение отправки email-уведомлений (0 — отключено, 1 — включено).
- **Email-адреса для уведомлений (`notify_email`)**: Укажите email-адреса администраторов через запятую (например, `admin1@example.com,admin2@example.com`).
- **Формат уведомлений (`notify_format`)**: Выберите `text` (текстовый) или `html` (форматированный HTML).
- **Логирование в базу данных (`notify_log`)**: Включение/отключение записи логов в базу данных (0 — отключено, 1 — включено).


## Использование

1. **Уведомления**:
   - При регистрации пользователя (через `users.register.add.done`):
     - Отправляется письмо с текстом: «Здравствуйте, Administrator! пользователь зарегистрировался, но еще не подтвердил регистрацию через свою почту» (если `regnoactivation = 0`).
     - Логируется событие в `cot_usrnewnotify_logs` с `log_status='error'` и `log_message='Pending email validation'`.
   - При подтверждении email (через `users.register.validate.done`):
     - Отправляется письмо с текстом: «Здравствуйте, Administrator! пользователь успешно зарегистрировался и подтвердил свою почту!».
     - Логируется новая запись с `log_status='success'` и `log_message='Notification sent successfully'`.
   - Уведомления содержат: имя пользователя, email, дату регистрации, IP-адрес, страну, устройство, браузер и кликабельную ссылку на профиль (например, `https://example.com/ru/users/1687?m=details`).


2. **Просмотр логов**:

   - В админ-панели: **Администрирование → Расширения → User New Notify**.
   - Таблица логов включает:
     - ID записи.
     - Имя пользователя.
     - Email.
     - IP-адрес.
     - Страна.
     - Устройство (Mobile, Tablet, Desktop).
     - Браузер (Chrome, Firefox, Safari и т.д.).
     - Дата регистрации.
     - Статус отправки (success/error).
     - Сообщение (например, «Pending email validation» или «Notification sent successfully»).
     - Ссылка на профиль.
   - Поддерживается фильтрация по имени, email, IP, стране, статусу и диапазону дат, а также пагинация.


## Файлы плагина

| Файл | Описание |
|------|----------|
| `usrnewnotify.setup.php` | Метаданные и конфигурация плагина. |
| `usrnewnotify.users.register.add.done.php` | Логика отправки уведомления и логирования при регистрации (срабатывает после `cot_add_user` в `users.register.php`). |
| `usrnewnotify.users.register.validate.done.php` | Логика отправки уведомления и логирования при подтверждении email (срабатывает после клика по ссылке подтверждения в `users.register.php`). |
| `usrnewnotify.tools.php` | Интерфейс админ-панели для просмотра и фильтрации логов. |
| `usrnewnotify.tools.tpl` | Шаблон интерфейса админ-панели. |
| `inc/usrnewnotify.functions.php` | Функции для определения устройства, браузера, страны и логирования. |
| `lang/usrnewnotify.ru.lang.php` | Русская локализация с шаблонами писем. |
| `lang/usrnewnotify.en.lang.php` | Английская локализация. |
| `lang/usrnewnotify.ua.lang.php` | Украинская локализация. |
| `usrnewnotify.install.sql` | SQL-скрипт для создания таблицы `cot_usrnewnotify_logs`. |
| `usrnewnotify.uninstall.sql` | SQL-скрипт для удаления таблицы при деинсталляции. |


## Структура базы данных

Таблица `cot_usrnewnotify_logs`:
- `log_id` (INT, PRIMARY KEY): Уникальный идентификатор записи.
- `log_user_id` (INT): ID пользователя.
- `log_user_name` (VARCHAR): Имя пользователя.
- `log_user_email` (VARCHAR): Email пользователя.
- `log_ip` (VARCHAR): IP-адрес (IPv4/IPv6).
- `log_user_agent` (TEXT): User-Agent.
- `log_device` (VARCHAR): Устройство (Mobile, Tablet, Desktop).
- `log_browser` (VARCHAR): Браузер.
- `log_country` (VARCHAR): Страна.
- `log_date` (DATETIME): Дата события.
- `log_status` (ENUM): Статус (`success`/`error`).
- `log_message` (TEXT): Сообщение (например, «Pending email validation» или «Notification sent successfully»).


## Безопасность

- Экранирование данных через `htmlspecialchars` для защиты от XSS.
- Валидация IP-адресов через `filter_var`.
- Проверка email-адресов перед отправкой.
- SQL-запросы с подготовленными выражениями для защиты от инъекций.
- Безопасное использование API ip-api.com с обработкой ошибок.


## Лицензия

Плагин распространяется под лицензией BSD


## Автор

- **webitproff**
- GitHub: [https://github.com/webitproff](https://github.com/webitproff)
- Copyright © 2025 webitproff


## Известные проблемы

- HTML-уведомления требуют поддержки HTML в почтовом клиенте.
- Определение устройства, браузера и страны зависит от User-Agent и API ip-api.com, что может быть неточным.
- Ограничение API ip-api.com: 45 запросов/мин (бесплатная версия).


## Поддержка

Вопросы и проблемы — создавайте Issue на [GitHub](https://github.com/webitproff/cot-usrnewnotify/issues).
Обсуждение плагина на [форуме](https://abuyfile.com/ru/forums/cotonti/custom/plugs)

**Предложить разработчику [задание или работу](https://abuyfile.com/users/webitproff)**

10/29/2025
