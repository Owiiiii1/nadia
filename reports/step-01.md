# Шаг 01. Подготовка сервера и решения техлида

## Что сделано

- Входные файлы шага 0 скопированы байт в байт в `reference/`.
- Решения техлида внесены в `docs/00`–`docs/07`. Открытым остался только канал уведомлений о заявке (плюс фото/тексты и system prompt, по ним решения не было).
- Сервер подготовлен под Laravel: SSH, swap, ufw, fail2ban, nginx, PHP 8.5-FPM, MySQL 8.4, Composer, Node 22, certbot, supervisor, бэкап БД.
- Laravel не ставился, vhost nginx не создавался, сертификат не выпускался.

## Изменённые файлы

- `reference/landing-index.html`
- `reference/admin-kit-install.md`
- `deploy/scripts/nadia-db-backup.sh`
- `docs/00-brief.md`
- `docs/01-architecture.md`
- `docs/02-data-model.md`
- `docs/03-roadmap.md`
- `docs/04-server.md`
- `docs/05-security.md`
- `docs/06-deploy.md`
- `docs/07-open-questions.md`
- `reports/step-01.md`

Исходники в `docs/index.html` и `docs/инструкция к админке.txt` не редактировались.

## Выполненные команды

Пакеты: `nginx`, `php8.5-fpm`, `php8.5-cli`, `php8.5-mbstring`, `php8.5-xml`, `php8.5-curl`, `php8.5-mysql`, `php8.5-zip`, `php8.5-bcmath`, `php8.5-intl`, `php8.5-gd`, `php8.5-readline`, `mysql-server`, `fail2ban`, `certbot`, `python3-certbot-nginx`, `supervisor`, `nodejs`, `npm`, `unzip`, `curl`, `acl`.

Отдельного пакета `php8.5-opcache` в архиве нет. Zend OPcache уже в PHP 8.5, `opcache.enable=On`.

Дальше без вывода секретов:

- `sshd -t`, `systemctl reload ssh`, проверка нового входа `deploy` по ключу на 127.0.0.1 и на 2.29.102.216
- swap 2 ГБ, `sysctl vm.swappiness=10`
- `ufw allow OpenSSH`, `ufw allow 80/tcp`, `ufw allow 443/tcp`, `ufw enable`
- правка `php.ini` FPM/CLI, `pm.max_children=10`, reload `php8.5-fpm`
- MySQL: база `nadia`, пользователи `nadia`@`localhost` и `nadia`@`127.0.0.1`, `innodb_buffer_pool_size=512M`, bind `127.0.0.1`
- установщик Composer, сверка SHA-384 с `https://composer.github.io/installer.sig`
- `/usr/local/bin/nadia-db-backup.sh` один раз вручную
- cron `/etc/cron.d/nadia-db-backup`: `30 3 * * *`

Пароль БД записан в `/home/deploy/.nadia-db-credentials` (режим 600). В команды отчёта и в git он не входил.

## Результаты проверок

Версии:

| Пакет | Версия |
| --- | --- |
| nginx | 1.28.3 (пакет `1.28.3-2ubuntu1.11`) |
| PHP | 8.5.4 (`8.5.4-0ubuntu1.3`), CLI и FPM |
| MySQL | 8.4.11-0ubuntu0.26.04.1 |
| Composer | 2.10.3 |
| Node | v22.22.1 |
| npm | 9.2.0 |
| certbot | 4.0.0 |
| fail2ban | 1.1.0 |
| supervisor | 4.3.0, программ нет |

`sudo sshd -T`:

```text
permitrootlogin no
passwordauthentication no
kbdinteractiveauthentication no
```

`PubkeyAuthentication yes`. Новое подключение `deploy` по ключу на публичный IP после включения ufw прошло. Парольный вход отклоняется (`Permission denied (publickey)`). Тестовый ключ из `authorized_keys` удалён, рабочий ключ на месте.

`sudo ufw status`: active, allow OpenSSH, 80/tcp, 443/tcp (и те же правила для v6).

`sudo fail2ban-client status sshd`: jail включён, backend по journal `ssh.service`, currently banned 0.

`swapon --show`: `/swapfile` file 2G. `vm.swappiness = 10`.

PHP: сокет `/run/php/php8.5-fpm.sock`, `www-data`, `pm=dynamic`, `max_children=10`. FPM `memory_limit=256M`, `upload_max_filesize=20M`, `post_max_size=25M`, `expose_php=Off` (CLI то же, кроме `memory_limit=-1`). Модули mbstring, xml, curl, mysqli/pdo_mysql, zip, bcmath, intl, gd, readline, Zend OPcache есть.

MySQL слушает `127.0.0.1:3306` и `127.0.0.1:33060`. `innodb_buffer_pool_size=536870912`. Вход `nadia`@`127.0.0.1` в базу `nadia` проходит, charset `utf8mb4` / `utf8mb4_unicode_ci`. GRANT — только `nadia.*` плюс стандартный `USAGE ON *.*`.

nginx активен, `server_tokens off`, `sites-enabled` пуст, порты 80 и 443 не слушаются.

unattended-upgrades: `APT::Periodic::Unattended-Upgrade "1"`, в `Allowed-Origins` есть `${distro_id}:${distro_codename}-security`. Пакет был включён до шага, конфиг не менялся.

Тестовый бэкап: `/var/backups/nadia/nadia-20261010.sql.gz`, 452 байта, режим `600`, `gzip -t` успешен, внутри дамп MySQL 8.4.11 базы `nadia`. Каталог дампов `700 root:root`.

SHA-384 установщика Composer совпал с подписью на composer.github.io.

## Проблемы

- В архиве Ubuntu нет пакета `php8.5-opcache`. OPcache включён как встроенный модуль PHP 8.5.
- В `reference/admin-kit-install.md` пример nginx ссылается на `php8.3-fpm.sock`. Файл не менялся. На шаге 2 нужен сокет `/run/php/php8.5-fpm.sock`.
- Канал уведомлений о заявке по-прежнему не выбран.

## Что нужно от тестера

- Сверить `docs/07-open-questions.md` с решениями: закрыты 2, 3, 6, 7, 8, 9, 10, 11, 12; открыты 1, 4, 5.
- Убедиться, что в коммите нет пароля БД и нет `.nadia-db-credentials`.
- При желании проверить SSH с внешней машины ключом `deploy` и что пароль не принимается.
- Не ждать ответа на портах 80/443 до vhost на шаге 2.
