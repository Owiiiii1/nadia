<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TelegramBotSetting;
use App\Models\User;
use App\Services\Notifications\LeadNotifier;
use App\Services\Telegram\TelegramUpdates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'created_at'])
            ->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => optional($user->created_at)->toIso8601String(),
            ])
            ->all();

        $allowedTabs = ['general', 'users', 'ai', 'assistant', 'telegram', 'notifications'];
        $tab = (string) $request->query('tab', 'general');
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'general';
        }

        /** @var AiSettingsController $aiSettings */
        $aiSettings = app(AiSettingsController::class);

        return Inertia::render('Settings/Index', [
            'users' => $users,
            'providers' => $aiSettings->providersPayload(),
            'telegram' => $this->telegramPayload(),
            'assistant' => app(AssistantSettingsController::class)->payload(),
            'notifications' => $this->notificationsPayload($request),
            'tab' => $tab,
        ]);
    }

    public function updateLanguage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:en,ru,uk'],
        ]);

        $request->session()->put('locale', $validated['locale']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function telegramPayload(): array
    {
        $empty = [
            'has_bot_token' => false,
            'bot_token_masked' => null,
            'bot_username' => null,
            'webhook_url' => null,
            'has_webhook_secret' => false,
            'is_webhook_set' => false,
            'is_connected' => false,
            'last_checked_at' => null,
            'last_webhook_set_at' => null,
            'last_error' => null,
        ];

        try {
            if (! class_exists(TelegramBotSetting::class) || ! Schema::hasTable('telegram_bot_settings')) {
                return $empty;
            }

            /** @var TelegramBotSetting|null $setting */
            $setting = TelegramBotSetting::query()->first();
            if ($setting === null) {
                return $empty;
            }

            return [
                'has_bot_token' => filled($setting->bot_token),
                'bot_token_masked' => $this->maskSecret($setting->bot_token),
                'bot_username' => $setting->bot_username,
                'webhook_url' => $setting->webhook_url,
                'has_webhook_secret' => filled($setting->webhook_secret),
                'is_webhook_set' => (bool) $setting->is_webhook_set,
                'is_connected' => (bool) $setting->is_connected,
                'last_checked_at' => optional($setting->last_checked_at)?->toIso8601String(),
                'last_webhook_set_at' => optional($setting->last_webhook_set_at)?->toIso8601String(),
                'last_error' => $setting->last_error,
            ];
        } catch (\Throwable) {
            return $empty;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationsPayload(Request $request): array
    {
        $settings = app(\App\Support\Settings::class);
        $notifier = app(LeadNotifier::class);
        $telegram = app(TelegramUpdates::class);
        $bot = null;

        try {
            if (class_exists(TelegramBotSetting::class) && Schema::hasTable('telegram_bot_settings')) {
                $bot = TelegramBotSetting::query()->first();
            }
        } catch (\Throwable) {
            $bot = null;
        }

        $emails = json_decode((string) $settings->get('notify_email_recipients', '[]'), true);

        return [
            'telegram_enabled' => $settings->boolean('notify_telegram_enabled', false),
            'email_enabled' => $settings->boolean('notify_email_enabled', false),
            'bot_connected' => $bot !== null && (bool) $bot->is_connected && filled($bot->bot_username),
            'bot_username' => $bot?->bot_username,
            'recipients' => $telegram->recipients(),
            'emails' => is_array($emails) ? array_values($emails) : [],
            'telegram_error' => $settings->get('notify_last_error_telegram'),
            'email_error' => $settings->get('notify_last_error_email'),
            'mail_configured' => $notifier->mailConfigured(),
            'bind_code' => $request->session()->get('telegram_bind_code'),
            'bind_username' => $request->session()->get('telegram_bind_username'),
            'test_notice' => $request->session()->get('notify_test'),
        ];
    }

    private function maskSecret(?string $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $plain = trim((string) $value);
        $length = strlen($plain);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($plain, 0, 4).'...'.substr($plain, -4);
    }
}
