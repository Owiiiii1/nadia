<?php

namespace Tests\Feature;

use App\Models\PageView;
use App\Models\SiteEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackTest extends TestCase
{
    use RefreshDatabase;

    private string $browser = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    public function test_a_pageview_is_stored_and_rejections_stay_empty(): void
    {
        $origin = config('app.url');
        $visitor = '11111111-1111-4111-8111-111111111111';
        $session = '22222222-2222-4222-8222-222222222222';

        $this->call('POST', '/t', [], [], [], $this->server($origin, $this->browser), json_encode([
            'type' => 'pageview',
            'visitor_id' => $visitor,
            'session_id' => $session,
            'path' => '/buy',
            'referrer' => 'https://www.google.com/search?q=nadia',
            'utm_source' => 'google',
        ]))->assertNoContent();

        $view = PageView::query()->first();
        $this->assertNotNull($view);
        $this->assertSame($visitor, $view->visitor_id);
        $this->assertSame('/buy', $view->path);
        $this->assertSame('www.google.com', $view->referrer_host);
        $this->assertSame('google', $view->utm_source);
        $this->assertSame('desktop', $view->device);
        $this->assertSame('Chrome', $view->browser);
        $this->assertSame('Windows', $view->os);

        $this->call('POST', '/t', [], [], [], $this->server($origin, 'curl/8.5.0'), json_encode([
            'type' => 'pageview',
            'visitor_id' => $visitor,
            'session_id' => $session,
            'path' => '/bot',
        ]))->assertNoContent();

        $this->call('POST', '/t', [], [], [], $this->server('https://evil.example', $this->browser), json_encode([
            'type' => 'pageview',
            'visitor_id' => $visitor,
            'session_id' => $session,
            'path' => '/foreign',
        ]))->assertNoContent();

        $this->call('POST', '/t', [], [], [], $this->server($origin, $this->browser), json_encode([
            'type' => 'event',
            'event' => 'purchase',
            'visitor_id' => $visitor,
            'session_id' => $session,
        ]))->assertNoContent();

        $this->call('POST', '/t', [], [], [], $this->server($origin, $this->browser), str_repeat('a', 4097))
            ->assertNoContent();

        $this->assertSame(1, PageView::query()->count());
        $this->assertSame(0, SiteEvent::query()->count());
    }

    public function test_origin_is_taken_from_the_app_url_host(): void
    {
        config(['app.url' => 'https://nadiaglavin.com']);

        $this->call('POST', '/t', [], [], [], $this->server('https://nadiaglavin.com', $this->browser), json_encode([
            'type' => 'pageview',
            'visitor_id' => '11111111-1111-4111-8111-111111111111',
            'session_id' => '22222222-2222-4222-8222-222222222222',
            'path' => '/from-app-url',
        ]))->assertNoContent();

        $this->call('POST', '/t', [], [], [], $this->server('https://nadia.owlsolutions.net', $this->browser), json_encode([
            'type' => 'pageview',
            'visitor_id' => '11111111-1111-4111-8111-111111111111',
            'session_id' => '22222222-2222-4222-8222-222222222222',
            'path' => '/old-host',
        ]))->assertNoContent();

        $this->assertSame(1, PageView::query()->count());
        $this->assertSame('/from-app-url', PageView::query()->value('path'));
    }

    public function test_a_signed_in_admin_is_not_counted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->call('POST', '/t', [], [], [], $this->server(config('app.url'), $this->browser), json_encode([
            'type' => 'pageview',
            'visitor_id' => '11111111-1111-4111-8111-111111111111',
            'session_id' => '22222222-2222-4222-8222-222222222222',
            'path' => '/admin-visit',
        ]))->assertNoContent();

        $this->assertSame(0, PageView::query()->count());
    }

    /**
     * @return array<string, string>
     */
    private function server(string $origin, string $ua): array
    {
        return [
            'HTTP_ORIGIN' => $origin,
            'HTTP_USER_AGENT' => $ua,
            'CONTENT_TYPE' => 'text/plain',
        ];
    }
}
