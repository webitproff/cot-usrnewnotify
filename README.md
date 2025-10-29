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
  - При регистрации: «Здравствуйте, Administrator! пользователь зарыгался но еще не подтвердил свою рыгылку».
  - При подтверждении email: «Здравствуйте, Administrator! пользователь успешно зарыгался и подтвердил свою почту!!!».
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
