<?php

namespace App\Services\Telegram;

use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Fake;
use Throwable;

class TelegramUpdates
{
    public function __construct(private readonly Settings $settings) {}

    /**
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update, string $token): void
    {
        $bot = new Nutgram($token);
        $bot->setRunningMode(new Fake($update));
        $bot->onCommand('start {code}', function (Nutgram $bot, string $code) use ($token): void {
            $this->bind($token, $bot->chatId(), $code, $bot->user()?->first_name, $bot->user()?->username);
        });
        $bot->onMessage(function (Nutgram $bot) use ($token): void {
            $text = trim((string) ($bot->message()?->text ?? ''));
            $reply = str_starts_with($text, '/start')
                ? 'Code is invalid or expired.'
                : 'This bot only sends notifications.';
            $this->reply($token, $bot->chatId(), $reply);
        });
        $bot->run();
    }

    public function bind(string $token, mixed $chatId, string $code, ?string $firstName, ?string $username): void
    {
        $code = trim($code);

        if ($chatId === null || Cache::get($this->cacheKey($code)) !== true) {
            $this->reply($token, $chatId, 'Code is invalid or expired.');

            return;
        }

        $recipients = $this->recipients();
        $known = false;

        foreach ($recipients as $recipient) {
            if ((string) ($recipient['chat_id'] ?? '') === (string) $chatId) {
                $known = true;
                break;
            }
        }

        if (! $known) {
            $name = trim((string) ($firstName ?: $username ?: $chatId));
            $recipients[] = [
                'chat_id' => $chatId,
                'name' => $name,
                'username' => $username ? ltrim($username, '@') : null,
            ];
            $this->settings->set('notify_telegram_recipients', json_encode($recipients));
        }

        $this->reply($token, $chatId, "Done! You'll receive new leads here.");
    }

    /**
     * @return list<array{chat_id: int|string, name: string, username: ?string}>
     */
    public function recipients(): array
    {
        $decoded = json_decode((string) $this->settings->get('notify_telegram_recipients', '[]'), true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    public function rememberCode(string $code): void
    {
        Cache::put($this->cacheKey($code), true, now()->addMinutes(15));
    }

    public function reply(string $token, mixed $chatId, string $text): void
    {
        if ($chatId === null || $token === '') {
            return;
        }

        try {
            Http::timeout(10)->post($this->endpoint($token), [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (Throwable) {
            // A reply failure must not fail the webhook.
        }
    }

    public function endpoint(string $token): string
    {
        return 'https://api.telegram.org/bot'.$token.'/sendMessage';
    }

    private function cacheKey(string $code): string
    {
        return 'telegram-bind:'.$code;
    }
}
