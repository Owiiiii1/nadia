<?php

namespace Tests\Feature;

use App\Events\LeadCreated;
use App\Models\Lead;
use App\Models\User;
use App\Support\IpHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (getenv('DB_DATABASE') !== 'nadia_test') {
            $this->fail('Refusing to run. DB_DATABASE='.getenv('DB_DATABASE'));
        }

        parent::setUp();
    }

    public function test_json_lead_is_stored_with_attribution_and_event(): void
    {
        Event::fake([LeadCreated::class]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeader('User-Agent', 'NadiaTest/1.0')
            ->postJson('/leads', $this->payload([
                'utm_source' => 'test',
                'utm_campaign' => 'step4',
                'visitor_id' => '11111111-1111-4111-8111-111111111111',
            ]));

        $response->assertCreated();
        $this->assertDatabaseHas('leads', [
            'email' => 'ada@example.com',
            'utm_source' => 'test',
            'utm_campaign' => 'step4',
            'visitor_id' => '11111111-1111-4111-8111-111111111111',
            'ip_hash' => IpHasher::hash('198.51.100.20'),
            'user_agent' => 'NadiaTest/1.0',
            'source' => 'form',
            'status' => 'new',
        ]);
        Event::assertDispatched(LeadCreated::class);
    }

    public function test_missing_email_is_rejected(): void
    {
        $payload = $this->payload();
        unset($payload['email']);

        $this->postJson('/leads', $payload)->assertStatus(422);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_honeypot_is_stored_as_spam_without_event(): void
    {
        Event::fake([LeadCreated::class]);

        $this->postJson('/leads', $this->payload([
            'website' => 'https://spam.example',
        ]))->assertOk();

        $this->assertDatabaseHas('leads', [
            'email' => 'ada@example.com',
            'status' => 'spam',
        ]);
        Event::assertNotDispatched(LeadCreated::class);
    }

    public function test_fast_submit_is_stored_as_spam_without_event(): void
    {
        Event::fake([LeadCreated::class]);

        $this->postJson('/leads', $this->payload([
            'started_at' => now()->getTimestamp(),
        ]))->assertOk();

        $this->assertDatabaseHas('leads', ['status' => 'spam']);
        Event::assertNotDispatched(LeadCreated::class);
    }

    public function test_sixth_request_in_a_minute_is_throttled(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/leads', $this->payload([
                'email' => "ada{$i}@example.com",
            ]))->assertCreated();
        }

        $this->postJson('/leads', $this->payload([
            'email' => 'ada5@example.com',
        ]))->assertStatus(429);
    }

    public function test_guests_cannot_open_the_admin_and_members_can_update_filter_and_export(): void
    {
        $real = Lead::query()->create([
            'name' => 'Real Person',
            'email' => 'real@example.com',
            'goal' => 'buy',
            'source' => 'form',
            'status' => 'new',
        ]);
        Lead::query()->create([
            'name' => 'Spam Person',
            'email' => 'spam@example.com',
            'goal' => 'sell',
            'source' => 'form',
            'status' => 'spam',
        ]);

        $this->get('/leads')->assertRedirect(route('login'));
        $this->get('/leads/'.$real->id)->assertRedirect(route('login'));
        $this->get('/leads/export.csv')->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)->get('/leads?status=new')
            ->assertOk()
            ->assertSee('Real Person')
            ->assertDontSee('Spam Person');

        $this->actingAs($user)->patch('/leads/'.$real->id, [
            'status' => 'in_progress',
            'admin_note' => 'Called back',
        ])->assertRedirect(route('leads.show', $real));

        $this->assertDatabaseHas('leads', [
            'id' => $real->id,
            'status' => 'in_progress',
            'admin_note' => 'Called back',
        ]);

        $csv = $this->actingAs($user)->get('/leads/export.csv?status=in_progress');
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('email', $content);
        $this->assertStringContainsString('Real Person', $content);
        $this->assertStringNotContainsString('Spam Person', $content);
        $this->assertStringNotContainsString('ip_hash', $content);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
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
}
