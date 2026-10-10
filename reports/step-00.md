# Шаг 00. Документация и структура репозитория

## Что сделано

- Добавлены README, документация `docs/00`–`docs/07`, шаблон отчётов и этот отчёт.
- Добавлен `.gitignore` Laravel 12 (официальный список из ветки `12.x` репозитория `laravel/laravel`).
- Зафиксированы перенос Laravel из временного каталога, типы и индексы таблиц, план сервера по фактическому состоянию машины.
- Код приложения не писался. Пакеты не ставились. Конфиги сервера не менялись.

## Изменённые файлы

- `.gitignore`
- `README.md`
- `docs/00-brief.md`
- `docs/01-architecture.md`
- `docs/02-data-model.md`
- `docs/03-roadmap.md`
- `docs/04-server.md`
- `docs/05-security.md`
- `docs/06-deploy.md`
- `docs/07-open-questions.md`
- `reports/README.md`
- `reports/step-00.md`

Не добавлены, потому что их не было во входе:

- `reference/landing-index.html`
- `reference/admin-kit-install.md`

## Выполненные команды

Только чтение состояния, без установки пакетов и без правок `/etc`:

- `git status`, `git remote -v`, `git ls-remote origin`
- `ssh -T git@github.com`
- `free -h`, `df -h`, `timedatectl`, `swapon --show`
- `dpkg -l` / проверка `nginx`, `php`, `mysql`, `composer`, `node`, `certbot`, `fail2ban`
- `sudo ufw status`
- `ss -lntup`
- `stat /var/www/nadia`, `id deploy`
- `sudo sshd -T` и просмотр `PermitRootLogin` / `PasswordAuthentication`
- `apt-cache policy` для `php8.3-fpm`, `php8.5-fpm`, `nodejs`, `mysql-server`
- `systemctl is-enabled unattended-upgrades`

## Результаты проверок

- Репозиторий до коммита был пустым, origin `git@github.com:Owiiiii1/nadia.git`, SSH-аутентификация к GitHub проходит.
- `/var/www/nadia` — `deploy:www-data`, режим `2775`.
- ОС Ubuntu 26.04.1, RAM 3.7 GiB, swap нет, таймзона `Etc/UTC`.
- ufw установлен и выключен. unattended-upgrades включён.
- nginx, PHP, MySQL, Composer, Node, certbot, fail2ban не установлены.
- В архиве Ubuntu есть PHP 8.5 и Node 22.22.1, пакетов PHP 8.3 нет.
- SSH: `PermitRootLogin yes`, `PasswordAuthentication yes` (drop-in cloud-init). Это не совпадает с ожиданием «уже закрыто».

## Проблемы

- Входные файлы `reference/landing-index.html` и `reference/admin-kit-install.md` в задачу не попали и на диске не найдены. Содержимое не восстанавливалось. Шаг 2 блокирует отсутствие инструкции кита, шаг 4 — отсутствие HTML лендинга. См. вопрос 12 в `docs/07-open-questions.md`.
- PHP 8.3 нельзя поставить из архива этой Ubuntu без отдельного решения. См. вопрос 6.

## Что нужно от тестера

- Прочитать docs и сверить их с ТЗ.
- Положить в репозиторий два входных файла как есть, если они должны были быть на этом шаге.
- Подтвердить или поправить открытые вопросы, в первую очередь источник PHP 8.3.
- Убедиться, что в коммите нет секретов.
