<?php

namespace App\Support;

use App\Models\TrackingCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TrackingCodes
{
    public const CACHE_KEY = 'tracking.bundle';

    public function __construct(private readonly Settings $settings) {}

    public function render(string $placement): string
    {
        if (auth()->check() || ! Schema::hasTable('tracking_codes')) {
            return '';
        }

        $bundle = $this->bundle();
        $html = '';

        if ($placement === 'head') {
            $html .= $this->metaHead($bundle['meta']);
            $html .= $this->ga4($bundle['ga4']);
        }

        if ($placement === 'body_start') {
            $html .= $this->metaNoscript($bundle['meta']);
        }

        foreach ($bundle['snippets'][$placement] ?? [] as $code) {
            $html .= $code;
        }

        return $html;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array{meta: ?string, ga4: ?string, snippets: array<string, list<string>>}
     */
    private function bundle(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        $snippets = [
            'head' => [],
            'body_start' => [],
            'body_end' => [],
        ];

        TrackingCode::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get(['placement', 'code'])
            ->each(function (TrackingCode $row) use (&$snippets): void {
                if (array_key_exists($row->placement, $snippets)) {
                    $snippets[$row->placement][] = (string) $row->code;
                }
            });

        $bundle = [
            'meta' => $this->pixelId(),
            'ga4' => $this->ga4Id(),
            'snippets' => $snippets,
        ];
        Cache::forever(self::CACHE_KEY, $bundle);

        return $bundle;
    }

    private function pixelId(): ?string
    {
        $id = trim((string) $this->settings->get('meta_pixel_id', ''));

        return preg_match('/^\d{10,20}$/', $id) === 1 ? $id : null;
    }

    private function ga4Id(): ?string
    {
        $id = trim((string) $this->settings->get('ga4_measurement_id', ''));

        return preg_match('/^G-[A-Z0-9]{4,12}$/', $id) === 1 ? $id : null;
    }

    private function metaHead(?string $id): string
    {
        if ($id === null) {
            return '';
        }

        return "<script>\n!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');\nfbq('init', '{$id}');\nfbq('track', 'PageView');\n</script>\n";
    }

    private function metaNoscript(?string $id): string
    {
        if ($id === null) {
            return '';
        }

        return '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id='.$id.'&ev=PageView&noscript=1" alt=""></noscript>'."\n";
    }

    private function ga4(?string $id): string
    {
        if ($id === null) {
            return '';
        }

        return '<script async src="https://www.googletagmanager.com/gtag/js?id='.$id.'"></script>'."\n"
            ."<script>\nwindow.dataLayer = window.dataLayer || [];\nfunction gtag(){dataLayer.push(arguments);}\ngtag('js', new Date());\ngtag('config', '{$id}');\n</script>\n";
    }
}
