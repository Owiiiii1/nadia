# 06. Деплой

Сервер один: `/var/www/nadia`, ветка `main`, пользователь `deploy`.

`.env` на сервере уже заполнен и в git не входит. `vendor/` и `public/build` на сервере собираются заново и в git не входят.

## Команда

Из `/var/www/nadia`:

```bash
deploy/scripts/deploy.sh
```

Скрипт пишет вывод в `storage/logs/deploy-YYYYMMDD-HHMMSS.log` и останавливается на первой ошибке (`set -euo pipefail`).

Что он делает:

1. Отказывается, если пользователь не `deploy`, ветка не `main` или рабочее дерево не чистое.
2. `git pull --ff-only`.
3. `composer install --no-dev --optimize-autoloader --no-interaction`.
4. `npm ci && npm run build`.
5. Если `php artisan migrate:status` показывает `Pending`, включает `php artisan down --retry=15`. Ошибка после `down` снимает режим обслуживания и выходит с кодом 1.
6. `php artisan migrate --force`, затем `php artisan up`, если обслуживание включалось.
7. `php artisan optimize:clear && php artisan optimize`.
8. `sudo systemctl reload php8.5-fpm`.
9. `php artisan nadia:smoke`.

`git pull` — только fast-forward. Force pull не делается.

`nadia:smoke` с кодом 0 — проверка каркаса. `owl-admin:smoke` кита ждёт модули CRM и не используется.

## Тесты после деплоя

`composer install --no-dev` не ставит PHPUnit. Чтобы прогнать тесты на сервере и вернуть прод к тем же пакетам:

```bash
cd /var/www/nadia
composer install --optimize-autoloader --no-interaction
./vendor/bin/phpunit
composer install --no-dev --optimize-autoloader --no-interaction
php artisan optimize
sudo systemctl reload php8.5-fpm
```

Тесты идут в базу `nadia_test`, не в `nadia`.

## Права

Каталог `deploy:www-data`, setgid `2775` на корне. После `git pull` новые файлы наследуют группу `www-data`. `storage` и `bootstrap/cache` доступны на запись пользователю PHP-FPM (`www-data`).

## Что не входит в деплой

- Правка конфигов nginx и sshd.
- `composer update`.
- Копирование `.env` из репозитория.
- Перезапуск MySQL.
- Настройка Telegram и SMTP.
