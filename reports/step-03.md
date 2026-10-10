# Шаг 03. Упрощение админки

## Что сделано

- Удалены CRM-модули кита Customers, Orders, Services, Staff, Calendar: контроллеры, модели, страницы, роуты и их `use`. Старые миграции не откатывались. Файлы миграций удалены. Новая миграция `2026_10_11_000000_drop_crm_tables` делает `dropIfExists` в порядке `order_staff`, `orders`, `staff`, `services`, `customers`, удаляет строки этих миграций из таблицы `migrations`. `down()` пустой.
- Вкладка App убрана: `AppPanel.jsx`, `Pages/AppSettings`, `'app'` из `$allowedTabs` и из вкладок `Settings/Index.jsx`. `GET /app-settings` (`app-settings.index`) оставлен: smoke кита ищет это имя. Редирект идёт на `/settings` без `tab=app`. Неизвестный `?tab=app` на странице настроек попадает во вкладку general.
- Меню: Dashboard, Leads, Chats, группа Statistics (Analytics, Logs), Pixels & codes, Settings. Подписи en/ru/uk. Ключи customers/orders/calendar/staff/services из словаря убраны.
- Заглушки: `/leads` (шаг 4), `/chats` (шаг 5), `/analytics` (шаг 6), `/tracking-codes` (шаг 7), вкладка Assistant (шаг 5). Dashboard — заголовок и четыре KPI со значением «—».
- Бренд `owl-admin.brand_name` по умолчанию «Nadia Glavin». `public/images/company-logo.svg` заменён на монограмму NG.
- `owl-admin:smoke` не умеет исключать модули (только `--preset=core|admin`, CRM зашит в preset admin). Команда кита не менялась. Добавлена `php artisan nadia:smoke`. В `docs/06-deploy.md` проверка после деплоя — она.

## Изменённые файлы

- Удалены: `app/Http/Controllers/{Customers,Orders,Services,Staff,Calendar}Controller.php`, `app/Models/{Customer,Order,Service,Staff}.php`, `resources/js/Pages/{Customers,Orders,Services,Staff,Calendar}/`, `resources/js/Pages/Settings/AppPanel.jsx`, `resources/js/Pages/AppSettings/`, пять миграций `2026_07_05_100000`–`100400`.
- `routes/owl-admin-pages.php`
- `database/migrations/2026_10_11_000000_drop_crm_tables.php`
- `app/Console/Commands/NadiaSmokeCommand.php`
- `app/Http/Controllers/Settings/SettingsController.php`
- `resources/js/Layouts/AdminLayout.jsx`
- `resources/js/Pages/Dashboard.jsx`
- `resources/js/Pages/{Leads,Chats,Analytics,Tracking}/Index.jsx`
- `resources/js/Pages/Settings/Index.jsx`
- `resources/js/Pages/Settings/AssistantPanel.jsx`
- `config/owl-admin.php`
- `public/images/company-logo.svg`
- `docs/01-architecture.md`, `docs/03-roadmap.md`, `docs/06-deploy.md`

Упоминания CRM в `app`, `routes`, `resources`, `database`, `config`, `tests` остались только в `nadia:smoke` (проверка, что роутов и таблиц нет) и в миграции `drop_crm_tables`. `bootstrap/cache` пересобран `optimize`.

## Выполненные команды

```bash
npm run build
php artisan migrate --force
php artisan optimize
sudo systemctl reload php8.5-fpm
php artisan nadia:smoke
php artisan route:list --except-vendor
```

## Результаты проверок

`php artisan nadia:smoke` — код 0:

```
✓ route login
✓ route logout
✓ route dashboard
✓ route leads.index
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
✓ vite manifest exists
✓ manifest resources/js/Pages/Leads/Index.jsx
✓ manifest resources/js/Pages/Chats/Index.jsx
✓ manifest resources/js/Pages/Analytics/Index.jsx
✓ manifest resources/js/Pages/Tracking/Index.jsx
✓ manifest resources/js/Pages/Settings/AssistantPanel.jsx
```

`php artisan route:list --except-vendor` — 34 маршрута (полный список, без обрезки колонок):

