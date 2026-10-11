# Шаг 06. Аналитика и клиенты ИИ

## Клиенты

OpenAI шлёт `max_completion_tokens`. У `gpt-5*` в запрос добавляется `reasoning_effort: minimal`, у `o1*` / `o3*` / `o4*` — `low`. Для этих моделей лимит не меньше 2000 и `temperature` не передаётся. Остальные chat-модели получают запрошенный лимит (500) без `reasoning_effort` и без `max_tokens`.

Gemini 2.5 и 3.x получают `thinkingConfig.thinkingBudget`: 0 у flash, 512 у pro, `maxOutputTokens` 2000. `gemini-2.0-flash` без thinking и с лимитом 500.

Anthropic без изменений запроса. Текст ответа собирается из всех блоков `type=text`.

Пустой текст любого провайдера не разбирается как заявка. Посетителю уходит запасной ответ, в `chat_messages.error` пишется `empty response`.

## Сбор

`resources/js/tracker.js` — отдельный entry без зависимостей, подключён на лендинге всегда. `nadia_vid` как раньше, на год. `nadia_sid` — UUID, `Max-Age` 1800, продлевается на каждом хите. `path` — только pathname, UTM отдельно, referrer только внешний. События: `nadia:lead` → `form_submit`, `nadia:chat_open`, `nadia:chat_lead`. `sendBeacon` с `Blob` `text/plain`, иначе `fetch` keepalive. При `navigator.webdriver === true` ничего не уходит.

`POST /t` без CSRF, throttle `track` 60/мин по IP, тело до 4 КБ, ответ всегда 204. Запись только если host `Origin` или, при пустом Origin, host `Referer` равен host `APP_URL`. Не пишется пустой UA, UA из `nadia.tracking.bot_pattern` и визит залогиненного админа. Устройство, браузер и ОС разбирает `App\Support\UserAgent`. `ip_hash` в эти таблицы не пишется, колонки `is_bot` нет.

Сутки Чикаго считаются в PHP и подставляются в один SQL `CASE`: таблицы таймзон MySQL пустые, `CONVERT_TZ` возвращает NULL. Отчёт кэшируется на 5 минут.

## Админка

`/analytics`: Today / 7d / 30d / 90d / Custom. Карточки Visitors, Sessions, Pageviews, Leads (без spam), Conversion, Chat opens, Chat leads и дельта к предыдущему периоду той же длины. Ноль к нулю — 0%, рост с нуля — прочерк. График Visitors и Pageviews (recharts, только в чанке админки). Топ-10 рефереров, UTM source/medium/campaign, устройств, браузеров и ОС. Пустой период: «No data for this period yet» (en/ru/uk).

Dashboard за те же 30 календарных суток: Leads, Visitors, Conversion, Chats (сессии чата), мини-график и пять последних заявок без spam.

`analytics:prune` удаляет строки старше 12 месяцев пачками по 500. В `schedule:list`: `0 4 * * * php artisan analytics:prune`.

## Проверки

`npm run build`, `php artisan migrate --force` (миграция `2026_10_11_210000_create_analytics_tables`), `php artisan optimize`, `sudo systemctl reload php8.5-fpm`.

`nadia:smoke` — все пункты зелёные, включая `route track`, таблицы `page_views` и `site_events`, `manifest resources/js/tracker.js`, `schedule contains analytics:prune`. Строка `chat available: no (none)` на код выхода не влияет.

PHPUnit после optimize: 29 тестов, 225 проверок, все зелёные. Новые: `TrackTest` (pageview, бот, чужой Origin, неизвестное событие, тело больше 4 КБ, залогиненный админ), `UserAgentTest`, `AnalyticsTest` (границы полуночи Чикаго, dashboard, prune, гость), `ChatTest` на `gpt-4.1-mini`, `gpt-5-mini`, `o4-mini`, `gemini-2.5-flash`, `gemini-2.5-pro`, склейку блоков Claude и пустой ответ.

Прод, без сессии админа:

- обычный Chrome UA и `Origin: https://nadia.owlsolutions.net` на `POST /t` — 204, одна строка `/step-06-check`, desktop / Chrome / Windows, referrer `www.google.com`;
- UA `curl/8.5.0` — 204, строки нет;
- заявок по-прежнему 2;
- в HTML `/` есть tracker, блока `nadia-chat-boot` нет;
- `POST /chat/message` с CSRF — 503 `{"error":"unavailable"}`.

`/analytics` за 30 дней: 1 посетитель, 1 сессия, 1 просмотр, 2 заявки, конверсия 200.0% (заявки шага 4 уже были, визит один — сегодняшний), открытия чата 0, заявок из чата 0. Дельта у нулевых прошлых периодов — прочерк, у нулевых карточек чата — 0%. График на 30 дней, ненулевая точка 11 октября. Реферер `www.google.com`, устройство desktop, браузер Chrome, ОС Windows.

`/dashboard`: Leads 2, Visitors 1, Conversion 200.0%, Chats 0, тот же график и заявки Browser Check (Oct 10, 2026 5:38 PM) и Step Four (Oct 10, 2026 5:34 PM).

Tracker: `public/build/assets/tracker-DV2phQE0.js`, 1.83 КБ, gzip 889 байт. recharts в этот файл не входит.

График: в `package.json` библиотеки не было, поставлен `recharts`.
