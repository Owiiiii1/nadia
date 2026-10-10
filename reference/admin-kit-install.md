инструкия разворачивания сервера моего кастомного
_______________________________________________________

# Custom Admin Kit v0.5.0 — clean install instruction

Инструкция для чистой установки `owlsolutions/custom-admin-kit` на новый Laravel-проект.

Текущая рекомендуемая версия:

```bash
owlsolutions/custom-admin-kit:v0.5.0
```

## Что устанавливает preset `admin`

После установки должны быть доступны:

```text
/login
/dashboard
/customers
/orders
/services
/staff
/calendar
/settings
/settings?tab=general
/settings?tab=users
/settings?tab=ai
/settings?tab=app
/settings?tab=telegram
/profile
/statistics/logs
/owl-admin/health
```

Backward-compatible redirects:

```text
/ai-settings  -> /settings?tab=ai
/app-settings -> /settings?tab=app
```

В составе админки:

```text
Auth / Login / Logout
Dashboard
Customers
Orders
Services
Staff
Calendar
Settings hub with tabs: General, Users, AI, App, Telegram
Profile
Statistics / Logs
AI provider settings: OpenAI, Anthropic, Gemini
Telegram / Nutgram settings
Header badges: AI status, Bot status
```

---

# 1. Create clean Laravel project

```bash
cd /var/www
composer create-project laravel/laravel project-name
cd /var/www/project-name
```

Set permissions if needed:

```bash
sudo chown -R deploy:www-data /var/www/project-name
chmod -R ug+rw storage bootstrap/cache
```

---

# 2. Configure `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Minimal `.env` example:

```env
APP_NAME="Project Name"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Important for Telegram webhooks:

```env
APP_URL=https://your-domain.com
```

`APP_URL` must be a valid HTTPS URL before pressing **Set webhook** in Telegram Settings.

Then:

```bash
php artisan config:clear
```

---

# 3. Add package repository

```bash
composer config repositories.custom-admin-kit '{"type":"vcs","url":"https://github.com/Owiiiii1/custom-admin-kit.git","no-api":true}'
```

---

# 4. Install package v0.5.0

```bash
composer require owlsolutions/custom-admin-kit:v0.5.0
```

This should also install:

```bash
nutgram/nutgram
```

Check:

```bash
composer show owlsolutions/custom-admin-kit
composer show nutgram/nutgram
```

Expected:

```text
owlsolutions/custom-admin-kit v0.5.0
nutgram/nutgram 4.48.x
```

---

# 5. Install Inertia and Ziggy

```bash
composer require inertiajs/inertia-laravel tightenco/ziggy
php artisan inertia:middleware
```

If Laravel says the middleware already exists, this is not a problem.

---

# 6. Run doctor

```bash
php artisan owl-admin:doctor --preset=admin
```

On a clean project before install, some warnings are acceptable. Critical `FAIL` items should be fixed before continuing.

---

# 7. Install admin preset

```bash
php artisan owl-admin:install --preset=admin --backup --migrate --no-smoke
```

This publishes routes, controllers, models, migrations, Inertia pages, layouts, settings panels, AI settings, Telegram settings, and CRM starter modules.

Expected new database tables include:

```text
ai_provider_settings
telegram_bot_settings
customers
services
staff
orders
order_staff
```

---

# 8. Frontend setup

```bash
php artisan owl-admin:frontend-setup --preset=admin --backup --install-npm --run-build
```

This should install npm dependencies, configure frontend, merge required routes, and build Vite assets.

---

# 9. Create admin user

For local/testing:

```bash
php artisan owl-admin:make-admin --email=admin@admin.com --password=admin
```

A warning about weak credentials is expected when using this test account.

For a real project:

```bash
php artisan owl-admin:make-admin --email=admin@example.com --password='StrongPasswordHere'
```

---

# 10. Smoke test

```bash
php artisan owl-admin:smoke --preset=admin
```

Expected checks include published files, routes, users table, AI settings table, Telegram settings table, CRM tables, Settings tabs, AI panel, Telegram panel, AdminLayout badges, and Vite manifest.

---

# 11. Check routes

```bash
php artisan route:list | grep -E "login|logout|dashboard|customers|orders|services|staff|calendar|settings|telegram|ai-settings|app-settings|profile|statistics|owl-admin"
```

Expected key routes:

```text
login
logout
dashboard
customers.index/store/update/destroy
orders.index/store/update/destroy/status/assign
services.index/store/update/destroy
staff.index/store/update/destroy
calendar.index
settings.index
settings.language.update
settings.users.store/update/destroy
ai-settings.index
ai-settings.save-key/check/activate/deactivate
app-settings.index
settings.telegram.save-token/check/set-webhook/remove-webhook
telegram.webhook
profile.edit/update/destroy
password.update
statistics.logs
owl-admin.health
```

---

# 12. Final frontend build

```bash
npm run build
```

---

# 13. Nginx example

Create:

```bash
sudo nano /etc/nginx/sites-available/your-domain.com
```

Example config:

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    root /var/www/project-name/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activate:

```bash
sudo ln -s /etc/nginx/sites-available/your-domain.com /etc/nginx/sites-enabled/your-domain.com
sudo nginx -t
sudo systemctl reload nginx
```

SSL:

```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

