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
