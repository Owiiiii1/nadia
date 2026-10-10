# 01. Архитектура

Одно Laravel-приложение. Публичный лендинг — Blade и vanilla JS. Админка — страницы кита на Inertia и Vite. Общие: БД, `.env`, пользователи кита, `ai_provider_settings`.

```mermaid
flowchart LR
  visitor[Посетитель]
  landing[Blade лендинг]
  admin[Inertia админка кита]
  leads[(leads)]
  chat[(chat_sessions / chat_messages)]
  analytics[(page_views / site_events)]
  settings[(site_settings / tracking_codes)]
  kitAi[(ai_provider_settings)]

  visitor --> landing
  landing -->|POST /leads| leads
  landing -->|POST /chat/message| chat
  landing -->|POST /t| analytics
  chat -->|блок lead| leads
  chat --> kitAi
  admin --> leads
  admin --> chat
  admin --> analytics
  admin --> settings
  admin --> kitAi
  landing --> settings
```

Лендинг не входит в Vite-сборку кита и не использует Inertia. Админка не подключает маячок, пиксели и сниппеты.

## Публичные роуты

| Метод | Путь | Назначение |
| --- | --- | --- |
| GET | `/` | Лендинг |
| POST | `/leads` | Заявка с формы |
| POST | `/chat/message` | Сообщение ассистенту |
| POST | `/t` | Маячок аналитики (`sendBeacon`) |

`POST /leads` и `POST /chat/message` проходят CSRF, валидацию и throttle. На форме дополнительно honeypot. `POST /t` тоже с CSRF-токеном в теле запроса: `sendBeacon` умеет отправить `FormData` с `_token`.

## Роуты админки

Префикс и имена роутов — как их задаёт кит v0.5.0. В брифе явно назван путь `owl-admin/health`.

Оставить:

- Auth
- Dashboard, переделанный под KPI: заявки, визиты, конверсия
- Settings: General, Users, AI, новая вкладка Assistant
- Profile
- Statistics / Logs
- `owl-admin/health`
- Telegram
- App, если вкладка не завязана на Customers, Orders, Services, Staff, Calendar

Удалить вместе с роутами, контроллерами, моделями, страницами, пунктами меню, миграциями и таблицами: Customers, Orders, Services, Staff, Calendar.

Добавить разделы: Заявки, Чаты, Аналитика, Пиксели и коды.

## Как чат берёт провайдера

1. Лендинг показывает кнопку чата только если в `site_settings` чат включён (`chat_enabled`) и в `ai_provider_settings` есть активный провайдер. Решение принимается на сервере при рендере `GET /`.
2. `POST /chat/message` читает активные провайдер и модель из `ai_provider_settings`. Ключ расшифровывает кит, в репозиторий и в логи он не попадает.
3. В запрос к провайдеру уходят system prompt из `site_settings.assistant_system_prompt` и последние 20 сообщений сессии. Ответ ограничен примерно 500 output tokens.
4. Сессия создаётся по `uuid`. У неё не больше 30 сообщений. Одно сообщение пользователя — не больше 1000 символов. Сверх лимита запрос отклоняется.
5. Если в тексте ответа есть `<lead>{json}</lead>`, блок вырезается до отдачи клиенту. JSON — те же поля, что у формы: `name`, `email`, `phone`, `goal`, `message`. Заявка создаётся при непустом `name` и непустом `email` или `phone`, с `source=chat` и ссылкой на сессию. Невалидный блок не создаёт заявку и не показывается посетителю.

Приветствие (`assistant_greeting`) показывается в виджете, это не вызов провайдера.

Провайдеры, которые должен уметь активный ряд кита: OpenAI, Anthropic, Gemini.

## Заявки

Форма: обязательны name, email, goal. Phone и message необязательны. Goal — одно из `sell`, `buy`, `invest`, `consult`.

К заявке с формы и из чата пишутся UTM (`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`), referrer, landing path, visitor_id, ip_hash, user_agent. У формы `source=form`. У чата `source=chat` и `chat_session_id`.

Статусы: `new`, `in_progress`, `done`, `spam`. Заметку пишет админ. CSV выгружает текущую выборку с теми же фильтрами, что таблица: статус, источник, период.

Успешный ответ формы, если заданы ID, даёт фронту сигнал вызвать `fbq('track','Lead')` и gtag `generate_lead`. Вызов только в браузере на лендинге.

## Аналитика

Маячок стоит только на лендинге.

- Cookie `visitor_id`: first-party, 1 год, `SameSite=Lax`, `Secure`, без `HttpOnly` — маячок читает значение сам. Это не сессия Laravel.
- Cookie сессии трекинга живёт 30 минут с последней активности. Имя cookie не должно совпадать с cookie сессии Laravel. В БД поле называется `session_id`.
- `POST /t` передаёт path, referrer, UTM и признаки устройства. Сервер считает `referrer_host`, `ip_hash` и пишет `page_views`.
- Сырой IP не пишется и не логируется этим запросом.
- User-Agent бота не создаёт визит. Колонка `is_bot` в схеме есть; считать ли такие хиты в таблицу — вопрос в [07-open-questions.md](07-open-questions.md).
- События `form_submit`, `chat_open`, `chat_lead` пишутся в `site_events`.

Админские HTML-ответы маячок не содержат.

## Пиксели

`tracking_codes` и ID из `site_settings` (`meta_pixel_id`, `ga4_id`) подключаются только в layout лендинга, в местах `head`, `body_start`, `body_end`. Layout админки их не выводит.

## Появление Laravel в этом репозитории

На шаге 2 в каталоге уже лежат `.git`, `docs/`, `reference/`, `reports/`, `README.md`. `composer create-project` в этот каталог не запускается.

Порядок:

1. `composer create-project laravel/laravel` во временный каталог, ветка/constraint Laravel 12.
2. Скопировать проект в `/var/www/nadia` без `--delete` и без замены `.git`.
3. Не затирать `README.md`, `docs/`, `reference/`, `reports/`.
4. Файл `.env` из временного проекта не коммитить. В git попадает только `.env.example`, после правок под MySQL этого сайта.

Дальше установка кита — строго по `reference/admin-kit-install.md`.
