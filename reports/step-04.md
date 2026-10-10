# Шаг 04. Лендинг и заявки

## Что сделано

- `reference/landing-index.html` перенесён в `resources/views/landing.blade.php`. CSS — `resources/css/landing.css`, JS — `resources/js/landing.js`. Оба файла добавлены во вход Vite рядом с админскими. `welcome.blade.php` удалён. `GET /` — `LandingController@show`, имя роута `landing`.
- В `<head>`: прежний title и description, canonical `https://nadia.owlsolutions.net/`, og:title, og:description, og:type=website, csrf. Внешних шрифтов нет. Три `@stack` пустые.
- Форма `#contact` пишет заявку: JSON `POST /leads` и обычный POST без JS. Honeypot и `started_at` младше 2 секунд сохраняют заявку как spam и не шлют `LeadCreated`. Валидная заявка — 201 и событие. Throttle `leads`: 5 запросов в минуту с IP.
- Админка: таблица, фильтры, карточка, статус, заметка, удаление через Dialog, CSV без `ip_hash`. Бейдж `status=new` в меню. KPI Leads — последние 30 дней без spam.
- `HandleInertiaRequests` подключён к web-группе: без этого общие props (бренд, бейджи, счётчик заявок) не доходили до React. `config('owl-admin.branding')` не содержал ai/telegram — они были только значением по умолчанию `config()` и не выполнялись. Теперь они мержатся поверх бренда.
- Тесты идут в MySQL `nadia_test`. Пароль берётся из `.env`, в phpunit.xml его нет. Кэш конфигурации, маршрутов и событий в тестах смотрит на несуществующие файлы, чтобы не подхватить production-кэш.

## Изменённые файлы

- `resources/views/landing.blade.php`, `resources/css/landing.css`, `resources/js/landing.js`, удалён `resources/views/welcome.blade.php`
- `vite.config.js`, `package.json`, `package-lock.json` (`playwright` в devDependencies)
- `routes/web.php`, `routes/owl-admin-pages.php`, `bootstrap/app.php`
- `app/Http/Controllers/LandingController.php`, `LeadController.php`
- `app/Http/Requests/StoreLeadRequest.php`, `UpdateLeadRequest.php`
- `app/Models/Lead.php`, `app/Events/LeadCreated.php`, `app/Support/IpHasher.php`
- `app/Enums/LeadStatus.php`, `LeadSource.php`, `LeadGoal.php`
- `database/migrations/2026_10_11_120000_create_leads_table.php`
- `app/Http/Middleware/HandleInertiaRequests.php`, `app/Providers/AppServiceProvider.php`
- `app/Console/Commands/NadiaSmokeCommand.php`
- `resources/js/Pages/Leads/Index.jsx`, `Show.jsx`, `Dashboard.jsx`, `Layouts/AdminLayout.jsx`
- `phpunit.xml`, `.gitignore`, `tests/Feature/LeadTest.php`, `tests/Feature/ExampleTest.php`
- `docs/01-architecture.md`, `docs/02-data-model.md`, `docs/03-roadmap.md`
- `reports/step-04/*.png`

Email в `leads` nullable: на форме он обязателен, в чате (шаг 5) достаточно email или телефона. Длины колонок — из задания шага 4; где длина не была задана, оставлена из `docs/02`.

## Выполненные команды

```bash
npm run build
php artisan migrate --force
php artisan optimize
sudo systemctl reload php8.5-fpm
php artisan nadia:smoke
php artisan test
./vendor/bin/phpunit --colors=never
npm install -D playwright
npx playwright install chromium
sudo npx playwright install-deps chromium
```

Системный Chrome не ставился. Библиотеки (`libnspr4` и соседние) поставил `playwright install-deps`.

## Результаты проверок

`php artisan nadia:smoke` — код 0:

