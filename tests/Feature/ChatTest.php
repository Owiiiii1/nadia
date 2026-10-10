<?php

namespace Tests\Feature;

use App\Events\LeadCreated;
use App\Models\AiProviderSetting;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Lead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Chat\ChatMessenger;
use App\Support\AssistantDefaults;
use App\Support\IpHasher;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_chat_is_unavailable_without_a_provider_or_when_disabled(): void
    {
        Http::fake();

        $this->postJson('/chat/message', ['message' => 'Hello'])
            ->assertStatus(503)
            ->assertJson(['error' => 'unavailable']);

        $this->activate('openai', 'gpt-4o-mini', 'sk-test-openai');
        app(Settings::class)->set('chat_enabled', '0');

        $this->postJson('/chat/message', ['message' => 'Hello'])
            ->assertStatus(503)
            ->assertJson(['error' => 'unavailable']);

        Http::assertNothingSent();
    }

    public function test_first_message_creates_a_session_and_stores_attribution(): void
    {
        $this->activate('openai', 'gpt-4o-mini', 'sk-test-openai');
        Http::fake([
            'api.openai.com/*' => Http::response($this->openaiBody('Hello from Nadia')),
        ]);

        $visitor = '11111111-1111-4111-8111-111111111111';
        $response = $this->withCredentials()
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeader('User-Agent', 'NadiaChat/1.0')
            ->withUnencryptedCookie('nadia_vid', $visitor)
            ->withUnencryptedCookie('nadia_ft', json_encode([
                'utm_source' => 'ig',
                'utm_medium' => 'social',
                'utm_campaign' => 'spring',
                'referrer' => 'https://instagram.com/nadia',
                'landing_path' => '/?utm_source=ig',
            ]))
            ->postJson('/chat/message', ['message' => 'Looking in Lakeview']);

        $response->assertOk()
            ->assertJsonPath('reply', 'Hello from Nadia')
            ->assertJsonPath('lead_captured', false);

        $session = ChatSession::query()->where('uuid', $response->json('session'))->first();
        $this->assertNotNull($session);
        $this->assertSame($visitor, $session->visitor_id);
        $this->assertSame('ig', $session->utm_source);
        $this->assertSame('social', $session->utm_medium);
        $this->assertSame('spring', $session->utm_campaign);
        $this->assertSame('https://instagram.com/nadia', $session->referrer);
        $this->assertSame('/?utm_source=ig', $session->landing_path);
        $this->assertSame(IpHasher::hash('203.0.113.10'), $session->ip_hash);
        $this->assertSame('NadiaChat/1.0', $session->user_agent);
        $this->assertSame(2, $session->messages()->count());
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'role' => 'user',
            'content' => 'Looking in Lakeview',
        ]);
        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'Hello from Nadia',
        ]);
    }

    public function test_lead_block_is_stripped_and_a_second_block_updates_the_same_lead(): void
    {
        Event::fake([LeadCreated::class]);
        $this->activate('openai', 'gpt-4o-mini', 'sk-test-openai');
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push($this->openaiBody('Thanks Ada. '.$this->leadJson([
                    'name' => 'Ada Lovelace',
                    'email' => 'ada@example.com',
                    'phone' => null,
                    'goal' => 'buy',
                    'summary' => 'Wants a condo in Lakeview',
                ])))
                ->push($this->openaiBody('Noted. '.$this->leadJson([
                    'name' => 'Someone Else',
                    'email' => 'other@example.com',
                    'phone' => '+13125550100',
                    'goal' => 'sell',
                    'summary' => 'Different request',
                ]))),
        ]);

        $first = $this->withCredentials()
            ->withUnencryptedCookie('nadia_vid', '22222222-2222-4222-8222-222222222222')
            ->withUnencryptedCookie('nadia_ft', json_encode([
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => 'may',
                'referrer' => 'https://example.com/news',
                'landing_path' => '/?utm_source=newsletter',
            ]))
            ->postJson('/chat/message', ['message' => 'I am Ada, ada@example.com, looking to buy']);

        $first->assertOk()->assertJsonPath('lead_captured', true);
        $this->assertStringNotContainsString('<lead', (string) $first->json('reply'));

        $session = ChatSession::query()->where('uuid', $first->json('session'))->firstOrFail();
        $this->assertSame(1, Lead::query()->count());
        $lead = Lead::query()->firstOrFail();
        $this->assertSame('chat', $lead->source->value);
        $this->assertSame($session->id, $lead->chat_session_id);
        $this->assertSame($lead->id, $session->fresh()->lead_id);
        $this->assertSame('Ada Lovelace', $lead->name);
        $this->assertSame('ada@example.com', $lead->email);
        $this->assertSame('Wants a condo in Lakeview', $lead->message);
        $this->assertSame('newsletter', $lead->utm_source);
        $this->assertSame('22222222-2222-4222-8222-222222222222', $lead->visitor_id);
        $stored = ChatMessage::query()->where('role', 'assistant')->value('content');
        $this->assertSame('Thanks Ada.', $stored);
        $this->assertStringNotContainsString('<lead', (string) $stored);
        Event::assertDispatchedTimes(LeadCreated::class, 1);

        $second = $this->postJson('/chat/message', [
            'session' => $session->uuid,
            'message' => 'My phone is +13125550100',
        ]);
        $second->assertOk()->assertJsonPath('lead_captured', true);
        $this->assertSame(1, Lead::query()->count());
        $lead->refresh();
        $this->assertSame('Ada Lovelace', $lead->name);
        $this->assertSame('ada@example.com', $lead->email);
        $this->assertSame('+13125550100', $lead->phone);
        $this->assertSame('buy', $lead->goal->value);
        Event::assertDispatchedTimes(LeadCreated::class, 1);
    }

    public function test_broken_lead_markup_is_removed_and_does_not_create_a_lead(): void
    {
        $this->activate('openai', 'gpt-4o-mini', 'sk-test-openai');
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push($this->openaiBody('Hello <lead>{"name":"Ada"'))
                ->push($this->openaiBody('Sure <lead>not-json</lead>')),
        ]);

        $this->postJson('/chat/message', ['message' => 'Hi'])
            ->assertOk()
            ->assertJsonPath('reply', 'Hello');
        $this->postJson('/chat/message', ['message' => 'Again'])
            ->assertOk()
            ->assertJsonPath('reply', 'Sure');

        $this->assertSame(0, Lead::query()->count());
        $this->assertFalse(
            ChatMessage::query()->where('role', 'assistant')->where('content', 'like', '%<lead%')->exists()
        );
    }

    public function test_session_message_cap_and_validation_and_throttle(): void
    {
        $this->activate('openai', 'gpt-4o-mini', 'sk-test-openai');
        Http::fake([
            'api.openai.com/*' => Http::response($this->openaiBody('Ok')),
        ]);

        $session = ChatSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'ip_hash' => IpHasher::hash('127.0.0.1'),
        ]);
        for ($i = 0; $i < 30; $i++) {
            ChatMessage::query()->create([
                'chat_session_id' => $session->id,
                'role' => 'user',
                'content' => 'm'.$i,
                'created_at' => now(),
            ]);
        }

        $this->postJson('/chat/message', [
            'session' => $session->uuid,
            'message' => 'one more',
        ])->assertStatus(429)->assertJsonPath('error', ChatMessenger::SESSION_LIMIT);

        $this->postJson('/chat/message', [
            'message' => str_repeat('a', 1001),
        ])->assertStatus(422);

        Http::assertNothingSent();

        Cache::flush();
        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
                ->postJson('/chat/message', ['message' => 'ping '.$i])
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.40'])
            ->postJson('/chat/message', ['message' => 'ping overflow'])
            ->assertStatus(429);
    }

    public function test_provider_failure_returns_the_fallback_and_stores_the_error(): void
    {
        $this->activate('openai', 'gpt-4o-mini', 'sk-test-openai');
        Http::fake([
            'api.openai.com/*' => Http::response('upstream', 500),
        ]);

        $this->postJson('/chat/message', ['message' => 'Hello'])
            ->assertOk()
            ->assertJsonPath('reply', ChatMessenger::FALLBACK);

        $message = ChatMessage::query()->where('role', 'assistant')->first();
        $this->assertNotNull($message);
        $this->assertSame(ChatMessenger::FALLBACK, $message->content);
        $this->assertNotNull($message->error);
        $this->assertStringNotContainsString('sk-test', (string) $message->error);
    }

    public function test_openai_request_shape(): void
    {
        $this->assertProviderRequest(
            'openai',
            'gpt-4o-mini',
            'sk-test-openai',
            'api.openai.com/*',
            function ($request): bool {
                $data = $request->data();

                return $request->url() === 'https://api.openai.com/v1/chat/completions'
                    && $request->hasHeader('Authorization', 'Bearer sk-test-openai')
                    && ($data['model'] ?? null) === 'gpt-4o-mini'
                    && ($data['max_tokens'] ?? null) === 500
                    && ($data['messages'][0]['role'] ?? null) === 'system'
                    && str_starts_with((string) ($data['messages'][0]['content'] ?? ''), 'You are the virtual assistant')
                    && ! str_contains((string) ($data['messages'][0]['content'] ?? ''), '[DRAFT')
                    && ($data['messages'][1]['role'] ?? null) === 'user'
                    && ($data['messages'][1]['content'] ?? null) === 'Hello';
            },
        );
    }

    public function test_anthropic_request_shape(): void
    {
        $this->assertProviderRequest(
            'anthropic',
            'claude-3-5-sonnet',
            'sk-test-anthropic',
            'api.anthropic.com/*',
            function ($request): bool {
                $data = $request->data();

                return $request->url() === 'https://api.anthropic.com/v1/messages'
                    && $request->hasHeader('x-api-key', 'sk-test-anthropic')
                    && $request->hasHeader('anthropic-version', '2023-06-01')
                    && ($data['model'] ?? null) === 'claude-3-5-sonnet'
                    && ($data['max_tokens'] ?? null) === 500
                    && str_starts_with((string) ($data['system'] ?? ''), 'You are the virtual assistant')
                    && ! str_contains((string) ($data['system'] ?? ''), '[DRAFT')
                    && ($data['messages'][0]['role'] ?? null) === 'user'
                    && ($data['messages'][0]['content'] ?? null) === 'Hello';
            },
        );
    }

    public function test_gemini_request_shape(): void
    {
        $this->assertProviderRequest(
            'gemini',
            'gemini-2.0-flash',
            'sk-test-gemini',
            'generativelanguage.googleapis.com/*',
            function ($request): bool {
                $data = $request->data();

                return str_contains($request->url(), 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent')
                    && str_contains($request->url(), 'key=sk-test-gemini')
                    && ($data['generationConfig']['maxOutputTokens'] ?? null) === 500
                    && str_starts_with((string) ($data['systemInstruction']['parts'][0]['text'] ?? ''), 'You are the virtual assistant')
                    && ($data['contents'][0]['role'] ?? null) === 'user'
                    && ($data['contents'][0]['parts'][0]['text'] ?? null) === 'Hello';
            },
        );
    }

    public function test_guest_cannot_open_chats_and_a_member_can_view_and_save_assistant_settings(): void
    {
        $session = ChatSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'messages_count' => 1,
            'last_message_at' => now(),
        ]);
        ChatMessage::query()->create([
            'chat_session_id' => $session->id,
            'role' => 'user',
            'content' => 'Hello from visitor',
            'created_at' => now(),
        ]);

        $this->get('/chats')->assertRedirect(route('login'));
        $this->get('/chats/'.$session->id)->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get('/chats')
            ->assertOk()
            ->assertSee('Hello from visitor');
        $this->actingAs($user)->get('/chats/'.$session->id)
            ->assertOk()
            ->assertSee('Hello from visitor');

        $this->actingAs($user)->put(route('settings.assistant.update'), [
            'chat_enabled' => false,
            'greeting' => 'Hello from settings',
            'system_prompt' => 'Be brief.',
        ])->assertRedirect(route('settings.index', ['tab' => 'assistant']));

        $this->assertSame('0', SiteSetting::query()->where('key', 'chat_enabled')->value('value'));
        $this->assertSame('Hello from settings', SiteSetting::query()->where('key', 'assistant_greeting')->value('value'));
        $this->assertSame('Be brief.', SiteSetting::query()->where('key', 'assistant_system_prompt')->value('value'));

        $this->actingAs($user)->post(route('settings.assistant.reset'))
            ->assertRedirect(route('settings.index', ['tab' => 'assistant']));
        $this->assertStringStartsWith(
            AssistantDefaults::DRAFT_LINE,
            (string) SiteSetting::query()->where('key', 'assistant_system_prompt')->value('value'),
        );
    }

    public function test_landing_hides_the_widget_until_chat_is_available(): void
    {
        $this->get('/')->assertOk()->assertDontSee('nadia-chat-boot', false);

        $this->activate('openai', 'gpt-4o-mini', 'sk-test-openai');

        $this->get('/')->assertOk()->assertSee('nadia-chat-boot', false);
    }

    private function activate(string $provider, string $model, string $key): void
    {
        AiProviderSetting::query()->create([
            'provider' => $provider,
            'label' => $provider,
            'api_key' => $key,
            'is_connected' => true,
            'is_active' => true,
            'active_model' => $model,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function openaiBody(string $text): array
    {
        return [
            'choices' => [[
                'message' => ['content' => $text],
            ]],
            'usage' => [
                'prompt_tokens' => 11,
                'completion_tokens' => 7,
            ],
        ];
    }

    private function leadJson(array $payload): string
    {
        return '<lead>'.json_encode($payload).'</lead>';
    }

    private function assertProviderRequest(string $provider, string $model, string $key, string $url, callable $check): void
    {
        $this->activate($provider, $model, $key);
        $body = match ($provider) {
            'anthropic' => [
                'content' => [['type' => 'text', 'text' => 'Hello back']],
                'usage' => ['input_tokens' => 3, 'output_tokens' => 4],
            ],
            'gemini' => [
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Hello back']]],
                ]],
                'usageMetadata' => ['promptTokenCount' => 5, 'candidatesTokenCount' => 6],
            ],
            default => $this->openaiBody('Hello back'),
        };

        Http::fake([$url => Http::response($body)]);

        $this->postJson('/chat/message', ['message' => 'Hello'])->assertOk();
        Http::assertSent($check);
    }
}
