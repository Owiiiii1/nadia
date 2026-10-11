# 02. Модель данных

СУБД — MySQL. Имена таблиц ниже принадлежат приложению. Таблица `ai_provider_settings` принадлежит киту: колонки берутся из его миграций на шаге 2, здесь они не задаются. Приложение только читает активные провайдер и модель и пользуется расшифровкой ключа средствами кита.

Денежные суммы и мягкое удаление в этих таблицах не используются. Время — `TIMESTAMP` в UTC (`APP_TIMEZONE=UTC`). Показ в админке и группировка аналитики по дням — `America/Chicago` через `config('app.display_timezone')`.

`visitor_id` и публичный `uuid` сессии чата — UUID, `CHAR(36)`. `ip_hash` — 64 hex-символа SHA-256.

## leads

Заявка с формы или из чата.

| Колонка | Тип | NULL | Примечание |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED AUTO_INCREMENT | нет | PK |
| name | VARCHAR(255) | нет | На форме max 120 |
| email | VARCHAR(255) | да | На форме обязателен, max 190. В чате достаточно email или phone |
| phone | VARCHAR(50) | да | На форме max 40 |
| goal | VARCHAR(20) | да | `sell`, `buy`, `invest`, `consult`. На форме обязателен |
| message | TEXT | да | На форме max 2000 |
| source | VARCHAR(10) | нет | `form` или `chat`. По умолчанию `form` |
| status | VARCHAR(20) | нет | По умолчанию `new`. Ещё `in_progress`, `done`, `spam` |
| admin_note | TEXT | да | |
| utm_source | VARCHAR(190) | да | |
| utm_medium | VARCHAR(190) | да | |
| utm_campaign | VARCHAR(190) | да | |
| utm_term | VARCHAR(190) | да | |
| utm_content | VARCHAR(190) | да | |
| referrer | VARCHAR(500) | да | |
| landing_path | VARCHAR(500) | да | |
| visitor_id | CHAR(36) | да | |
| chat_session_id | BIGINT UNSIGNED | да | FK → `chat_sessions.id` на шаге 5. Пусто у формы |
| ip_hash | CHAR(64) | да | |
| user_agent | VARCHAR(500) | да | |
| created_at | TIMESTAMP | нет | |
| updated_at | TIMESTAMP | нет | |

Индексы:

- PRIMARY `id`
- `leads_status_created_at_index` (`status`, `created_at`) — фильтр статуса и периода
- `leads_source_created_at_index` (`source`, `created_at`)
- `leads_created_at_index` (`created_at`)
- `leads_visitor_id_index` (`visitor_id`)
- `leads_chat_session_id_index` (`chat_session_id`)

Email не уникален: повторные заявки сохраняются.

## chat_sessions

| Колонка | Тип | NULL | Примечание |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED AUTO_INCREMENT | нет | PK |
| uuid | CHAR(36) | нет | UNIQUE. Идентификатор сессии в API |
| visitor_id | CHAR(36) | да | Cookie `nadia_vid` |
| ip_hash | CHAR(64) | да | |
| user_agent | VARCHAR(500) | да | |
| messages_count | INT UNSIGNED | нет | По умолчанию 0. Считаются все сообщения сессии. Лимит 30 — только роль `user` |
| lead_id | BIGINT UNSIGNED | да | FK → `leads.id`, `nullOnDelete` |
| utm_source | VARCHAR(190) | да | Из cookie `nadia_ft` |
| utm_medium | VARCHAR(190) | да | |
| utm_campaign | VARCHAR(190) | да | |
| referrer | VARCHAR(500) | да | |
| landing_path | VARCHAR(500) | да | |
| last_message_at | TIMESTAMP | да | |
| created_at | TIMESTAMP | нет | |
| updated_at | TIMESTAMP | нет | |

Индексы:

- PRIMARY `id`
- UNIQUE `chat_sessions_uuid_unique` (`uuid`)
- `chat_sessions_visitor_id_index` (`visitor_id`)
- `chat_sessions_lead_id_index` (`lead_id`)
- `chat_sessions_last_message_at_index` (`last_message_at`)

`leads.chat_session_id` и `chat_sessions.lead_id` оба nullable. Сессия создаётся первой. Заявка из чата ссылается на сессию, сессия — на заявку. Ограничения FK ставятся после обеих таблиц.

## chat_messages

| Колонка | Тип | NULL | Примечание |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED AUTO_INCREMENT | нет | PK |
| chat_session_id | BIGINT UNSIGNED | нет | FK → `chat_sessions.id`, cascade |
| role | VARCHAR(10) | нет | `user` или `assistant` |
| content | TEXT | нет | У ответа ассистента уже без блока `<lead>` |
| provider | VARCHAR(20) | да | Для ответа ассистента |
| model | VARCHAR(100) | да | Для ответа ассистента |
| tokens_in | INT UNSIGNED | да | |
| tokens_out | INT UNSIGNED | да | |
| latency_ms | INT UNSIGNED | да | |
| error | VARCHAR(500) | да | Короткая причина, без ключа API |
| created_at | TIMESTAMP | нет | Колонки `updated_at` нет |

