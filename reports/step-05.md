# Шаг 05. ИИ-ассистент

## Исправления шага 4

### Предохранитель тестов

`tests/TestCase.php` поднимает приложение и до `parent::setUp()` (а значит до `RefreshDatabase` и миграций) останавливает прогон, если база не `nadia_test` или конфигурация закэширована не в `/tmp/nadia-phpunit-config.php`. Сообщение: `Refusing to run tests against non-test database`.

Проверка: в `phpunit.xml` временно `DB_DATABASE=nadia`, затем `./vendor/bin/phpunit --filter test_json_lead_is_stored_with_attribution_and_event`.

- exit 1, один тест, сообщение ровно `Refusing to run tests against non-test database`, стек на `tests/TestCase.php:29`
- в выводе нет миграций и SQL
- заявки в боевой базе до и после: 2 и 2
- `phpunit.xml` возвращён на `nadia_test`

### Playwright

Пакет убран из `devDependencies`, `package-lock.json` обновлён, `npm install` прогнан, каталог `~/.cache/ms-playwright` удалён. Системные пакеты `install-deps` не снимались.

Команда из `/var/log/apt/history.log` (2026-10-10 22:37:31): `apt-get install -y --no-install-recommends` и список библиотек Chromium. Уже стоявшие пакеты apt повторно не ставил. Строка `Install:` — то, что появилось этой командой:

`xfonts-cyrillic`, `libxres1`, `fonts-wqy-zenhei`, `libavahi-common-data`, `libxdamage1`, `libcups2t64`, `libatspi2.0-0t64`, `libasound2t64`, `libxfont2`, `libfontenc1`, `xauth`, `xserver-common`, `libnss3`, `xfonts-encodings`, `fonts-liberation`, `libatk1.0-0t64`, `x11-xkb-utils`, `libasound2-data`, `libavahi-common3`, `xvfb`, `fonts-noto-color-emoji`, `xfonts-utils`, `fonts-freefont-ttf`, `fonts-tlwg-loma-otf`, `xfonts-scalable`, `fonts-unifont`, `libatk-bridge2.0-0t64`, `fonts-ipafont-gothic`, `at-spi2-common`, `libavahi-client3`, `libnspr4`.

В команде ещё были `libcairo2`, `libdbus-1-3`, `libdrm2`, `libgbm1`, `libglib2.0-0t64`, `libpango-1.0-0`, `libx11-6`, `libxcb1`, `libxcomposite1`, `libxext6`, `libxfixes3`, `libxkbcommon0`, `libxrandr2`, `libfontconfig1`, `libfreetype6` — они уже стояли и в `Install:` не попали.

### Карточка заявки

`ip_hash` больше не уходит в props и не рисуется. Подписи полей человеческие, en/ru/uk: UTM source/medium/campaign/term/content, Referrer, Landing page, Visitor ID, User agent. Если у заявки есть сессия чата, на карточке транскрипт и ссылка на чат.

## Что сделано

- Таблицы `chat_sessions`, `chat_messages`, `site_settings`. У `leads.chat_session_id` внешний ключ `nullOnDelete`. Миграция кладёт `chat_enabled=1`, приветствие и черновик system prompt.
- `App\Support\Settings` читает cache store и сбрасывает ключ при записи.
- В `AiProviderManager` кита есть только `listModels`. Генерации ответа нет, поэтому свой слой `App\Ai\ChatClient`: OpenAI Chat Completions (`max_tokens`), Anthropic Messages API (`anthropic-version: 2023-06-01`), Gemini `generateContent`. Таймаут 30 с, один повтор через 1 с на 429/5xx. Ключ в логи и в `chat_messages.error` не пишется.
- `POST /chat/message`: 503 без активного провайдера или при выключенном чате; лимиты из `config/nadia.php`; сессия с cookie `nadia_vid` / `nadia_ft` (эти cookie исключены из шифрования); блок `<lead>` вырезается, заявка `source=chat` и событие `LeadCreated` только при создании. Повторный блок дописывает пустые поля. Ошибка провайдера — 200 и запасной текст, ошибка сохраняется в сообщении.
- Первая строка промпта `[DRAFT — replace with client-approved prompt]` в модель не уходит. Приветствие только в виджете.
- Виджет vanilla JS, без React. В HTML лендинга он есть только когда чат доступен на сервере.
- Админка: вкладка Assistant, список чатов, карточка с пузырями, удаление, транскрипт на заявке. Подписи en/ru/uk.
- `nadia:smoke` проверяет новые роуты, таблицы и входы Vite. Строка `chat available` не влияет на код выхода.

Размер виджета после `npm run build`: `chat-widget.js` 5683 байта, gzip 2166; `chat-widget.css` 3055 байт, gzip 1071.

## Сомнения

- У кита нет метода генерации, только список моделей. Слой `App\Ai` написан отдельно и ключ по-прежнему берётся из активной строки `ai_provider_settings`.
- OpenAI ходит в Chat Completions с `max_tokens`. Responses API и `max_completion_tokens` не используются: активной модели ещё нет, а Chat Completions покрывает обычные модели из списка кита.
- Суточный лимит по `ip_hash` считает сообщения сессий с хэшем текущего дня. Сессия, открытая вчера, несёт вчерашний хэш; глобальные 1500 сообщений в сутки остаются общим потолком.

## Проверки

```bash
npm run build
php artisan migrate --force
php artisan optimize
sudo systemctl reload php8.5-fpm
php artisan nadia:smoke
./vendor/bin/phpunit --colors=never
```

`nadia:smoke` — все обязательные пункты зелёные, в конце информационная строка `chat available: no (none)`.

PHPUnit: 19 тестов, 144 утверждения, passed. Запросы к провайдерам только через `Http::fake`.

Боевой сайт без активной строки провайдера (`is_active` и `is_connected`):

- `GET /` — 200, в HTML нет `nadia-chat-boot` и `chat-widget`
- `POST /chat/message` — 503 `{"error":"unavailable"}`
- заявок по-прежнему 2

Ключ ИИ не вводился.
