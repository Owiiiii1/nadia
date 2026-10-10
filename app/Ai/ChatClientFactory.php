<?php

namespace App\Ai;

use App\Support\ChatAvailability;
use RuntimeException;

class ChatClientFactory
{
    public function __construct(private ChatAvailability $availability) {}

    public function make(): ChatClient
    {
        $setting = $this->availability->setting();
        $key = $setting?->api_key;

        if ($setting === null || ! is_string($key) || $key === '' || ! is_string($setting->active_model) || $setting->active_model === '') {
            throw new RuntimeException('No active AI provider.');
        }

        return match ($setting->provider) {
            'openai' => new OpenAiChatClient($key, $setting->active_model),
            'anthropic' => new AnthropicChatClient($key, $setting->active_model),
            'gemini' => new GeminiChatClient($key, $setting->active_model),
            default => throw new RuntimeException('Unsupported AI provider.'),
        };
    }
}