```
✓ route login
✓ route logout
✓ route dashboard
✓ route landing
✓ route leads.store
✓ route leads.index
✓ route leads.show
✓ route leads.update
✓ route leads.destroy
✓ route leads.export
✓ route chats.index
✓ route analytics.index
✓ route tracking.index
✓ route settings.index
✓ route ai-settings.index
✓ route statistics.logs
✓ route profile.edit
✓ route owl-admin.health
✓ route telegram.webhook
✓ no routes customers.*
✓ no routes orders.*
✓ no routes services.*
✓ no routes staff.*
✓ no routes calendar.*
✓ app-settings.index is only the /settings redirect
✓ table customers absent
✓ table services absent
✓ table staff absent
✓ table orders absent
✓ table order_staff absent
✓ table users present
✓ table ai_provider_settings present
✓ table telegram_bot_settings present
✓ table leads present
✓ landing route is /
✓ vite manifest exists
✓ manifest resources/js/Pages/Leads/Index.jsx
✓ manifest resources/js/Pages/Chats/Index.jsx
✓ manifest resources/js/Pages/Analytics/Index.jsx
✓ manifest resources/js/Pages/Tracking/Index.jsx
✓ manifest resources/js/Pages/Settings/AssistantPanel.jsx
✓ manifest resources/js/landing.js
✓ manifest resources/css/landing.css
✓ GET / returns the landing headline
```

`./vendor/bin/phpunit --colors=never` в этой среде печатает одну строку, код 0:

```
{"tool":"phpunit","result":"passed","tests":8,"passed":8,"assertions":38,"duration_ms":1580}
```

Тесты: `ExampleTest` (лендинг 200 и заголовок), unit `test_that_true_is_true`, и шесть в `LeadTest`: валидный JSON с UTM и `ip_hash` плюс `LeadCreated`; нет email → 422; honeypot → 200 и spam без события; `started_at` младше 2 секунд → spam без события; шестой запрос → 429; гость не видит список, карточку и CSV, авторизованный фильтрует, меняет статус и заметку, CSV содержит заголовок и строку и не содержит `ip_hash`.

`curl -I https://nadia.owlsolutions.net/` — `HTTP/1.1 200 OK`. HTML 11309 байт. Бандл лендинга не содержит `react` / `inertia`:

| Файл | Сырой | gzip |
| --- | --- | --- |
| `landing-B1yHkAnu.css` | 7127 | 2260 |
| `landing-CPfotccM.js` | 3375 | 1591 |

Админский JS (`app-*.js`, около 348 КБ) в HTML лендинга не подключён.

Заявка `POST /leads` с CSRF, `utm_source=test`, `utm_campaign=step4` — 201 `{"ok":true,"name":"Step Four"}`. В `/leads` она видна: Buy a home, Form, New, landing path `/?utm_source=test&utm_campaign=step4`. Бейдж меню — 2: вторая заявка «Browser Check» создана отправкой формы в Playwright, тоже `new`.

Скриншоты в `reports/step-04/`: `landing-desktop.png`, `landing-mobile.png` (390px), `form-before.png`, `form-after.png`, `leads.png`, `lead-show.png`.

Отличия от `reference/landing-index.html`:

- Стили вынесены в файл. Правила те же. К селектору полей добавлен `textarea`. Добавлены классы ошибки, honeypot и строки благодарности.
- В head — canonical, Open Graph, csrf, `@vite` и пустые stack. Блока `<style>` нет.
- В форме появились Phone и Anything else?, скрытые поля атрибуции и honeypot. Подписи цели те же, у option появились `value`. Кнопка отправляет заявку, а не `alert`.
- Липкое меню при прокрутке к `#contact` накрывает верх секции — так же, как `position:sticky` в макете.

Тексты секций, цвета, сетка и футер совпадают с макетом, включая «Replace with your hero portrait» и дисклеймер.

## Проблемы

- Первый `php artisan test` шёл с production-кэшем конфигурации: CSRF отвечал 419, а транзакция `RefreshDatabase` открывалась на базе `nadia` и откатилась. Пользователь админки на месте, тестовых строк не осталось. Autoincrement `leads` сдвинулся, живая заявка получила id 3. Повтор с отдельными путями кэша прошёл на `nadia_test`.

## Что нужно от тестера

- Открыть https://nadia.owlsolutions.net/ и сравнить с `reference/landing-index.html`.
- Отправить форму и увидеть строку в `/leads`, бейдж новых заявок и CSV по фильтру.
- `php artisan nadia:smoke` и `php artisan test` на сервере. Тесты не должны видеть базу `nadia`.
