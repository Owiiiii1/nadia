# Шаг 9. Домен nadiaglavin.com

2026-10-13. DNS до правок: `dig +short nadiaglavin.com`, `www.nadiaglavin.com` и `nadia.owlsolutions.net` — все `2.29.102.216`.

Боевой адрес — `https://nadiaglavin.com`. `APP_URL` в `.env` тот же. `SESSION_DOMAIN` не задан, cookie сессии host-only.

## Сертификат

`certbot --nginx -d nadiaglavin.com -d www.nadiaglavin.com` сертификат получил и сохранил, но в nginx сам не вставил: в текущем vhost не было `server_name` нового домена. Файлы:

- `/etc/letsencrypt/live/nadiaglavin.com/fullchain.pem`
- subject `CN=nadiaglavin.com`
- notBefore `Oct 13 19:07:41 2026 GMT`
- notAfter `Jan 11 19:07:40 2027 GMT`

Сертификат `nadia.owlsolutions.net` оставлен: `CN=nadia.owlsolutions.net`, notBefore `Oct 10 20:53:08 2026 GMT`, notAfter `Jan 8 20:53:07 2027 GMT`. Он нужен для HTTPS-редиректа со старого имени.

`certbot renew --dry-run --no-random-sleep-on-renew`: оба сертификата, `Congratulations, all simulated renewals succeeded`. Без `--no-random-sleep-on-renew` certbot уходит в случайную паузу, как на шаге 8.

## nginx

Живой файл `/etc/nginx/sites-available/nadiaglavin.com`, в `sites-enabled` только он. Старый `sites-available/nadia.owlsolutions.net` удалён, чтобы не осталось второго vhost. Копия в репозитории: `deploy/nginx/nadiaglavin.com.conf` (прежний `deploy/nginx/nadia.owlsolutions.net.conf` убран).

- `server_name nadiaglavin.com` — приложение, заголовки шага 8 (`HSTS` без `includeSubDomains`, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`), тот же набор повторён в `location /build/`.
- `www.nadiaglavin.com` на 443 — `return 301 https://nadiaglavin.com$request_uri`, сертификат нового домена.
- `nadia.owlsolutions.net` на 443 — тот же 301, старый сертификат.
- порт 80 для всех трёх имён — 301 на `https://nadiaglavin.com$request_uri`.

`nginx -t` успешен, nginx перезагружен.

## Приложение

`php artisan optimize:clear && php artisan optimize`, `systemctl reload php8.5-fpm`.

Canonical и `og:url` на лендинге берутся из `config('app.url')`, не из строки домена. `TrackController::allowedOrigin()` уже сравнивает host `Origin`/`Referer` с host `APP_URL`. В `nadia:smoke` проверка canonical завязана на `APP_URL`. Тест `test_origin_is_taken_from_the_app_url_host` фиксирует, что `https://nadiaglavin.com` пишется, а `https://nadia.owlsolutions.net` — нет.

Email админа не менялся.

В `telegram_bot_settings` строк нет. Вебхук не переустанавливался.

Фикстура аналитики: у `Lead` `created_at` не в `$fillable`, и после 11 октября заявка «сегодня» выпадала из окна 10–11 октября. В тесте дата выставляется после `create`.

## Проверки

| Запрос | Ответ |
| --- | --- |
| `http://nadiaglavin.com/` | 301 `https://nadiaglavin.com/` |
| `https://www.nadiaglavin.com/?a=1` | 301 `https://nadiaglavin.com/?a=1` |
| `https://nadia.owlsolutions.net/login` | 301 `https://nadiaglavin.com/login` |
| `http://www.nadiaglavin.com/privacy?x=1` | 301 `https://nadiaglavin.com/privacy?x=1` |
| `http://nadia.owlsolutions.net/login` | 301 `https://nadiaglavin.com/login` |
| `https://nadiaglavin.com/` | 200 |

В HTML главной: `canonical` и `og:url` равны `https://nadiaglavin.com/`.

`GET /login` — 200. Вход с учёткой из `/home/deploy/.nadia-admin-credentials` — 302 на `https://nadiaglavin.com/dashboard`. `Set-Cookie` без `Domain` (host-only), `Secure`. `GET /dashboard` с этой cookie — 200.

`POST /t` с `Origin: https://nadiaglavin.com` и обычным Chrome UA — 204, одна строка `page_views` с путём `/step-09-check`. Строка удалена. Остальные просмотры, которые уже были в базе, не трогались.

`nadia:smoke` — 0. PHPUnit — 40 тестов, 298 assertions, все зелёные. После прогона снова `composer install --no-dev`, `php artisan optimize`, reload php-fpm, повторный smoke зелёный.
