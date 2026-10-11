# 08. Сдача

Сайт Nadia Glavin, `https://nadia.owlsolutions.net`, релиз `v1.0.0`.

## Что это

Одно приложение Laravel 13.

- Лендинг на `/`: секции, форма заявки, виджет чата (кнопка видна только когда ИИ включён).
- Админка кита: Dashboard, Leads, Chats, Analytics, Logs, Pixels & codes, Settings (AI, Assistant, Notifications).
- Страница `/privacy`.
- Уведомления о заявке в Telegram и на почту — выключены, пока их не подключат.

## Доступы и где лежат

Значения в этот файл не копируются.

| Что | Где |
| --- | --- |
| Сайт и админка | `https://nadia.owlsolutions.net`, вход `/login` |
| Email и пароль админа | `/home/deploy/.nadia-admin-credentials` (режим 600) |
| Креды БД | `/home/deploy/.nadia-db-credentials` (режим 600) |
| Токен Composer (приватный кит) | `/home/deploy/.config/composer/auth.json` |
| `.env` | `/var/www/nadia/.env`, режим 640, `deploy:www-data` |

## Первые действия владельца

1. Войти и в Profile сменить email и пароль админа.
2. Подключить ИИ: Settings → AI. Вставить ключ, Check, выбрать модель, Activate. Для старта хватает недорогой модели (`gpt-4o-mini`, `gemini-2.5-flash` или аналог Haiku).
3. В Assistant отредактировать system prompt и удалить строку `[DRAFT — replace with client-approved prompt]`. Пока строка на месте, в модель она не отправляется, но это всё ещё черновик.
4. Telegram, если нужен: BotFather → токен в настройках бота → Set webhook. Затем Settings → Notifications → включить Telegram → Connect → в боте отправить `/start` и код. Пока это не сделано, канал выключен.
5. Почта, если нужна: в `.env` заполнить `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. Затем `php artisan optimize` и `sudo systemctl reload php8.5-fpm`. В Notifications включить email и добавить адрес. Сейчас `MAIL_MAILER=log`, письма никуда не уходят.
6. Вставить Meta Pixel ID и GA4 Measurement ID в Pixels & codes. Свои сниппеты — туда же.

## Контент до публичного запуска

Ещё не закрыто заказчиком ([07-open-questions.md](07-open-questions.md)):

- реальные фото вместо плейсхолдеров макета;
- дисклеймеры брокера в футере;
- контакт вместо `[contact email]` на `/privacy`;
- финальный system prompt.

## Деплой, тесты, бэкапы

- Обновление: [06-deploy.md](06-deploy.md), одна команда `deploy/scripts/deploy.sh`.
- Восстановление базы и сервера с нуля: [09-restore.md](09-restore.md).
- Дамп каждый день в 03:30 UTC, каталог `/var/backups/nadia`, 14 архивов. Копия только на этом сервере — стоит забирать архив наружу.
- Внешний мониторинг можно повесить на `https://nadia.owlsolutions.net/up` (ответ 200, без авторизации).

## Лимиты чата

Файл `config/nadia.php`.

| Параметр | Сейчас |
| --- | --- |
| `per_minute` | 20 запросов с IP |
| `max_message_length` | 1000 символов |
| `session_user_messages` | 30 на сессию |
| `daily_ip_limit` | 100 сообщений пользователя в сутки на `ip_hash` |
| `daily_global_limit` | 1500 в сутки на весь сайт, переменная `NADIA_CHAT_DAILY_GLOBAL_LIMIT` |
| `context_messages` | 20 |
| `max_output_tokens` | 500 |

После правки `config/nadia.php` или `.env` нужен `php artisan optimize`.

## Известные ограничения

- Приёмка — `php artisan nadia:smoke`. `owl-admin:smoke` не используется.
- Бэкапы лежат только на этом сервере.
- Content-Security-Policy не включён: пиксели и сниппеты вставляются как есть.
- Telegram и SMTP не настроены и остаются выключенными.
