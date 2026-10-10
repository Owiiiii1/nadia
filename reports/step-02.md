# Шаг 02. Laravel и кит

Шаг остановлен. Кит v0.5.0 не ставится на Laravel 12. Nginx, SSL, cron планировщика и smoke не делались. `--ignore-platform-reqs` не использовался. Токен Composer не читался и не менялся.

## Что сделано

- Удалены дубли `docs/index.html` и `docs/инструкция к админке.txt`. Оригиналы в `reference/` на месте.
- `composer create-project laravel/laravel /var/www/nadia-tmp "^12.0"` — скелет `laravel/laravel` v12.12.2, фреймворк v12.69.3.
- Перенос `rsync` в `/var/www/nadia` без `.git`, `docs/`, `reports/`, `reference/`, `deploy/`, нашего `README.md` и корневого `.gitignore`. Каталог `/var/www/nadia-tmp` удалён.
- Корневой `.gitignore` Laravel v12.12.2 совпал с нашим, оставлен наш.
- Исключение rsync `.gitignore` задело и вложенные файлы. Они восстановлены из тега `laravel/laravel` v12.12.2: `bootstrap/cache/.gitignore`, `database/.gitignore`, `storage/**/.gitignore`.
- Права: `deploy:www-data`, корень `2775`, ACL на `storage` и `bootstrap/cache` для `www-data` и `deploy`.
- `.env` на сервере (не в git): `APP_NAME="Nadia Glavin"`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://nadia.owlsolutions.net`, `APP_TIMEZONE=UTC`, `APP_LOCALE=en`, `APP_DISPLAY_TIMEZONE=America/Chicago`, MySQL `127.0.0.1` / база `nadia`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=sync`, `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`, `SESSION_SECURE_COOKIE=true`. `php artisan key:generate` выполнен. Пароль БД в отчёт не входит.
- В `config/app.php` чтение `APP_TIMEZONE` и ключ `display_timezone`. В `.env.example` добавлены `APP_TIMEZONE`, `APP_DISPLAY_TIMEZONE`, `SESSION_SECURE_COOKIE`.
- Репозиторий Composer для кита прописан. `composer require` откатил `composer.json` и `composer.lock` к состоянию до require.

## Изменённые файлы

Удалены:

- `docs/index.html`
- `docs/инструкция к админке.txt`

Изменены:

- `config/app.php`
- `.env.example`
- `docs/04-server.md`
- `docs/07-open-questions.md`
- `reports/step-02.md`

Плюс дерево Laravel 12 (без `vendor/`, `.env`, `node_modules/`, `public/build`).

## Выполненные команды

- `composer create-project laravel/laravel /var/www/nadia-tmp "^12.0"`
- `rsync` в `/var/www/nadia`, `sudo rm -rf /var/www/nadia-tmp`
- `sudo chown -R deploy:www-data /var/www/nadia`, `sudo chmod 2775 /var/www/nadia`
- ACL `setfacl` на `storage` и `bootstrap/cache`
- `php artisan key:generate`, `php artisan config:clear`
- `composer config repositories.custom-admin-kit ...`
- `composer require owlsolutions/custom-admin-kit:v0.5.0`

## Результаты проверок

`php artisan --version`: Laravel Framework 12.69.3.

`config('app.timezone')` = UTC, `config('app.display_timezone')` = America/Chicago, база `nadia`.

`git check-ignore` подтверждает: `.env`, `vendor`, `node_modules`, `public/build` в коммит не входят.

Smoke, `route:list`, curl, сертификат, логин — не выполнялись.

## Проблемы

Точный вывод Composer:

```text
Your requirements could not be resolved to an installable set of packages.

Problem 1
  - Root composer.json requires owlsolutions/custom-admin-kit v0.5.0 -> satisfiable by owlsolutions/custom-admin-kit[v0.5.0].
  - owlsolutions/custom-admin-kit v0.5.0 requires illuminate/console ^13.0 -> found illuminate/console[v13.0.0, ..., v13.35.0] but these were not loaded, likely because it conflicts with another require.

Installation failed, reverting ./composer.json and ./composer.lock to their original content.
```

Это не 401/404 и не отказ по версии PHP. В `composer.json` кита v0.5.0:

```text
php: ^8.3
illuminate/console: ^13.0
illuminate/filesystem: ^13.0
illuminate/support: ^13.0
nutgram/nutgram: ^4.48
```

PHP 8.5 подходит под `^8.3`. `illuminate/* ^13.0` — это Laravel 13. То же ограничение у v0.3.0, v0.3.1, v0.4.0 и `dev-main`.

