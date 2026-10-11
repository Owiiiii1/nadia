<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\TelegramBotSetting;
use App\Services\Notifications\LeadNotifier;
use App\Services\Telegram\TelegramUpdates;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationSettingsController extends Controller
{
    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'telegram_enabled' => ['required', 'boolean'],
            'email_enabled' => ['required', 'boolean'],
            'emails' => ['array', 'max:5'],
            'emails.*' => ['email', 'max:190'],
        ]);

        $settings->set('notify_telegram_enabled', $data['telegram_enabled'] ? '1' : '0');
        $settings->set('notify_email_enabled', $data['email_enabled'] ? '1' : '0');
        $settings->set('notify_email_recipients', json_encode(array_values($data['emails'] ?? [])));

        return back();
    }

    public function connect(Settings $settings, TelegramUpdates $telegram): RedirectResponse
    {
        $bot = TelegramBotSetting::query()->first();

        if ($bot === null || ! $bot->is_connected || ! filled($bot->bot_username)) {
            return back()->with('notify_test', 'Connect the Telegram bot first.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $telegram->rememberCode($code);

        return back()->with([
            'telegram_bind_code' => $code,
            'telegram_bind_username' => $bot->bot_username,
        ]);
    }

    public function remove(Request $request, Settings $settings, TelegramUpdates $telegram): RedirectResponse
    {
        $data = $request->validate([
            'chat_id' => ['required'],
        ]);
        $left = array_values(array_filter(
            $telegram->recipients(),
            fn (array $recipient): bool => (string) ($recipient['chat_id'] ?? '') !== (string) $data['chat_id'],
        ));
        $settings->set('notify_telegram_recipients', json_encode($left));

        return back();
    }

    public function testTelegram(TelegramUpdates $telegram, LeadNotifier $notifier): RedirectResponse
    {
        $bot = TelegramBotSetting::query()->first();
        $token = (string) ($bot->bot_token ?? '');
        $recipients = $telegram->recipients();

        if ($token === '' || $recipients === []) {
            return back()->with('notify_test', 'Telegram has no connected recipients.');
        }

        $lead = new Lead([
            'name' => 'Test',
            'goal' => 'consult',
            'source' => 'form',
            'message' => 'Test notification from the Nadia Glavin website.',
        ]);
        $lead->id = 0;
        $notifier->sendTelegram($lead);

        return back()->with('notify_test', 'Test sent to Telegram.');
    }

    public function testEmail(LeadNotifier $notifier): RedirectResponse
    {
        if (! $notifier->mailConfigured()) {
            return back()->with('notify_test', 'Email transport is not configured on the server');
        }

        $emails = $notifier->emails();

        if ($emails === []) {
            return back()->with('notify_test', 'Add an email recipient first.');
        }

        $lead = new Lead([
            'name' => 'Test',
            'goal' => 'consult',
            'source' => 'form',
            'message' => 'Test notification from the Nadia Glavin website.',
        ]);
        $lead->id = 0;
        Mail::to($emails)->send(new NewLeadMail($lead));

        return back()->with('notify_test', 'Test sent by email.');
    }
}
