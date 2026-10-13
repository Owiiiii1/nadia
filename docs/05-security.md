# 05. Безопасность

Правила для шагов 4–8. На шаге 0 секретов в репозитории нет.

## Форма и чат

- CSRF Laravel на `POST /leads` и `POST /chat/message`. `POST /t` без CSRF, как webhook Telegram: маячок не может приложить токен сессии. Запись только при совпадении host `Origin`/`Referer` с host `APP_URL`, теле не больше 4 КБ и ответе 204.
- Honeypot на форме заявки: скрытое поле, которое человек не заполняет. Непустое значение — ответ как у обычной формы, заявка не создаётся.
- Throttle по IP: `POST /leads` — 5 в минуту, `POST /chat/message` — 20 в минуту, `POST /t` — 60 в минуту.
- `POST /login`: 5 неудачных попыток в минуту на пару email+IP (`LoginRequest`, decay 60 секунд). Шестая попытка — HTTP 429, не ошибка валидации. Публичных роутов `register`, `password.request`, `password.email`, `password.reset` нет. Смена пароля — только в Profile у уже вошедшего админа (`password.update`).
- Валидация полей до записи. Goal только из списка `sell`, `buy`, `invest`, `consult`. Неизвестный goal в блоке `<lead>` сохраняется как `consult`.
- Сообщение чата: не больше 1000 символов и не больше 30 сообщений пользователя на сессию. Дальше — 100 сообщений пользователя в сутки на один `ip_hash` и 1500 в сутки на весь сайт. Числа — `config/nadia.php`, глобальный потолок можно переопределить `NADIA_CHAT_DAILY_GLOBAL_LIMIT`.

## Ключи провайдеров

Ключи OpenAI, Anthropic и Gemini вводятся в настройках AI кита и лежат в `ai_provider_settings.api_key` с cast `encrypted`. Приложение читает активную строку (`is_active` и `is_connected`) в память на время запроса. Ключ не копируется в `site_settings`, логи, `chat_messages.error`, отчёты и git. У Gemini ключ уходит query-параметром `key`; если исключение содержит URL, перед записью ошибки вырезаются `key=`, `Bearer` и префикс `sk-`.

## IP

Сырой IP не пишется в `leads`, `chat_sessions`, `page_views`, `site_events` и не кладётся в access-лог приложения. В `leads` и `chat_sessions` лежит `ip_hash = sha256(ip + '|' + дата UTC + '|' + app.key)`, считает `App\Support\IpHasher`. В аналитике хэша нет. Строка не пишется, если UA пустой или подходит под `nadia.tracking.bot_pattern`, либо если админ залогинен. Свои визиты в цифры не входят. Отдельный файл соли не используется: дневная смена заложена в дату внутри хэша. Сырые строки старше 12 месяцев удаляет `analytics:prune`.

## Вывод

- Тексты заявок, заметок и сообщений чата в админке выводятся экранированными.
- Ответ ассистента на лендинге вставляется как текст, не как HTML.
- Блок `<lead>...</lead>` снимается на сервере до ответа клиенту.
- Сниппеты из `tracking_codes` — единственное место, где в HTML публичных страниц попадает код, заданный админом. Они выводятся на `/` и `/privacy` как есть и не выводятся в админке. Залогиненный админ их тоже не получает.
- Уведомление о заявке уходит после ответа клиенту. Текст в Telegram экранируется (`parse_mode=HTML`). Ошибка канала пишется без текста заявки: id лида и время.

## Пиксели только на лендинге

Meta Pixel, GA4 и сниппеты подключают `/` и `/privacy`. Layout Inertia-админки их не включает. В HTML админской страницы нет этих фрагментов. Залогиненный просмотр лендинга тоже их не получает.

## Секреты и git

- `.env`, `.env.backup`, `.env.production` в `.gitignore`.
- В отчёты не копировать пароль БД, `APP_KEY`, ключи ИИ и содержимое `.env`.
- `storage/*.key` в `.gitignore`.

## Бэкапы БД

Ежедневный дамп MySQL базы `nadia`: `/usr/local/bin/nadia-db-backup.sh` (копия без секретов — `deploy/scripts/nadia-db-backup.sh`), cron root в 03:30 UTC. Архивы лежат в `/var/backups/nadia`, хранятся 14 последних. Учётка читается из `/home/deploy/.nadia-db-credentials` (режим 600), этот файл не в git и не в отчётах. На шаге 8 дамп поднимался во временную базу и сверялся по заявкам и пользователю админки, затем база удалялась.

Каталог дампов — `root:root`, режим `700`, не под `public`. Как поднять дамп — [09-restore.md](09-restore.md).

## Вход и файлы

- `.env` на сервере: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `LOG_CHANNEL=daily`, `LOG_DAILY_DAYS=14`. Режим `640`, владелец `deploy:www-data`. Канал `daily` сам удаляет старые файлы `laravel-*.log`. Отдельный logrotate для них не нужен. `storage/logs/deploy-*.log` старше 30 дней удаляет `logs:prune` (04:10 UTC).
- Диск `local` в `config/filesystems.php`: `'serve' => false`. Роутов `storage.local` и `storage.local.upload` нет.
- Webhook Telegram: если бот настроен, запрос без верного `X-Telegram-Bot-Api-Secret-Token` получает 403 до обработки апдейта. Пока бот не настроен, эндпоинт отвечает 404.

## nginx

Заголовки на ответах, с `always`: `Strict-Transport-Security: max-age=31536000` (без `includeSubDomains`: домен `owlsolutions.net` общий), `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`. Тот же набор повторён в `location /build/`, потому что свой `add_header` там не наследует заголовки сервера.

`location ~ /\.(?!well-known).*` и `location ~* \.(env|log|sql|bak|sh)$` — `deny all`. PHP исполняется только `location = /index.php`. Остальные `*.php` — 404. `client_max_body_size 2m`. TLS 1.2 и 1.3 задаёт `/etc/letsencrypt/options-ssl-nginx.conf` (`ssl_protocols TLSv1.2 TLSv1.3`); файл принадлежит Certbot и вручную не правится. Заголовки стоят только на основном блоке `nadiaglavin.com`. `www` и `nadia.owlsolutions.net` отвечают `301` на `https://nadiaglavin.com$request_uri`. Копия vhost в репозитории: `deploy/nginx/nadiaglavin.com.conf`.

## CSP

Content-Security-Policy не включён. Это решение, а не пропуск: пиксели Meta/GA4 и произвольные сниппеты из админки вставляются в страницу как есть. Жёсткая политика их сломает. Включать CSP только после отдельного списка разрешённых источников.

## SSH

Боевой вход — ключ пользователя `deploy`. С шага 1 в `/etc/ssh/sshd_config.d/00-hardening.conf`: `PermitRootLogin no`, `PasswordAuthentication no`, `KbdInteractiveAuthentication no`, `PubkeyAuthentication yes`. Файл с префиксом `00-` перекрывает `50-cloud-init.conf`, потому что sshd берёт первое значение.
