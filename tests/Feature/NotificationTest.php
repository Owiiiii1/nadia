<?php

namespace Tests\Feature;

use App\Models\TelegramBotSetting;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_lead_notifies_enabled_channels_and_escapes_telegram_text(): void
    {
        $this->ready();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Mail::fake();

        $this->postJson('/leads', $this->lead([
            'name' => 'A<b> & "x"',
            'message' => 'Hello <script>',
            'utm_source' => 'google',
            'utm_campaign' => 'spring',
        ]))->assertCreated();

        Http::assertSent(function ($request): bool {
            $text = (string) ($request['text'] ?? '');

            return str_contains($request->url(), 'api.telegram.org')
                && ($request['parse_mode'] ?? null) === 'HTML'
                && str_contains($text, '🏠 New lead — Form')
                && str_contains($text, 'A&lt;b&gt; &amp; &quot;x&quot;')
                && str_contains($text, 'Hello &lt;script&gt;')
                && str_contains($text, 'UTM: google/spring')
                && str_contains($text, '>Open in admin</a>');
        });
        Mail::assertSent(\App\Mail\NewLeadMail::class, function ($mail): bool {
            return $mail->hasTo('owner@example.com')
                && $mail->hasReplyTo('ada@example.com')
                && $mail->envelope()->subject === 'New lead: A<b> & "x" (buy)';
        });
    }

    public function test_a_disabled_channel_is_skipped(): void
    {
        $this->ready();
        app(Settings::class)->set('notify_telegram_enabled', '0');
        Http::fake();
        Mail::fake();

        $this->postJson('/leads', $this->lead())->assertCreated();

        Http::assertNothingSent();
        Mail::assertSent(\App\Mail\NewLeadMail::class);
    }

    public function test_a_telegram_error_does_not_block_email(): void
    {
        $this->ready();
        Http::fake(fn () => Http::response(['ok' => false], 500));
        Mail::fake();

        $this->postJson('/leads', $this->lead(['email' => 'second@example.com']))->assertCreated();

        Mail::assertSent(\App\Mail\NewLeadMail::class);
        $error = (string) app(Settings::class)->get('notify_last_error_telegram');
        $this->assertStringContainsString('failed', $error);
        $this->assertStringNotContainsString('second@example.com', $error);
    }

    public function test_spam_is_not_notified(): void
    {
        $this->ready();
        Http::fake();
        Mail::fake();
        $this->postJson('/leads', $this->lead(['website' => 'https://spam.test']))->assertOk();
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_start_code_saves_a_chat_once(): void
    {
        $bot = $this->bot();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        app(\App\Services\Telegram\TelegramUpdates::class)->rememberCode('123456');

        $this->postJson('/telegram/webhook', $this->update('/start 123456'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'secret-token',
        ])->assertOk();

        $saved = app(\App\Services\Telegram\TelegramUpdates::class)->recipients();
        $this->assertCount(1, $saved);
        $this->assertSame(42, $saved[0]['chat_id']);
        Http::assertSent(fn ($request) => ($request['text'] ?? null) === "Done! You'll receive new leads here.");

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->postJson('/telegram/webhook', $this->update('/start 123456'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'secret-token',
        ])->assertOk();
        $this->assertCount(1, app(\App\Services\Telegram\TelegramUpdates::class)->recipients());

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->postJson('/telegram/webhook', $this->update('/start 000000'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'secret-token',
        ])->assertOk();
        $this->assertCount(1, app(\App\Services\Telegram\TelegramUpdates::class)->recipients());
        Http::assertSent(fn ($request) => ($request['text'] ?? null) === 'Code is invalid or expired.');

        $this->assertNotEmpty($bot->bot_token);
    }

    private function ready(): void
    {
        $this->bot();
        $settings = app(Settings::class);
        $settings->set('notify_telegram_enabled', '1');
        $settings->set('notify_email_enabled', '1');
        $settings->set('notify_telegram_recipients', json_encode([
            ['chat_id' => 42, 'name' => 'Ada', 'username' => 'ada'],
        ]));
        $settings->set('notify_email_recipients', json_encode(['owner@example.com']));
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
        ]);
    }

    private function bot(): TelegramBotSetting
    {
        return TelegramBotSetting::query()->create([
            'bot_token' => '123456:ABC-DEF',
            'bot_username' => 'nadia_bot',
            'webhook_secret' => 'secret-token',
            'is_connected' => true,
            'is_webhook_set' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function lead(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+1 (312) 555-0100',
            'goal' => 'buy',
            'message' => 'Looking in Lakeview',
            'started_at' => now()->subSeconds(10)->getTimestamp(),
            'website' => '',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function update(string $text): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'from' => [
                    'id' => 42,
                    'is_bot' => false,
                    'first_name' => 'Ada',
                    'username' => 'ada',
                ],
                'chat' => [
                    'id' => 42,
                    'type' => 'private',
                    'first_name' => 'Ada',
                ],
                'date' => time(),
                'text' => $text,
            ],
        ];
    }
}
