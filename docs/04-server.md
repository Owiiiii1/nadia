# 04. Сервер

План шага 1. Команды на шаге 0 не выполнялись: пакеты не ставились, конфиги не менялись. Факты ниже сняты 2026-10-10 с этой машины.

## Факты

| | |
| --- | --- |
| IP | 2.29.102.216 |
| ОС | Ubuntu 26.04.1 LTS (`resolute`), ядро 7.0.0-38-generic |
| Hostname | `ubuntu-4gb-hel1-2` |
| RAM | 3.7 GiB, swap нет |
| Диск | 38 ГБ, занято около 2.6 ГБ |
| Часовой пояс | `Etc/UTC`, NTP включён |
| Каталог сайта | `/var/www/nadia`, `deploy:www-data`, режим `2775` |
| Пользователь | `deploy` в группах `sudo`, `www-data` |
| Git | origin `git@github.com:Owiiiii1/nadia.git`, ветка `main` |
| Домен | `nadia.owlsolutions.net`, A-запись по брифу уже на этот сервер. На шаге 0 nginx нет, порты 80 и 443 не слушаются |

Уже стоит из базовой системы: `ufw` (выключен), `unattended-upgrades` (enabled и active), `git`, OpenSSH.

Ещё не установлено: nginx, PHP, MySQL, Composer, Node, npm, certbot, fail2ban, supervisor.

### Расхождение с ожиданием «уже сделано»

Ожидалось: вход только по ключу, root-логин закрыт.

Фактический конфиг на момент шага 0:

- `/etc/ssh/sshd_config`: `PermitRootLogin yes`
- `/etc/ssh/sshd_config.d/50-cloud-init.conf`: `PasswordAuthentication yes`
- `PubkeyAuthentication` включён, `KbdInteractiveAuthentication no`

На шаге 1 это нужно довести до брифа, не считая задачу закрытой. Перед `sshd` reload проверить вход по ключу отдельной сессией.

### PHP 8.3 и репозиторий Ubuntu

В архиве Ubuntu 26.04 есть `php8.5` (кандидат FPM `8.5.4`) и нет пакетов `php8.3`. Бриф требует PHP 8.3-FPM. Как ставить 8.3 — открытый вопрос в [07-open-questions.md](07-open-questions.md). Пока вопрос не закрыт, шаг 1 не подменяет 8.3 на 8.5.

Node 22 LTS в архиве есть: кандидат `nodejs` `22.22.1`. MySQL: кандидат `mysql-server` `8.4.11`.

## Что сделать на шаге 1

Работать от `deploy` через sudo. Версии пакетов записать в отчёт шага.

1. **SSH.** `PermitRootLogin no`. `PasswordAuthentication no` в конфиге и в drop-in cloud-init, чтобы drop-in не перебил основной файл. Перезагрузить sshd только после проверки ключа.
2. **Часовой пояс.** Оставить `Etc/UTC`. Отдельно не переключать на Chicago: это ОС сервера.
3. **Swap.** RAM меньше 4 ГБ, swap нужен. Файл `/swapfile` на 2 ГБ, запись в `/etc/fstab`, `vm.swappiness=10`.
4. **ufw.** Разрешить OpenSSH, 80/tcp, 443/tcp. Включить. IPv6 не открывать шире, чем IPv4.
5. **fail2ban.** Jail `sshd`.
6. **nginx.** Пакет и сервис. Виртуальный хост сайта — на шаге 2, вместе с Laravel `public` и сертификатом. На шаге 1 достаточно установленного nginx и открытых портов.
7. **PHP 8.3-FPM** после решения по источнику пакетов. Пакеты: `php8.3-fpm`, `php8.3-cli`, `php8.3-mbstring`, `php8.3-xml`, `php8.3-curl`, `php8.3-mysql`, `php8.3-zip`, `php8.3-bcmath`, `php8.3-intl`, `php8.3-gd`. Пул — сокет, пользователь `www-data`.
8. **MySQL.** `mysql-server`. База `nadia`, пользователь `nadia`@`127.0.0.1` и `nadia`@`localhost`, права только на эту базу. Пароль генерируется на сервере и пишется в `.env`, не в git и не в отчёт.
9. **Composer.** Официальный установщик, бинарник в `/usr/local/bin/composer`.
10. **Node 22 LTS.** Пакеты `nodejs` и `npm` из архива Ubuntu. Проверить `node -v` на 22.x. Отдельный NodeSource не нужен, пока кандидат архива остаётся 22.
11. **certbot.** Пакеты `certbot` и `python3-certbot-nginx`. Сертификат выпускается на шаге 2, когда nginx уже отдаёт домен.
12. **unattended-upgrades.** Уже включён. Проверить, что security-обновления включены, и записать это в отчёт. Повторно пакет не ставить без нужды.
13. **cron.** От `www-data`:

    `* * * * * cd /var/www/nadia && php artisan schedule:run >> /dev/null 2>&1`

    Добавить после появления `artisan` (шаг 2). В плане шага 1 — пользователь и строка, чтобы их не забыть.

14. **supervisor.** Установить пакет. Программу воркера очереди не заводить, пока приложению не понадобится queue. Чат по брифу синхронный.

## Что на шаге 1 не делать

- Не создавать Laravel и не ставить кит.
- Не выпускать сертификат до рабочего `server_name`.
- Не коммитить пароли MySQL и ключи API.