```
GET|HEAD  /                                          Closure
GET|HEAD  ai-settings                                ai-settings.index
POST      ai-settings/deactivate                     ai-settings.deactivate
POST      ai-settings/{provider}/activate            ai-settings.activate
POST      ai-settings/{provider}/check               ai-settings.check
POST      ai-settings/{provider}/key                 ai-settings.save-key
GET|HEAD  analytics                                  analytics.index
GET|HEAD  app-settings                               app-settings.index
GET|HEAD  chats                                      chats.index
GET|HEAD  dashboard                                  dashboard
GET|HEAD  leads                                      leads.index
GET|HEAD  login                                      login
POST      login                                      AuthenticatedSessionController@store
POST      logout                                     logout
GET|HEAD  owl-admin/health                           owl-admin.health
PUT       password                                   password.update
GET|HEAD  profile                                    profile.edit
PATCH     profile                                    profile.update
DELETE    profile                                    profile.destroy
GET|HEAD  settings                                   settings.index
POST      settings/language                          settings.language.update
POST      settings/telegram/check                    settings.telegram.check
POST      settings/telegram/remove-webhook           settings.telegram.remove-webhook
POST      settings/telegram/save-token               settings.telegram.save-token
POST      settings/telegram/set-webhook              settings.telegram.set-webhook
POST      settings/users                             settings.users.store
PATCH     settings/users/{user}                      settings.users.update
DELETE    settings/users/{user}                      settings.users.destroy
GET|HEAD  statistics/logs                            statistics.logs
GET|HEAD  storage/{path}                             storage.local
PUT       storage/{path}                             storage.local.upload
POST      telegram/webhook                           telegram.webhook
GET|HEAD  tracking-codes                             tracking.index
GET|HEAD  up                                         Closure
```

Гость, `https://nadia.owlsolutions.net`:

| Путь | Код |
| --- | --- |
| `/customers`, `/orders`, `/services`, `/staff`, `/calendar` | 404 |
| `/leads`, `/chats`, `/analytics`, `/tracking-codes`, `/settings?tab=assistant` | 302 на `/login` |

Залогиненный (cookie jar, CSRF из `<meta name="csrf-token">`):

| Путь | Код | Компонент / вкладка |
| --- | --- | --- |
| `/dashboard` | 200 | Dashboard |
| `/leads` | 200 | Leads/Index |
| `/chats` | 200 | Chats/Index |
| `/analytics` | 200 | Analytics/Index |
| `/tracking-codes` | 200 | Tracking/Index |
| `/settings?tab=assistant` | 200 | tab=assistant |
| `/settings?tab=ai` | 200 | tab=ai |
| `/settings?tab=telegram` | 200 | tab=telegram |
| `/settings?tab=app` | 200 | tab=general |
| `/statistics/logs` | 200 | |
| `/profile` | 200 | |

`GET /login` и страницы админки содержат «Nadia Glavin». `GET /images/company-logo.svg` — 200, монограмма NG.

`SHOW TABLES` базы `nadia`:

```
ai_provider_settings
cache
cache_locks
failed_jobs
job_batches
jobs
migrations
password_reset_tokens
sessions
telegram_bot_settings
users
```

В `migrations` нет `create_customers_table`, `create_services_table`, `create_staff_table`, `create_orders_table`, `create_order_staff_table`. Есть `2026_10_11_000000_drop_crm_tables`.

## Проблемы

- `SmokeTester` кита не принимает список модулей на исключение. `owl-admin:smoke --preset=admin` после этого шага падает на CRM. Не запускался как приёмка и не чинился в vendor. Приёмка — `nadia:smoke`.
- `app-settings.index` оставлен редиректом на `/settings`, потому что vendor (`SmokeTester`, стабы, INSTALL) ссылается на это имя.
- Цифры KPI и сохранение настроек Assistant — следующие шаги, сейчас заглушки.

## Что нужно от тестера

- `php artisan nadia:smoke` на сервере, код 0.
- Меню из шести пунктов, подписи en/ru/uk, бренд «Nadia Glavin» и логотип NG на логине и в шапке.
- Гость: CRM-URL 404, новые разделы редиректят на логин.
- Залогиненным: заглушки «Coming in step N», вкладка Assistant, `?tab=app` открывает General.
