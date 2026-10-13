# 09. Восстановление

Дампы пишет `/usr/local/bin/nadia-db-backup.sh`. Cron: `/etc/cron.d/nadia-db-backup`, каждый день в 03:30 UTC, лог `/var/log/nadia-db-backup.log`. Архивы: `/var/backups/nadia/nadia-YYYYMMDD.sql.gz`, режим `600`, каталог `700`, хранятся 14 последних. Пароль БД лежит в `/home/deploy/.nadia-db-credentials` (ключи `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). В команды ниже он не подставляется текстом.

## Поднять дамп в текущую базу

Команды от пользователя, который читает файл кредов. Текущая база `nadia` будет перезаписана содержимым дампа.

```bash
creds=/home/deploy/.nadia-db-credentials
set -a
source "$creds"
set +a

cnf=$(mktemp)
chmod 600 "$cnf"
cat > "$cnf" <<EOF
[client]
host=127.0.0.1
user=${DB_USERNAME}
password=${DB_PASSWORD}
EOF

gzip -dc /var/backups/nadia/nadia-YYYYMMDD.sql.gz | mysql --defaults-extra-file="$cnf" "$DB_DATABASE"
rm -f "$cnf"

cd /var/www/nadia
php artisan optimize:clear
php artisan optimize
```

`YYYYMMDD` — дата нужного архива. Перед восстановлением поверх живой базы имеет смысл снять свежий дамп тем же скриптом.

Проверка после импорта: в `users` есть админ, в `leads` — заявки из того дампа. Затем `php artisan nadia:smoke`.

## Сервер с нуля

1. Поднять машину по [04-server.md](04-server.md): пользователь `deploy`, nginx, PHP 8.5 FPM, MySQL 8.4, certbot, cron бэкапа, ufw, fail2ban.
2. Клон `main` в `/var/www/nadia`. Секреты не из git: `.env`, `/home/deploy/.nadia-db-credentials`, `/home/deploy/.nadia-admin-credentials`, токен Composer в `~/.config/composer/auth.json`.
3. Базу `nadia` создать пустой и залить дамп командами выше.
4. Дальше деплой из [06-deploy.md](06-deploy.md): `deploy/scripts/deploy.sh`.
5. nginx — `deploy/nginx/nadiaglavin.com.conf`. Сертификаты certbot: `nadiaglavin.com` (и `www`) для сайта, `nadia.owlsolutions.net` для HTTPS-редиректа.
6. Что уже сделано в приложении и что ещё открыто у заказчика — [08-handover.md](08-handover.md) и [03-roadmap.md](03-roadmap.md).

Копия скрипта бэкапа без секретов: `deploy/scripts/nadia-db-backup.sh`. На сервере рабочий файл — `/usr/local/bin/nadia-db-backup.sh`.
