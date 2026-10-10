# 04. Сервер

Состояние после шага 1 (2026-10-10). ОС — Ubuntu 26.04.1 LTS, IP 2.29.102.216, часовой пояс `Etc/UTC`, RAM 3.7 GiB. Каталог сайта `/var/www/nadia`, владелец `deploy:www-data`, режим `2775`.

## Сделано

| Компонент | Факт |
| --- | --- |
| SSH | `/etc/ssh/sshd_config.d/00-hardening.conf`: root и пароль выключены, ключ включён. Префикс `00-` перекрывает `50-cloud-init.conf` |
| Swap | `/swapfile` 2 ГБ, строка в `/etc/fstab`, `vm.swappiness=10` в `/etc/sysctl.d/99-swap.conf` |
| ufw | Включён. Входящие: OpenSSH, 80/tcp, 443/tcp |
| fail2ban | Jail `sshd`, `backend = systemd`, сервис включён. Конфиг пакета: `/etc/fail2ban/jail.d/defaults-debian.conf` |
| nginx | 1.28.3, `server_tokens off`. Default site убран из `sites-enabled`. Своего vhost нет, порты 80 и 443 пока не слушаются |
| PHP | 8.5.4 FPM и CLI. Сокет `/run/php/php8.5-fpm.sock`, пул `www`, пользователь `www-data`, `pm=dynamic`, `pm.max_children=10` |
| PHP ini | FPM и CLI: `upload_max_filesize=20M`, `post_max_size=25M`, `expose_php=Off`. FPM: `memory_limit=256M` |
| MySQL | 8.4.11, слушает только `127.0.0.1` (3306 и mysqlx 33060). `innodb_buffer_pool_size=512M` в `/etc/mysql/mysql.conf.d/zz-nadia.cnf` |
| База | `nadia`, `utf8mb4` / `utf8mb4_unicode_ci`. Пользователи `nadia`@`localhost` и `nadia`@`127.0.0.1`, права только на `nadia.*` |
| Пароль БД | `/home/deploy/.nadia-db-credentials`, режим `600`. Не в git и не в отчётах |
| Composer | 2.10.3 в `/usr/local/bin/composer`, установщик проверен по SHA-384 |
| Node | 22.22.1 и npm 9.2.0 из архива Ubuntu |
| certbot | 4.0.0 и `python3-certbot-nginx`. Сертификат не выпускался |
| supervisor | 4.3.0 установлен, программ нет |
| unattended-upgrades | Включён. В разрешённых origin есть `${distro_id}:${distro_codename}-security` |
| Утилиты | unzip, curl, acl |
| Бэкап БД | `/usr/local/bin/nadia-db-backup.sh`, cron root `30 3 * * *` (03:30 UTC). Каталог `/var/backups/nadia`, режим `700`, 14 последних архивов |

Отдельного пакета `php8.5-opcache` в архиве Ubuntu нет. Модуль Zend OPcache входит в PHP 8.5, `opcache.enable=On`.

Расширения, которые просили: mbstring, xml, curl, mysql, zip, bcmath, intl, gd, readline. OPcache — встроенный.

## Права на шаге 2

`deploy` уже пишет в `/var/www/nadia`. Каталогов `storage` и `bootstrap/cache` ещё нет, ACL на шаге 1 не ставились.

Когда Laravel будет на месте, выдать `www-data` запись:

```bash
sudo setfacl -R -m u:www-data:rwX storage bootstrap/cache
sudo setfacl -R -d -m u:www-data:rwX storage bootstrap/cache
```

Cron `schedule:run` от `www-data` добавляется там же, после появления `artisan`:

```cron
* * * * * cd /var/www/nadia && php artisan schedule:run >> /dev/null 2>&1
```

## Что остаётся на шаг 2

- Виртуальный хост nginx с `root` на `public` и сокетом `/run/php/php8.5-fpm.sock`.
- Сертификат certbot для `nadia.owlsolutions.net`.
- Laravel 12 и кит. Если кит v0.5.0 не встанет на PHP 8.5, вопрос версии PHP открывается снова.
- Пароль БД переносится в `.env` на сервере. Файл кредов не коммитится.