---

# 14. HTTP checks

```bash
curl -I https://your-domain.com/login
curl -I https://your-domain.com/owl-admin/health
curl -I https://your-domain.com/customers
curl -I https://your-domain.com/settings
curl -I "https://your-domain.com/settings?tab=telegram"
curl -i -X POST https://your-domain.com/telegram/webhook
```

Expected:

```text
/login -> 200
/owl-admin/health -> 200
/customers as guest -> 302 to /login
/settings as guest -> 302 to /login
/settings?tab=telegram as guest -> 302 to /login
/telegram/webhook without configured bot -> controlled JSON 404/403/503, not 500
```

---

# 15. Browser check

Open:

```text
https://your-domain.com/login
```

Login with the created admin account.

Check:

```text
/dashboard
/customers
/orders
/services
/staff
/calendar
/settings
/settings?tab=general
/settings?tab=users
/settings?tab=ai
/settings?tab=app
/settings?tab=telegram
```

Header should show:

```text
AI status badge
Bot status badge
language switcher
avatar menu
Powered by OwlSolutions
```

---

# 16. AI setup

Open:

```text
/settings?tab=ai
```

Supported providers:

```text
OpenAI / ChatGPT
Anthropic / Claude
Google / Gemini
```

Flow:

```text
1. Paste API key.
2. Save key.
3. Check connection.
4. Select available model.
5. Activate model.
```

Expected result:

```text
Header AI badge becomes connected after provider is active and connected.
Only one provider/model can be active at a time.
```

Security:

```text
API key is encrypted in DB.
Frontend receives only masked key.
Full key is never returned to UI.
```

---

# 17. Telegram setup

Open:

```text
/settings?tab=telegram
```

Flow:

```text
1. Paste Telegram bot token.
2. Click Save token.
3. Click Check bot.
4. Confirm bot username appears.
5. Check APP_URL is correct and HTTPS.
6. Click Set webhook.
7. Header Bot badge should become connected when:
   - token exists
   - bot responds
   - webhook is set
   - bot username exists
```

Webhook URL:

```text
APP_URL + /telegram/webhook
```

Example:

```text
https://your-domain.com/telegram/webhook
```

Bot status badge logic:

```text
Bot: not connected
  no token/settings

Bot: incomplete
  token saved, but check or webhook is incomplete

Bot: connected — @bot_username
  token saved + bot checked + webhook set
```

Security:

```text
Bot token is encrypted in DB.
Webhook secret is encrypted in DB.
Frontend does not receive token or secret.
Webhook checks X-Telegram-Bot-Api-Secret-Token when secret exists.
```

---

# 18. Updating an existing project

For an existing project that already has admin stubs from older versions, do not run a normal install without backups.

Use:

```bash
composer require owlsolutions/custom-admin-kit:v0.5.0

php artisan owl-admin:install --preset=admin --backup --force --migrate --no-smoke

php artisan owl-admin:frontend-setup --preset=admin --backup --force --install-npm --run-build

php artisan optimize:clear

php artisan owl-admin:smoke --preset=admin

npm run build
```

Before this, create a manual archive:

```bash
mkdir -p ~/project-backups

tar \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='bootstrap/cache/*' \
  -czf ~/project-backups/project-before-admin-kit-v050-$(date +%Y%m%d-%H%M%S).tar.gz .
```

Use `--force` only when you intentionally want package stubs to overwrite existing files. Installer creates backups, but local custom changes can still require manual review.

---

# 19. Short command version for clean install

```bash
cd /var/www

composer create-project laravel/laravel project-name
cd project-name

cp .env.example .env
php artisan key:generate

# Configure DB and APP_URL in .env before continuing.

composer config repositories.custom-admin-kit '{"type":"vcs","url":"https://github.com/Owiiiii1/custom-admin-kit.git","no-api":true}'

composer require owlsolutions/custom-admin-kit:v0.5.0

composer require inertiajs/inertia-laravel tightenco/ziggy

php artisan inertia:middleware

php artisan config:clear

php artisan owl-admin:doctor --preset=admin

php artisan owl-admin:install --preset=admin --backup --migrate --no-smoke

php artisan owl-admin:frontend-setup --preset=admin --backup --install-npm --run-build

php artisan owl-admin:make-admin --email=admin@admin.com --password=admin

php artisan owl-admin:smoke --preset=admin

php artisan route:list | grep -E "login|logout|dashboard|customers|orders|services|staff|calendar|settings|telegram|ai-settings|app-settings|profile|statistics|owl-admin"

npm run build
```

---

# 20. Expected final state

A successful clean install should produce:

```text
Package: owlsolutions/custom-admin-kit v0.5.0
Nutgram: installed
Admin preset: installed
Frontend setup: completed
Smoke: passed
Build: passed

/login works
/dashboard protected
CRM pages protected
Settings tabs work
AI settings work
Telegram settings visible
Bot badge visible
AI badge visible
Health route works
```