При `create-project` миграция sqlite не прошла (`could not find driver`). Файл `database/database.sqlite` удалён, приложение смотрит в MySQL. Драйвер sqlite не ставился.

## Что нужно от тестера

Решить вопрос 13 в `docs/07-open-questions.md`: ставить Laravel 13 под кит v0.5.0 или оставить Laravel 12 и менять кит. До ответа nginx, certbot и `owl-admin:install` не запускались.

Карта модулей Customers/Orders/Services/Staff/Calendar для шага 3 пустая: эти файлы появляются только после установки кита.

## Повтор на Laravel 13

Решение техлида: чистый скелет Laravel 13, без апгрейда 12. Вопрос 13 закрыт.

### Что сделано

- `.env` сохранён в `/home/deploy/.nadia-env.bak` (600) и возвращён. На сервере файл `.env` имеет режим `640`, группа `www-data`, чтобы PHP-FPM мог его читать.
- Скелет 12 удалён. `composer create-project laravel/laravel /var/www/nadia-tmp "^13.0"` поставил `laravel/laravel` v13.11.0 и `laravel/framework` v13.35.0. rsync исключил только корневой `/.gitignore`, `.git`, `README.md`, `docs/`, `reports/`, `reference/`, `deploy/`. Вложенные `.gitignore` перенесены. `/var/www/nadia-tmp` и `database/database.sqlite` удалены.
- В `.gitignore` добавлены строки скелета 13, которых не было: `/.codex`, `/.cursor/`, `/public/fonts-manifest.dev.json`, `_ide_helper.php`. Строка `/.fleet` оставлена.
- `config/app.php`: `timezone` из `APP_TIMEZONE`, `display_timezone` из `APP_DISPLAY_TIMEZONE`. Те же ключи и `SESSION_SECURE_COOKIE` снова в `.env.example`.
- ACL и `2775` как в прошлый раз. `php artisan migrate --force` создал `users`, `cache`, `jobs`.
- Кит v0.5.0, Inertia, Ziggy, doctor, install `--preset=admin --backup --migrate --no-smoke`, frontend-setup `--preset=admin --backup --install-npm --run-build`, затем ещё `npm run build`.
- Админ `admin@nadia.owlsolutions.net`, пароль в `/home/deploy/.nadia-admin-credentials` (600). В отчёт и git не входит.
- nginx vhost и certbot. Cron `www-data` для `schedule:run`. `php artisan optimize` (config, events, routes, views) и reload `php8.5-fpm`.
- Бэкапы инсталлятора: `storage/app/owl-admin-kit/backups/2026-10-10-21-49-54/` (package.json, vite.config.js, css, middleware, routes/web.php). Каталог уже закрыт `storage/app/.gitignore`. В корневой `.gitignore` добавлен `/storage/app/owl-admin-kit/`.

### Версии

| Пакет | Версия |
| --- | --- |
| Laravel | 13.35.0 (`php artisan --version`) |
| Скелет | laravel/laravel v13.11.0 |
| owlsolutions/custom-admin-kit | v0.5.0 |
| nutgram/nutgram | 4.50.3 (ограничение кита `^4.48`, не пин 4.48.x) |
| inertiajs/inertia-laravel | v3.5.1 |
| tightenco/ziggy | v2.6.4 |
| React | `^18.3.1` (`react`, `react-dom`) |
| @inertiajs/react | `^2.3.29` |
| Vite | `^8.0.0`, `@vitejs/plugin-react` `^6.1.2`, `laravel-vite-plugin` `^3.1` |
| Node | v22.22.1 |

Фронт кита — React/JSX, не Vue. Отдельного пакета Vue в `package.json` нет.

### Smoke

`php artisan owl-admin:smoke --preset=admin` после `optimize`, код выхода 0. Все проверки с отметкой успеха, включая CRM-файлы, таблицы, пункты меню и Vite manifest. Отдельной строки `passed` команда не печатает.

Doctor до install: критических FAIL нет. Предупреждения — ещё не опубликованные файлы и отсутствие npm-пакетов React; их закрыл install и frontend-setup. PHP 8.5.4 doctor принял (`^8.3`).

### HTTP

