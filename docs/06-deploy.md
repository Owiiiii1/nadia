# 06. Деплой

Сервер один: `/var/www/nadia`, ветка `main`. Деплой вручную по SSH под `deploy`.

`.env` на сервере уже заполнен и в git не входит. `vendor/` и `public/build` на сервере собираются заново.

## Команды

Из `/var/www/nadia`:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan optimize
sudo systemctl reload php8.3-fpm
```

`git pull` — fast-forward `main`. Если история разошлась, деплой останавливается, force pull не делается.

`composer install --no-dev` ставит зависимости ровно по lock-файлу. `npm ci` так же по lock-файлу, затем production-сборка Vite кита. Лендинг — Blade и vanilla JS, отдельной сборки не требует; `npm run build` нужен админке.

`php artisan optimize` собирает кэши конфигурации, маршрутов и представлений. После смены `.env` кэш конфигурации нужно сбросить и собрать снова.

Имя сервиса FPM завязано на PHP 8.3. Если шаг 1 по открытому вопросу останется на другом пакете PHP, в этой команде меняется только имя сервиса.

## Права

Каталог `deploy:www-data`, setgid `2775` на корне. После `git pull` новые файлы должны наследовать группу `www-data`. `storage` и `bootstrap/cache` доступны на запись пользователю PHP-FPM (`www-data`).

## Что не входит в деплой

- Правка конфигов nginx и sshd.
- `composer update`.
- Копирование `.env` из репозитория.
- Перезапуск MySQL.

Первый выклад на шаге 2 отличается тем, что код появляется через `composer create-project` во временный каталог, а не через `git pull`. Схема выше — для последующих обновлений, когда приложение уже в `main`.
