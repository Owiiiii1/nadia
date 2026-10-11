<?php

namespace App\Console\Commands;

use App\Support\ChatAvailability;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class NadiaSmokeCommand extends Command
{
    protected $signature = 'nadia:smoke';

    protected $description = 'Check the simplified Nadia admin shell';

    public function handle(): int
    {
        $ok = true;

        foreach ([
            'login',
            'logout',
            'dashboard',
            'landing',
            'leads.store',
            'leads.index',
            'leads.show',
            'leads.update',
            'leads.destroy',
            'leads.export',
            'chats.index',
            'chats.show',
            'chats.destroy',
            'chat.message',
            'track',
            'analytics.index',
            'tracking.index',
            'tracking.store',
            'tracking.update',
            'tracking.destroy',
            'tracking.pixels',
            'privacy',
            'settings.notifications.update',
            'settings.index',
            'ai-settings.index',
            'statistics.logs',
            'profile.edit',
            'owl-admin.health',
            'telegram.webhook',
        ] as $name) {
            $ok = $this->mark(Route::has($name), "route {$name}") && $ok;
        }

        $names = collect(Route::getRoutes())->map(fn ($route) => $route->getName())->filter();

        foreach (['customers.', 'orders.', 'services.', 'staff.', 'calendar.', 'storage.local'] as $prefix) {
            $found = $names->contains(fn ($name) => str_starts_with((string) $name, $prefix));
            $ok = $this->mark(! $found, "no routes {$prefix}*") && $ok;
        }

        foreach (['register', 'password.request', 'password.email', 'password.reset'] as $closed) {
            $ok = $this->mark(! Route::has($closed), "route {$closed} absent") && $ok;
        }

        $appSettings = $names
            ->filter(fn ($name) => str_starts_with((string) $name, 'app-settings.'))
            ->values();
        $redirectOnly = $appSettings->count() === 1 && $appSettings->first() === 'app-settings.index';
        $ok = $this->mark($redirectOnly, 'app-settings.index is only the /settings redirect') && $ok;

        foreach (['customers', 'services', 'staff', 'orders', 'order_staff'] as $table) {
            $ok = $this->mark(! Schema::hasTable($table), "table {$table} absent") && $ok;
        }

        foreach (['users', 'ai_provider_settings', 'telegram_bot_settings', 'leads', 'chat_sessions', 'chat_messages', 'site_settings', 'page_views', 'site_events', 'tracking_codes'] as $table) {
            $ok = $this->mark(Schema::hasTable($table), "table {$table} present") && $ok;
        }

        $landing = collect(Route::getRoutes())->first(fn ($route) => $route->getName() === 'landing');
        $ok = $this->mark($landing !== null && $landing->uri() === '/', 'landing route is /') && $ok;

        $manifestPath = public_path('build/manifest.json');
        $manifest = is_file($manifestPath)
            ? (json_decode((string) file_get_contents($manifestPath), true) ?: [])
            : [];
        $ok = $this->mark($manifest !== [], 'vite manifest exists') && $ok;

        foreach ([
            'resources/js/Pages/Leads/Index.jsx',
            'resources/js/Pages/Chats/Index.jsx',
            'resources/js/Pages/Analytics/Index.jsx',
            'resources/js/Pages/Tracking/Index.jsx',
            'resources/js/Pages/Settings/AssistantPanel.jsx',
            'resources/js/landing.js',
            'resources/js/tracker.js',
            'resources/css/landing.css',
            'resources/js/chat-widget.js',
            'resources/css/chat-widget.css',
        ] as $page) {
            $ok = $this->mark(isset($manifest[$page]), "manifest {$page}") && $ok;
        }

        $kernel = app(Kernel::class);
        $request = Request::create('/', 'GET');
        $response = $kernel->handle($request);
        $body = (string) $response->getContent();
        $landingOk = $response->getStatusCode() === 200
            && str_contains($body, 'Chicago real estate, handled strategically.');
        $kernel->terminate($request, $response);
        $ok = $this->mark($landingOk, 'GET / returns the landing headline') && $ok;

        try {
            $chatLine = app(ChatAvailability::class)->statusLine();
        } catch (\Throwable) {
            $chatLine = 'chat available: no (none)';
        }
        $events = collect(app(Schedule::class)->events());
        $prune = $events->contains(fn ($event) => str_contains((string) $event->command, 'analytics:prune'));
        $ok = $this->mark($prune, 'schedule contains analytics:prune') && $ok;
        $logs = $events->contains(fn ($event) => str_contains((string) $event->command, 'logs:prune'));
        $ok = $this->mark($logs, 'schedule contains logs:prune') && $ok;

        $this->line('· '.$chatLine);
        $this->line('· '.$this->notificationLine());

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function notificationLine(): string
    {
        try {
            $settings = app(Settings::class);
            $telegram = $settings->boolean('notify_telegram_enabled', false) ? 'on' : 'off';
            $email = $settings->boolean('notify_email_enabled', false) ? 'on' : 'off';
            $telegramCount = count(json_decode((string) $settings->get('notify_telegram_recipients', '[]'), true) ?: []);
            $emailCount = count(json_decode((string) $settings->get('notify_email_recipients', '[]'), true) ?: []);
            $mailer = (string) config('mail.default');

            return "notifications: telegram {$telegram} ({$telegramCount} recipients), email {$email} ({$emailCount} recipients), mail transport: {$mailer}";
        } catch (\Throwable) {
            return 'notifications: telegram off (0 recipients), email off (0 recipients), mail transport: '.config('mail.default');
        }
    }

    private function mark(bool $pass, string $label): bool
    {
        $this->line(($pass ? '✓' : '✗').' '.$label);

        return $pass;
    }
}
