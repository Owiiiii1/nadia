<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class Settings
{
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->cacheKey($key);
        $cached = Cache::get($cacheKey);

        if (! is_array($cached) || ! array_key_exists('value', $cached)) {
            $cached = [
                'value' => SiteSetting::query()->where('key', $key)->value('value'),
            ];
            Cache::forever($cacheKey, $cached);
        }

        return $cached['value'] === null ? $default : $cached['value'];
    }

    public function set(string $key, ?string $value): void
    {
        SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );

        Cache::forget($this->cacheKey($key));
    }

    public function boolean(string $key, bool $default): bool
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function cacheKey(string $key): string
    {
        return 'site_setting:'.$key;
    }
}
