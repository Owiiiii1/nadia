# Шаг 08. Безопасность, деплой, уборка, сдача

Релиз `v1.0.0`. Telegram и SMTP не настраивались и остаются выключенными. Пароль админа не менялся.

## 1. Аудит безопасности

### .env

Проверены ключи, не содержимое файла. `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`. Режим `640`, владелец `deploy:www-data`. `LOG_CHANNEL` уже был `daily`. `LOG_DAILY_DAYS` не было — записано `14` (дефолт канала и так 14). В `.env.example` стек логов тоже `daily` и `LOG_DAILY_DAYS=14`.

### Логин

Throttle кита уже был: 5 попыток на `email|IP`, decay 60 секунд, но отказ уходил как ошибка валидации. Теперь `LoginRequest` бросает HTTP 429. Curl на прод, чужой email `nobody@example.com`, с CSRF: попытки 1–5 — `302`, шестая — `429`. PHPUnit `LoginThrottleTest`: пять `422`, затем `429`.

### Регистрация и сброс пароля

`routes/owl-admin-auth.php` — только `GET/POST /login` и `POST /logout`. `route:list` не показывает `register`, `password.request`, `password.email`, `password.reset`. Остаётся `PUT password` (`password.update`) — смена пароля в Profile у вошедшего админа. Smoke проверяет отсутствие четырёх публичных имён.

### Админ

В `users` одна строка: `admin@nadia.owlsolutions.net`. `admin@admin.com` нет. Пароль не менялся.

### storage.local

У диска `local` было `'serve' => true`, из-за этого жили `GET/PUT storage/{path}`. Стоит `false`. После `php artisan optimize` `route:list --path=storage` пустой. Smoke: `no routes storage.local*`.

### Telegram webhook

В `TelegramWebhookController` секрет сравнивается через `hash_equals` до разбора апдейта. Пустой секрет или другой заголовок — 403. Бота на проде нет, поэтому живой `POST /telegram/webhook` без заголовка — `404` (токен не задан). Ветка 403 покрыта тестом `test_a_webhook_without_the_secret_is_forbidden_when_the_bot_is_configured`. Бот не включался.

### nginx

Обновлены `/etc/nginx/sites-available/nadia.owlsolutions.net` и `deploy/nginx/nadia.owlsolutions.net.conf`. `nginx -t` успешен, nginx перезагружен.

