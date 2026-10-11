<?php

namespace App\Services\Notifications;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Mail\NewLeadMail;
use App\Models\Lead;
use App\Models\TelegramBotSetting;
use App\Services\Telegram\TelegramUpdates;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LeadNotifier
{
    public function __construct(
        private readonly Settings $settings,
        private readonly TelegramUpdates $telegram,
    ) {}

    public function send(Lead $lead): void
    {
        if ($lead->status === LeadStatus::Spam) {
            return;
        }

        if ($this->settings->boolean('notify_telegram_enabled', false)) {
            $this->sendTelegram($lead);
        }

        if ($this->settings->boolean('notify_email_enabled', false)) {
            $this->sendEmail($lead);
        }
    }

    public function sendTelegram(Lead $lead): void
    {
        try {
            $setting = TelegramBotSetting::query()->first();
            $token = (string) ($setting->bot_token ?? '');
            $recipients = $this->telegram->recipients();

            if ($token === '' || $recipients === []) {
                throw new \RuntimeException('telegram is not ready');
            }

            $text = $this->telegramText($lead);
            $failed = false;

            foreach ($recipients as $recipient) {
                try {
                    $response = Http::timeout(10)->post($this->telegram->endpoint($token), [
                        'chat_id' => $recipient['chat_id'],
                        'text' => $text,
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true,
                    ]);
                    $failed = $failed || ! $response->successful();
                } catch (Throwable) {
                    $failed = true;
                }
            }

            if ($failed) {
                throw new \RuntimeException('telegram request failed');
            }

            $this->settings->set('notify_last_error_telegram', null);
        } catch (Throwable $exception) {
            $this->fail('telegram', $lead->id, $exception);
        }
    }

    public function sendEmail(Lead $lead): void
    {
        try {
            if (! $this->mailConfigured()) {
                throw new \RuntimeException('email transport is not configured');
            }

            $emails = $this->emails();

            if ($emails === []) {
                throw new \RuntimeException('email recipients are empty');
            }

            Mail::to($emails)->send(new NewLeadMail($lead));
            $this->settings->set('notify_last_error_email', null);
        } catch (Throwable $exception) {
            $this->fail('email', $lead->id, $exception);
        }
    }

    public function mailConfigured(): bool
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array', ''], true)) {
            return false;
        }

        return filled(config('mail.mailers.smtp.host'));
    }

    /**
     * @return list<string>
     */
    public function emails(): array
    {
        $decoded = json_decode((string) $this->settings->get('notify_email_recipients', '[]'), true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($email) => is_string($email) && $email !== ''));
    }

    public function telegramText(Lead $lead): string
    {
        $source = $lead->source === LeadSource::Chat ? 'Chat' : 'Form';
        $goal = $lead->goal instanceof \BackedEnum ? $lead->goal->value : (string) $lead->goal;
        $lines = [
            '🏠 New lead — '.$source,
            $this->escape((string) $lead->name),
        ];

        if (filled($lead->email)) {
            $lines[] = $this->escape((string) $lead->email);
        }

        if (filled($lead->phone)) {
            $lines[] = $this->escape((string) $lead->phone);
        }

        $lines[] = 'Goal: '.$this->escape($goal);

        $message = trim((string) $lead->message);

        if ($message !== '') {
            $lines[] = $this->escape(mb_substr($message, 0, 300));
        }

        $utmSource = trim((string) $lead->utm_source);
        $utmCampaign = trim((string) $lead->utm_campaign);

        if ($utmSource !== '' || $utmCampaign !== '') {
            $lines[] = 'UTM: '.$this->escape($utmSource).'/'.$this->escape($utmCampaign);
        }

        $url = rtrim((string) config('app.url'), '/').'/leads/'.$lead->id;
        $lines[] = '<a href=\''.$this->escape($url).'\'>Open in admin</a>';

        return implode("\n", $lines);
    }

    private function fail(string $channel, int $leadId, Throwable $exception): void
    {
        Log::warning('Lead notification failed', [
            'lead_id' => $leadId,
            'channel' => $channel,
        ]);
        $this->settings->set(
            'notify_last_error_'.$channel,
            now()->toIso8601String().' lead '.$leadId.' failed',
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
