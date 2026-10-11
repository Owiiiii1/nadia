<?php

namespace Tests\Feature;

use App\Models\TrackingCode;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_snippets_render_in_sort_order_and_stay_off_admin_pages(): void
    {
        TrackingCode::query()->create([
            'name' => 'Later',
            'placement' => 'body_end',
            'code' => '<!-- snippet-later -->',
            'is_active' => true,
            'sort' => 20,
        ]);
        TrackingCode::query()->create([
            'name' => 'Earlier',
            'placement' => 'head',
            'code' => '<!-- snippet-earlier -->',
            'is_active' => true,
            'sort' => 1,
        ]);
        TrackingCode::query()->create([
            'name' => 'Hidden',
            'placement' => 'body_end',
            'code' => '<!-- snippet-hidden -->',
            'is_active' => false,
            'sort' => 0,
        ]);
        app(Settings::class)->set('meta_pixel_id', '1234567890');
        app(Settings::class)->set('ga4_measurement_id', 'G-ABC123');
        app(\App\Support\TrackingCodes::class)->flush();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, '<!-- snippet-later -->'), strpos($html, '<!-- snippet-earlier -->'));
        $this->assertStringContainsString("fbq('init', '1234567890')", $html);
        $this->assertStringContainsString("gtag('config', 'G-ABC123')", $html);
        $this->assertStringContainsString('facebook.com/tr?id=1234567890', $html);
        $this->assertStringNotContainsString('<!-- snippet-hidden -->', $html);
        $this->assertStringContainsString('/privacy', $html);

        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $tracker = $manifest['resources/js/tracker.js']['file'];
        $this->assertStringContainsString($tracker, $html);

        $user = User::factory()->create();
        $private = $this->actingAs($user)->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('snippet-earlier', $private);
        $this->assertStringNotContainsString('fbq(', $private);
        $this->assertStringNotContainsString('gtag(', $private);
        $this->assertStringNotContainsString($tracker, $private);

        $dashboard = $this->actingAs($user)->get('/dashboard')->assertOk()->getContent();
        $this->assertStringNotContainsString('snippet-earlier', $dashboard);
        $this->assertStringNotContainsString('fbq(', $dashboard);
        $this->assertStringNotContainsString('gtag(', $dashboard);
    }

    public function test_guests_cannot_manage_codes_and_pixel_ids_are_validated(): void
    {
        $this->get('/tracking-codes')->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->post('/tracking-codes', [
            'name' => 'Pixel helper',
            'placement' => 'body_end',
            'code' => '<!-- saved-snippet -->',
            'sort' => 3,
        ])->assertRedirect();
        $this->assertDatabaseHas('tracking_codes', ['name' => 'Pixel helper', 'sort' => 3]);

        $code = TrackingCode::query()->first();
        $this->actingAs($user)->patch('/tracking-codes/'.$code->id, [
            'name' => 'Renamed',
        ])->assertRedirect();
        $this->assertSame('Renamed', $code->fresh()->name);

        $this->actingAs($user)->put('/tracking-codes/pixels', [
            'meta_pixel_id' => 'abc',
            'ga4_measurement_id' => 'UA-1',
        ])->assertSessionHasErrors(['meta_pixel_id', 'ga4_measurement_id']);

        $this->actingAs($user)->put('/tracking-codes/pixels', [
            'meta_pixel_id' => '1234567890123',
            'ga4_measurement_id' => 'G-ABCD',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->delete('/tracking-codes/'.$code->id)->assertRedirect();
        $this->assertDatabaseMissing('tracking_codes', ['id' => $code->id]);
    }

    public function test_privacy_page_is_public_and_linked(): void
    {
        $this->get('/')->assertOk()->assertSee('/privacy', false);
        $this->get('/privacy')
            ->assertOk()
            ->assertSee('nadia_vid', false)
            ->assertSee('[contact email]', false)
            ->assertSee('review with brokerage/legal', false);

        $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
        $this->get('/privacy')->assertSee($manifest['resources/js/tracker.js']['file'], false);
    }
}