Заголовки с `always`, в том числе в `location /build/`: HSTS `max-age=31536000` без `includeSubDomains`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`.

Запреты: `location ~ /\.(?!well-known).*`, `location ~* \.(env|log|sql|bak|sh)$`. PHP только `location = /index.php`, прочие `*.php` — 404. `client_max_body_size 2m`. TLS: `/etc/letsencrypt/options-ssl-nginx.conf` содержит `ssl_protocols TLSv1.2 TLSv1.3`. Файл Certbot не редактировался.

CSP не добавлялся. Решение записано в docs/05: пиксели и сниппеты вставляются как есть.

`curl -sI` на `/` и `/login` показывает все пять заголовков.

| Путь | Код |
| --- | --- |
| `/.env` | 403 |
| `/.git/config` | 403 |
| `/composer.json` | 404 |
| `/storage/logs/laravel.log` | 403 |
| `/index.php.bak` | 403 |
| `/vendor/autoload.php` | 404 |
| `/foo.php` | 404 |

### Аудит пакетов

`composer audit`: `No security vulnerability advisories found.`

`npm audit --omit=dev`: `found 0 vulnerabilities.`

High/critical нет, пакеты не обновлялись.

### Права

`find /var/www/nadia -not -path '*/node_modules/*' -not -path '*/vendor/*' -perm -o+w` — пусто.

## 2. Деплой-скрипт

`deploy/scripts/deploy.sh`: `set -euo pipefail`, только пользователь `deploy`, ветка `main`, чистое дерево, `git pull --ff-only`, `composer install --no-dev`, `npm ci && npm run build`, `down` только при Pending-миграциях, `migrate --force`, `up` в trap после ошибки, `optimize`, reload `php8.5-fpm`, `nadia:smoke`. Лог: `storage/logs/deploy-YYYYMMDD-HHMMSS.log`.

docs/06 заменён на эту команду. Там же процедура тестов: `composer install` (с dev) → `phpunit` → снова `--no-dev`.

Лог прогона — в конце отчёта.

## 3. Уборка

Свежий бэкап до удалений: `/usr/local/bin/nadia-db-backup.sh`. Архив `/var/backups/nadia/nadia-20261011.sql.gz`, 10845 байт, `gzip -t` успешен, 18 `CREATE TABLE`. Ночной cron в 03:30 UTC уже создавал этот файл (тогда 7044 байт); свежий прогон перезаписал его данными до очистки.

Перед удалением:

| id | name |
| --- | --- |
| 3 | Step Four |
| 4 | Browser Check |

Других заявок не было. `chat_sessions` 0, `chat_messages` 0, `site_events` 0, `tracking_codes` 0. Единственный `page_views`: id 1, путь `/step-06-check`. Ключей `notify_last_error*` в `site_settings` не было. `assistant_greeting`, `assistant_system_prompt`, `chat_enabled=1` оставлены.

Удалены все строки `leads`, `chat_sessions`, `chat_messages`, `page_views`, `site_events`. `AUTO_INCREMENT=1` у `leads`, `chat_sessions`, `page_views`, `site_events`. `php artisan cache:clear`.

Playwright. Сначала `apt-get purge -s`: 31 пакет из строки Install в reports/step-05.md, без nginx, php, mysql, certbot, git, supervisor. До purge сервисы `nginx`, `php8.5-fpm`, `mysql`, `fail2ban` — active, `nadia:smoke` код 0. Purge прошёл, освобождено 107 МБ. `apt-get autoremove --purge -s` — 0 пакетов, команда подтверждена. После: те же четыре сервиса active, smoke код 0.

Удалён `/home/deploy/.nadia-env.bak`. Скриншоты `reports/step-04/*.png` на месте.

`git grep` по `password=`, `api_key`, `github_pat`, `sk-`, `AKIA`: живых секретов нет. Совпадения — имя колонки `api_key`, маска `sk-` в редакторе ошибок, фиктивные `sk-test-*` в тестах, `password=${DB_PASSWORD}` в скрипте бэкапа и примеры `--password=` в `reference/admin-kit-install.md`. `github_pat` и `AKIA` не найдены.

## 4. Эксплуатация

`LOG_DAILY_DAYS=14` закрывает `laravel-*.log`. `logs:prune` удаляет `storage/logs/deploy-*.log` старше 30 дней, расписание 04:10 UTC рядом с `analytics:prune` в 04:00 UTC. Smoke проверяет обе команды.

Cron бэкапа: `/etc/cron.d/nadia-db-backup`, `30 3 * * *`. Ночной файл от 2026-10-11 03:30 UTC был на месте. Дамп до очистки восстановлен во временную базу `nadia_restore_check`: `users=1`, `leads=2`, админ на месте. База после сверки удалена. Процедура — docs/09-restore.md.

`GET /up` — 200. Для внешнего мониторинга (UptimeRobot и аналоги) подходит `https://nadia.owlsolutions.net/up`.

## 5. Документы

- `docs/08-handover.md` — сдача на русском.
- `README.md` — ссылки на handover, деплой, docs/08 и docs/09.
- `docs/07-open-questions.md` — страница privacy есть; открыты вопрос 4 (фото, тексты, дисклеймеры, контакт на `/privacy`) и вопрос 5 (финальный system prompt).
- `docs/03-roadmap.md` — шаг 8 выполнен.
- `docs/05-security.md` — 429 на логине, nginx, CSP, диск `local`, логи.

## 6. Финальная проверка

Гость:

| Запрос | Результат |
| --- | --- |
| `GET /` | 200 |
| `GET /privacy` | 200 |
| `GET /login` | 200 |
| `GET /up` | 200 |
| `GET /dashboard` | 302 на `/login` |
| `http://` → | 301 на https |
| `POST /chat/message` с CSRF | 503 `{"error":"unavailable"}` |
| `POST /leads` без CSRF | 419 |
| `POST /telegram/webhook` | 404 |

Заявка JSON с CSRF, имя `Handover Check`, ответ 201. Затем удалена (id 1) вместе со всеми `page_views` и `site_events`. Итог: `leads=0`, `chat_sessions=0`, `page_views=0`, `site_events=0`, auto-increment у этих таблиц снова 1.

Время ответа, 5 замеров, среднее: `/` 0.090 с, `/login` 0.082 с.

Сертификат `CN=nadia.owlsolutions.net`, notBefore `Oct 10 20:53:08 2026 GMT`, notAfter `Jan 8 20:53:07 2027 GMT`. `certbot renew --dry-run` уходит в случайную паузу (~405 с); проверка выполнена как `certbot renew --dry-run --no-random-sleep-on-renew`: `Congratulations, all simulated renewals succeeded`.

Сервисы: nginx, php8.5-fpm, mysql, fail2ban — active.

`df -h /`: 38G, занято 6.8G, свободно 29G, 20%. `free -h`: RAM 3.7 Gi, available 1.0 Gi, swap 2.0 Gi, занято 196 Mi.

PHPUnit до деплоя: 39 тестов, 292 assertions, passed. После `deploy.sh` — та же процедура из docs/06: `composer install` (с dev), `./vendor/bin/phpunit` (39 тестов, 292 assertions, passed), затем `composer install --no-dev`, `php artisan optimize`, reload `php8.5-fpm`. На проде снова пакеты без dev.

## Прогон deploy.sh

Команда `bash deploy/scripts/deploy.sh` на чистом `main`, код 0. Полный лог: `storage/logs/deploy-20261011-124058.log`.

```
deploy start 2026-10-11T12:40:58Z
log storage/logs/deploy-20261011-124058.log
Already up to date.
Installing dependencies from lock file
Package operations: 0 installs, 0 updates, 33 removals
Generating optimized autoload files
added 270 packages, and audited 271 packages in 16s
found 0 vulnerabilities
vite v8.3.4 building client environment for production...
✓ built in 3.43s
INFO  Nothing to migrate.
INFO  Clearing cached bootstrap files.
INFO  Caching framework bootstrap, configuration, and metadata.
nadia:smoke — все проверки с ✓, код 0
· chat available: no (none)
· notifications: telegram off (0 recipients), email off (0 recipients), mail transport: log
deploy finished 2026-10-11T12:41:32Z
```

Pending-миграций не было, `down` не включался.
