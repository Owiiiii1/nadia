<?php

namespace App\Console\Commands;

use App\Support\ChatAvailability;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
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
            'analytics.index',
            'tracking.index',
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

        foreach (['customers.', 'orders.', 'services.', 'staff.', 'calendar.'] as $prefix) {
            $found = $names->contains(fn ($name) => str_starts_with((string) $name, $prefix));
            $ok = $this->mark(! $found, "no routes {$prefix}*") && $ok;
        }

        $appSettings = $names
            ->filter(fn ($name) => str_starts_with((string) $name, 'app-settings.'))
            ->values();
        $redirectOnly = $appSettings->count() === 1 && $appSettings->first() === 'app-settings.index';
        $ok = $this->mark($redirectOnly, 'app-settings.index is only the /settings redirect') && $ok;

        foreach (['customers', 'services', 'staff', 'orders', 'order_staff'] as $table) {
            $ok = $this->mark(! Schema::hasTable($table), "table {$table} absent") && $ok;
        }

        foreach (['users', 'ai_provider_settings', 'telegram_bot_settings', 'leads', 'chat_sessions', 'chat_messages', 'site_settings'] as $table) {
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
        $this->line('· '.$chatLine);

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function mark(bool $pass, string $label): bool
    {
        $this->line(($pass ? '✓' : '✗').' '.$label);

        return $pass;
    }
}
