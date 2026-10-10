<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Support\AssistantDefaults;
use App\Support\ChatAvailability;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssistantSettingsController extends Controller
{
    public function __construct(
        private Settings $settings,
        private ChatAvailability $availability,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $setting = null;

        try {
            $setting = $this->availability->setting();
            $available = $this->availability->available();
        } catch (\Throwable) {
            $available = false;
        }

        $prompt = $this->settings->get('assistant_system_prompt', AssistantDefaults::systemPrompt());
        $greeting = $this->settings->get('assistant_greeting', AssistantDefaults::greeting());

        return [
            'chat_enabled' => $this->availability->enabled(),
            'greeting' => is_string($greeting) ? $greeting : AssistantDefaults::greeting(),
            'system_prompt' => is_string($prompt) ? $prompt : AssistantDefaults::systemPrompt(),
            'available' => $available,
            'provider' => $setting?->provider,
            'provider_label' => match ($setting?->provider) {
                'openai' => 'OpenAI',
                'anthropic' => 'Claude',
                'gemini' => 'Gemini',
                default => null,
            },
            'model' => $setting?->active_model,
        ];
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chat_enabled' => ['required', 'boolean'],
            'greeting' => ['required', 'string', 'max:1000'],
            'system_prompt' => ['required', 'string', 'max:20000'],
        ]);

        $this->settings->set('chat_enabled', $request->boolean('chat_enabled') ? '1' : '0');
        $this->settings->set('assistant_greeting', trim($validated['greeting']));
        $this->settings->set('assistant_system_prompt', $validated['system_prompt']);

        return redirect()->route('settings.index', ['tab' => 'assistant']);
    }

    public function reset(): RedirectResponse
    {
        $this->settings->set('assistant_system_prompt', AssistantDefaults::systemPrompt());

        return redirect()->route('settings.index', ['tab' => 'assistant']);
    }
}