Индексы:

- PRIMARY `id`
- `chat_messages_chat_session_id_id_index` (`chat_session_id`, `id`) — последние сообщения сессии и лента в админке

## page_views

Просмотр лендинга. Сырого IP нет.

| Колонка | Тип | NULL | Примечание |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED AUTO_INCREMENT | нет | PK |
| visitor_id | CHAR(36) | да | |
| session_id | CHAR(36) | да | Сессия трекинга, не сессия Laravel |
| path | VARCHAR(500) | нет | Только pathname |
| referrer | VARCHAR(500) | да | |
| referrer_host | VARCHAR(190) | да | Хост из referrer, для топа |
| utm_source | VARCHAR(190) | да | |
| utm_medium | VARCHAR(190) | да | |
| utm_campaign | VARCHAR(190) | да | |
| utm_term | VARCHAR(190) | да | |
| utm_content | VARCHAR(190) | да | |
| device | VARCHAR(10) | да | `desktop`, `mobile`, `tablet` |
| browser | VARCHAR(40) | да | |
| os | VARCHAR(40) | да | |
| created_at | TIMESTAMP | нет | |

Запросы ботов в эту таблицу не попадают, колонки признака бота нет.

Индексы:

- PRIMARY `id`
- `page_views_created_at_index` (`created_at`) — обязателен по ТЗ
- `page_views_visitor_created_index` (`visitor_id`, `created_at`) — уникальные посетители за период
- `page_views_session_id_index` (`session_id`)
- `page_views_referrer_host_index` (`referrer_host`)

## site_events

| Колонка | Тип | NULL | Примечание |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED AUTO_INCREMENT | нет | PK |
| visitor_id | CHAR(36) | да | |
| session_id | CHAR(36) | да | |
| type | VARCHAR(30) | нет | `form_submit`, `chat_open`, `chat_lead` |
| meta | JSON | да | |
| created_at | TIMESTAMP | нет | |

Индексы:

- PRIMARY `id`
- `site_events_type_created_index` (`type`, `created_at`)
- `site_events_created_at_index` (`created_at`)
- `site_events_visitor_created_index` (`visitor_id`, `created_at`)

## tracking_codes

| Колонка | Тип | NULL | Примечание |
| --- | --- | --- | --- |
| id | BIGINT UNSIGNED AUTO_INCREMENT | нет | PK |
| name | VARCHAR(255) | нет | |
| placement | VARCHAR(16) | нет | `head`, `body_start`, `body_end` |
| code | TEXT | нет | Вставляется как есть только в layout лендинга |
| is_active | TINYINT(1) | нет | По умолчанию 1 |
| sort | INT | нет | По умолчанию 0. Меньше — раньше |
| created_at | TIMESTAMP | нет | |
| updated_at | TIMESTAMP | нет | |

Индекс: `tracking_codes_active_placement_sort_index` (`is_active`, `placement`, `sort`).

## site_settings

Ключ–значение.

| Колонка | Тип | NULL | Примечание |
| --- | --- | --- | --- |
| key | VARCHAR(100) | нет | PK |
| value | LONGTEXT | да | |
| created_at | TIMESTAMP | нет | |
| updated_at | TIMESTAMP | нет | |

`App\Support\Settings` читает значение из cache store и сбрасывает ключ при `set`. Миграция ставит `chat_enabled=1`, приветствие и черновик system prompt, если таблицы ещё нет. Пустой ключ в коде не затирает уже сохранённое значение: вставка только при создании таблицы.

Ключи:

| key | Назначение |
| --- | --- |
| assistant_system_prompt | System prompt ассистента |
| assistant_greeting | Приветствие в виджете |
| chat_enabled | Чат включён |
| meta_pixel_id | Meta Pixel ID |
| ga4_id | GA4 Measurement ID |
| lead_notify_* | Префикс зарезервирован под канал уведомлений о заявке. Конкретные ключи — после решения в открытых вопросах |

## Связи

- `chat_messages.chat_session_id` → `chat_sessions.id`
- `leads.chat_session_id` → `chat_sessions.id`
- `chat_sessions.lead_id` → `leads.id`
- `leads.visitor_id`, `chat_sessions.visitor_id`, `page_views.visitor_id`, `site_events.visitor_id` — одна и та же cookie, без FK между таблицами

## ip_hash

`ip_hash = sha256(ip + '|' + дата UTC Y-m-d + '|' + app.key)` в hex. Считает `App\Support\IpHasher`. Сырой IP в `leads` и `chat_sessions` не пишется. В `page_views` и `site_events` хэша нет: визиты админа отсекаются сессией, боты — по User-Agent.
