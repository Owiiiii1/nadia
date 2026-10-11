<?php

namespace App\Ai;

use RuntimeException;

class OpenAiChatClient implements ChatClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function reply(string $system, array $messages, int $maxTokens): ChatReply
    {
        $started = hrtime(true);
        $payloadMessages = [['role' => 'system', 'content' => $system]];

        foreach ($messages as $message) {
            $role = ($message['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = (string) ($message['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $payloadMessages[] = ['role' => $role, 'content' => $content];
        }

        $effort = $this->reasoningEffort($this->model);
        $payload = [
            'model' => $this->model,
            'messages' => $payloadMessages,
            'max_completion_tokens' => $effort === null ? $maxTokens : max($maxTokens, 2000),
        ];

        if ($effort !== null) {
            $payload['reasoning_effort'] = $effort;
        }

        $response = ChatHttp::pending()
            ->withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        $latency = $this->latency($started);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI request failed ('.$response->status().')');
        }

        return new ChatReply(
            (string) $response->json('choices.0.message.content'),
            $this->intOrNull($response->json('usage.prompt_tokens')),
            $this->intOrNull($response->json('usage.completion_tokens')),
            $latency,
        );
    }

    private function reasoningEffort(string $model): ?string
    {
        $id = strtolower($model);

        if (str_starts_with($id, 'gpt-5')) {
            return 'minimal';
        }

        if (str_starts_with($id, 'o1') || str_starts_with($id, 'o3') || str_starts_with($id, 'o4')) {
            return 'low';
        }

        return null;
    }

    private function latency(int $started): int
    {
        return (int) ((hrtime(true) - $started) / 1_000_000);
    }

    private function intOrNull(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