| Проверка | Код |
| --- | --- |
| `http://nadia.owlsolutions.net/` | 301 → `https://nadia.owlsolutions.net/` |
| `/login` | 200 |
| `/owl-admin/health` | 200, JSON |
| `/customers` гостем | 302 → `/login` |
| `/settings` гостем | 302 → `/login` |
| `/settings?tab=telegram` гостем | 302 → `/login` |
| `POST /telegram/webhook` | 404 JSON `{"ok":false,"error":"Telegram bot not configured."}`, не 500 |
| `POST /login` созданным админом | 302 → `/dashboard` |
| `/dashboard` после входа | 200 |

`route:list` содержит login, logout, dashboard, customers/orders/services/staff CRUD, `orders.status`, `orders.assign`, calendar.index, settings, ai-settings, app-settings, profile, statistics.logs, owl-admin.health, telegram.webhook.

Сертификат: notBefore 2026-10-10, notAfter 2027-01-08. `certbot renew --dry-run` успешен. `certbot.timer` enabled и active.

`php artisan optimize` закэшировал и routes. Closure на `/` в Laravel 13 этому не помешал, отдельно отключать кэш роутов не пришлось.

### Что сделал инсталлятор

Опубликовано 71 файл пресета `admin`: роуты `routes/owl-admin-core.php`, `owl-admin-pages.php`, `owl-admin-auth.php`, контроллеры auth/settings/CRM, модели, миграции, JSX-страницы, `AdminLayout`, `resources/views/app.blade.php`. Frontend-setup дописал `resources/js/app.jsx`, подключил роуты в `routes/web.php`, поставил npm-зависимости и собрал `public/build` (в git не входит).

На `/` остаётся closure Laravel: `return view('welcome')`. Это не лендинг Nadia. Лендинг — шаг 4.

### Нестандартное

- При `create-project` sqlite-миграция снова предупредила `could not find driver`. Файл sqlite удалён.
- Nutgram встал 4.50.3, не 4.48.x. Версию кита не меняли.
- Inertia Laravel — v3, не ^2 из `suggest` пакета. Doctor и smoke это приняли.
- `route:cache` прошёл с closure на `/`.

### Карта модулей кита для удаления на шаге 3

Отдельных файлов переводов нет. Подписи меню на en/ru/uk лежат в `resources/js/Layouts/AdminLayout.jsx` (пункты `customers.index`, `orders.index`, `calendar.index`, `staff.index`, `services.index` и словари рядом). `resources/js/Pages/Dashboard.jsx` CRM не вызывает: это заглушка «Login is successful». Smoke (`vendor/owlsolutions/custom-admin-kit/src/Support/SmokeTester.php`) проверяет наличие моделей, страниц, миграций, таблиц и этих пунктов меню. После удаления шага 3 эти проверки smoke начнут падать, пока их не поправят в приложении или не отключат.

Связи моделей: `Customer` hasMany `Order`; `Service` hasMany `Order`; `Order` belongsTo `Customer` и `Service`, belongsToMany `Staff` через `order_staff`; `Staff` belongsToMany `Order`.

| Модуль | Файлы |
| --- | --- |
| Customers | `app/Http/Controllers/CustomersController.php`, `app/Models/Customer.php`, `database/migrations/2026_07_05_100000_create_customers_table.php`, `resources/js/Pages/Customers/Index.jsx`. Роуты в `routes/owl-admin-pages.php`: `customers.index/store/update/destroy` |
| Orders | `app/Http/Controllers/OrdersController.php`, `app/Models/Order.php`, `database/migrations/2026_07_05_100300_create_orders_table.php`, `database/migrations/2026_07_05_100400_create_order_staff_table.php`, `resources/js/Pages/Orders/Index.jsx`. Роуты: `orders.index/store/update/destroy/status/assign` |
| Services | `app/Http/Controllers/ServicesController.php`, `app/Models/Service.php`, `database/migrations/2026_07_05_100100_create_services_table.php`, `resources/js/Pages/Services/Index.jsx`. Роуты: `services.index/store/update/destroy` |
| Staff | `app/Http/Controllers/StaffController.php`, `app/Models/Staff.php`, `database/migrations/2026_07_05_100200_create_staff_table.php`, `resources/js/Pages/Staff/Index.jsx`. Роуты: `staff.index/store/update/destroy` |
| Calendar | `app/Http/Controllers/CalendarController.php`, `resources/js/Pages/Calendar/Index.jsx`. Своей модели и миграции нет, читает заказы. Роут: `calendar.index` |

Общее для всех пяти: меню и строки en/ru/uk в `resources/js/Layouts/AdminLayout.jsx`, регистрация роутов в `routes/owl-admin-pages.php`, проверки smoke на файлы, таблицы (кроме calendar) и имена роутов меню.
