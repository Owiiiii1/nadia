<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Lead;
use App\Models\PageView;
use App\Models\SiteEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_chicago_days_and_totals_match_the_fixtures(): void
    {
        $a = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $b = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $c = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

        $this->storeView($a, $this->chicago('2026-10-10 23:30:00'), '/');
        $this->storeView($a, $this->chicago('2026-10-10 23:40:00'), '/');
        $this->storeView($b, $this->chicago('2026-10-10 22:00:00'), '/', 'www.google.com', 'google');
        $this->storeView($c, $this->chicago('2026-10-11 00:30:00'), '/contact');
        $this->storeView($c, $this->chicago('2026-10-09 23:59:00'), '/outside');

        Lead::query()->create([
            'name' => 'In Range',
            'email' => 'in@example.com',
            'goal' => 'buy',
            'source' => 'form',
            'status' => 'new',
            'utm_source' => 'google',
            'referrer' => 'https://www.google.com/search',
            'created_at' => $this->chicago('2026-10-11 00:15:00'),
        ]);
        Lead::query()->create([
            'name' => 'Spam',
            'email' => 'spam@example.com',
            'goal' => 'buy',
            'source' => 'form',
            'status' => 'spam',
            'created_at' => $this->chicago('2026-10-11 00:20:00'),
        ]);
        SiteEvent::query()->create([
            'visitor_id' => $a,
            'session_id' => (string) Str::uuid(),
            'type' => 'chat_open',
            'created_at' => $this->chicago('2026-10-10 23:50:00'),
        ]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/analytics?period=custom&from=2026-10-10&to=2026-10-11');
        $response->assertOk();
        $props = $response->original->getData()['page']['props'];

        $cards = collect($props['cards'])->keyBy('key');
        $this->assertSame(3, $cards['visitors']['value']);
        $this->assertSame(4, $cards['sessions']['value']);
        $this->assertSame(4, $cards['pageviews']['value']);
        $this->assertSame(1, $cards['leads']['value']);
        $this->assertEquals(33.3, $cards['conversion']['value']);
        $this->assertSame(1, $cards['chat_opens']['value']);

        $series = collect($props['series'])->keyBy('date');
        $this->assertSame(2, $series['2026-10-10']['visitors']);
        $this->assertSame(3, $series['2026-10-10']['pageviews']);
        $this->assertSame(1, $series['2026-10-11']['visitors']);
        $this->assertSame(1, $series['2026-10-11']['pageviews']);

        $google = collect($props['referrers'])->firstWhere('label', 'www.google.com');
        $this->assertSame(1, $google['visits']);
        $this->assertSame(1, $google['leads']);
        $this->assertSame(1, $props['utm']['source'][0]['leads']);
    }

    public function test_guests_cannot_open_analytics(): void
    {
        $this->get('/analytics')->assertRedirect(route('login'));
    }

    public function test_dashboard_uses_the_last_thirty_chicago_days(): void
    {
        $visitor = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';
        $this->storeView($visitor, now(), '/');
        Lead::query()->create([
            'name' => 'Latest',
            'email' => 'latest@example.com',
            'goal' => 'consult',
            'source' => 'chat',
            'status' => 'new',
        ]);
        ChatSession::query()->create([
            'uuid' => (string) Str::uuid(),
            'messages_count' => 1,
            'last_message_at' => now(),
        ]);

        $user = User::factory()->create();
        $props = $this->actingAs($user)->get('/dashboard')->assertOk()->original->getData()['page']['props'];

        $this->assertSame(1, $props['visitors']);
        $this->assertSame(1, $props['leads']);
        $this->assertEquals(100.0, $props['conversion']);
        $this->assertSame(1, $props['chats']);
        $this->assertSame('Latest', $props['recent'][0]['name']);
        $this->assertCount(30, $props['series']);
    }

    public function test_prune_deletes_only_rows_older_than_twelve_months(): void
    {
        $this->storeView('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', now()->subMonths(13), '/old');
        $this->storeView('ffffffff-ffff-4fff-8fff-ffffffffffff', now()->subDays(2), '/new');
        SiteEvent::query()->create([
            'visitor_id' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
            'session_id' => (string) Str::uuid(),
            'type' => 'chat_open',
            'created_at' => now()->subMonths(13),
        ]);
        SiteEvent::query()->create([
            'visitor_id' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
            'session_id' => (string) Str::uuid(),
            'type' => 'chat_lead',
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('analytics:prune')->assertSuccessful();

        $this->assertSame(['/new'], PageView::query()->pluck('path')->all());
        $this->assertSame(['chat_lead'], SiteEvent::query()->pluck('type')->all());
    }

    private function storeView(string $visitor, Carbon $at, string $path, ?string $host = null, ?string $utm = null): void
    {
        PageView::query()->create([
            'visitor_id' => $visitor,
            'session_id' => (string) Str::uuid(),
            'path' => $path,
            'referrer_host' => $host,
            'utm_source' => $utm,
            'device' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'created_at' => $at,
        ]);
    }

    private function chicago(string $local): Carbon
    {
        return Carbon::parse($local, 'America/Chicago')->utc();
    }
}
