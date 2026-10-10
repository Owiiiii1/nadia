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

        $response = ChatHttp::pending()
            ->withToken($this->apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $payloadMessages,
                'max_tokens' => $maxTokens,
            ]);

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
