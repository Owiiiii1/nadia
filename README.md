# Nadia Glavin

Сайт-визитка риелтора (Чикаго) и упрощённая админка в одном приложении Laravel 13.

Публичная часть — обычный Blade-лендинг и vanilla JS. Админка — пакет `owlsolutions/custom-admin-kit` v0.5.0 (Inertia, Vite). Домен: `nadia.owlsolutions.net`.

Код приложения на шаге 0 не создаётся. Корень репозитория — будущий корень Laravel-приложения.

## Документация

| Файл | Содержание |
| --- | --- |
| [docs/00-brief.md](docs/00-brief.md) | Бриф, функционал, вне скоупа |
| [docs/01-architecture.md](docs/01-architecture.md) | Лендинг и админка, роуты, чат, заявки, аналитика |
| [docs/02-data-model.md](docs/02-data-model.md) | Таблицы, типы, индексы, связи |
| [docs/03-roadmap.md](docs/03-roadmap.md) | Шаги 0–8 |
| [docs/04-server.md](docs/04-server.md) | План подготовки сервера |
| [docs/05-security.md](docs/05-security.md) | CSRF, throttle, honeypot, ключи, ip_hash, пиксели |
| [docs/06-deploy.md](docs/06-deploy.md) | Схема деплоя |
| [docs/07-open-questions.md](docs/07-open-questions.md) | Открытые вопросы |

Входные материалы (как есть, без правок):

- `reference/landing-index.html` — текущий лендинг
- `reference/admin-kit-install.md` — установка кита v0.5.0

Отчёты по шагам: [reports/README.md](reports/README.md).
