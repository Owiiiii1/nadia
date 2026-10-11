<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Models\SiteEvent;
use App\Support\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TrackController extends Controller
{
    public function store(Request $request): Response
    {
        $raw = $request->getContent();

        if (strlen($raw) > (int) config('nadia.tracking.max_bytes', 4096)) {
            return response()->noContent();
        }

        if (! $this->allowedOrigin($request) || $this->ignoredVisitor($request)) {
            return response()->noContent();
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            return response()->noContent();
        }

        $type = $payload['type'] ?? null;
        $visitorId = $this->uuid($payload['visitor_id'] ?? null);
        $sessionId = $this->uuid($payload['session_id'] ?? null);

        if (($payload['visitor_id'] ?? null) !== null && $visitorId === null) {
            return response()->noContent();
        }

        if (($payload['session_id'] ?? null) !== null && $sessionId === null) {
            return response()->noContent();
        }

        if ($type === 'pageview') {
            $this->storePageView($request, $payload, $visitorId, $sessionId);
        } elseif ($type === 'event') {
            $this->storeEvent($payload, $visitorId, $sessionId);
        }

        return response()->noContent();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storePageView(Request $request, array $payload, ?string $visitorId, ?string $sessionId): void
    {
        $path = $this->clip($payload['path'] ?? null, 500);

        if ($path === null) {
            return;
        }

        $referrer = $this->clip($payload['referrer'] ?? null, 500);
        $host = $this->referrerHost($referrer);
        $agent = UserAgent::parse($request->userAgent());

        PageView::query()->create([
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'path' => $path,
            'referrer' => $referrer,
            'referrer_host' => $host,
            'utm_source' => $this->clip($payload['utm_source'] ?? null, 190),
            'utm_medium' => $this->clip($payload['utm_medium'] ?? null, 190),
            'utm_campaign' => $this->clip($payload['utm_campaign'] ?? null, 190),
            'utm_term' => $this->clip($payload['utm_term'] ?? null, 190),
            'utm_content' => $this->clip($payload['utm_content'] ?? null, 190),
            'device' => $agent['device'],
            'browser' => $agent['browser'],
            'os' => $agent['os'],
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeEvent(array $payload, ?string $visitorId, ?string $sessionId): void
    {
        $event = $payload['event'] ?? null;

        if (! in_array($event, ['form_submit', 'chat_open', 'chat_lead'], true)) {
            return;
        }

        $meta = null;

        if ($event === 'form_submit') {
            $goal = $this->clip(is_array($payload['meta'] ?? null) ? ($payload['meta']['goal'] ?? null) : null, 20);
            $meta = ['goal' => $goal];
        }

        SiteEvent::query()->create([
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'type' => $event,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    private function allowedOrigin(Request $request): bool
    {
        $expected = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($expected === '') {
            return false;
        }

        $origin = $request->headers->get('Origin');

        if (is_string($origin) && $origin !== '') {
            return $this->hostOf($origin) === $expected;
        }

        $referer = $request->headers->get('Referer');

        if (is_string($referer) && $referer !== '') {
            return $this->hostOf($referer) === $expected;
        }

        return false;
    }

    private function hostOf(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : '';
    }

    private function ignoredVisitor(Request $request): bool
    {
        if ($request->user() !== null) {
            return true;
        }

        $ua = trim((string) $request->userAgent());

        if ($ua === '') {
            return true;
        }

        $pattern = (string) config('nadia.tracking.bot_pattern', 'bot|crawl|spider');

        return preg_match('/'.$pattern.'/i', $ua) === 1;
    }

    private function referrerHost(?string $referrer): ?string
    {
        if ($referrer === null) {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($appHost !== '' && $host === $appHost) {
            return null;
        }

        return $this->clip($host, 190);
    }

    private function uuid(mixed $value): ?string
    {
        if (! is_string($value) || ! Str::isUuid($value)) {
            return null;
        }

        return $value;
    }

    private function clip(mixed $value, int $limit): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $limit);
    }
}
