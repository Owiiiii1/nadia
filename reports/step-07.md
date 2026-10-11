# Шаг 07. Пиксели, уведомления, privacy

## Пиксели

`/tracking-codes`: быстрая настройка Meta Pixel ID (10–20 цифр) и GA4 Measurement ID (`G-` и 4–12 символов), плюс свои сниппеты до 20 000 символов. Места: `head`, `body_start`, `body_end`, порядок `sort`. Неактивные не выводятся. Над формой предупреждение, что код вставляется как есть.

`App\Support\TrackingCodes` кэширует набор и сбрасывает его при сохранении. На `/` и `/privacy` гость получает стандартный код Meta (init + PageView и noscript) и gtag, если ID заданы, затем сниппеты. Залогиненный админ не получает ни пиксели, ни сниппеты, ни `tracker.js`. В HTML админки их нет.

`tracker.js` на `nadia:lead` и `nadia:chat_lead` вызывает `fbq('track','Lead')` и `gtag('event','generate_lead')`, если функция уже есть. Ошибка пикселя страницу не ломает.

## Уведомления

Вкладка Notifications. Telegram-вкладка кита остаётся для бота. Оба канала включаются отдельно.

Привязка: кнопка выдаёт 6 цифр на 15 минут. Вебхук через Nutgram на `/start <code>` сохраняет `chat_id` и имя в `notify_telegram_recipients` и не дублирует уже привязанный чат. Неверный код — «Code is invalid or expired.» Остальные сообщения — «This bot only sends notifications.»

Письмо — Markdown, тема `New lead: {name} ({goal})`, Reply-To — email лида. Получателей не больше пяти. Если `MAIL_MAILER` это `log`/`array` или нет host, в панели предупреждение, а тестовая кнопка показывает ошибку. На проде транспорт сейчас `log`, SMTP не вводился. Ключи почты в `.env.example` пустые, `MAIL_FROM_NAME` — `Nadia Glavin website`.

`LeadCreated` ставит отправку `afterResponse`, чтобы форма и чат не ждали Telegram и SMTP. Ошибка канала пишет warning с id лида и `notify_last_error_{channel}`, второй канал при этом уходит. Spam и повторный блок в чате событие не поднимают, уведомления нет.

## Privacy

`GET /privacy` в стиле лендинга. Текст на английском: форма, чат, cookie `nadia_vid` / `nadia_sid` / `nadia_ft`, страницы, источник, тип устройства, IP не хранится, AI-провайдер и Meta/Google только если подключены, аналитика 12 месяцев, контакт `[contact email]`. В HTML-комментарии: `Draft — review with brokerage/legal before publishing`. Ссылка Privacy в футере рядом с Equal Housing Opportunity, строка под формой и такая же под полем чата. Трекер на `/privacy` отдаётся гостю.

## Проверки

`npm run build`, `php artisan migrate --force` (`2026_10_11_220000_create_tracking_codes_table`), `php artisan optimize`, `sudo systemctl reload php8.5-fpm`.

`nadia:smoke` зелёный, включая `tracking.*`, `privacy`, `settings.notifications.update`, таблицу `tracking_codes`. Строка: `notifications: telegram off (0 recipients), email off (0 recipients), mail transport: log`. Чат по-прежнему `no (none)`.

PHPUnit: 37 тестов, 284 проверки, все зелёные.

Прод, без реальных Pixel/GA4 и без SMTP: сниппет `<!-- nadia-test-snippet -->` в `body_end` был виден гостю на `/` и `/privacy`, не виден залогиненным на `/` и в `/dashboard`. После удаления в HTML его нет, строк в `tracking_codes` ноль. В футере есть ссылка на `/privacy`, под формой — Privacy Policy.
