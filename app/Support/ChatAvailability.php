<?php

namespace App\Support;

use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Schema;

class ChatAvailability
{
    public function __construct(private Settings $settings) {}

    public function enabled(): bool
    {
        if (! Schema::hasTable('site_settings')) {
            return false;
        }

        return $this->settings->boolean('chat_enabled', true);
    }

    public function setting(): ?AiProviderSetting
    {
        if (! Schema::hasTable('ai_provider_settings')) {
            return null;
        }

        return AiProviderSetting::query()
            ->where('is_active', true)
            ->where('is_connected', true)
            ->whereNotNull('active_model')
            ->where('active_model', '!=', '')
            ->orderByDesc('id')
            ->first();
    }

    public function available(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        $setting = $this->setting();

        return $setting !== null && is_string($setting->api_key) && $setting->api_key !== '';
    }

    public function greeting(): string
    {
        $value = $this->settings->get('assistant_greeting', AssistantDefaults::greeting());
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : AssistantDefaults::greeting();
    }

    public function statusLine(): string
    {
        try {
            $setting = $this->setting();
            $ready = $this->enabled()
                && $setting !== null
                && is_string($setting->api_key)
                && $setting->api_key !== '';
        } catch (\Throwable) {
            return 'chat available: no (none)';
        }

        $label = $setting ? $setting->provider.'/'.$setting->active_model : 'none';

        return 'chat available: '.($ready ? 'yes' : 'no').' ('.$label.')';
    }
}
