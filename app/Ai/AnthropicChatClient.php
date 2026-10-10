<?php

namespace App\Ai;

use RuntimeException;

class AnthropicChatClient implements ChatClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function reply(string $system, array $messages, int $maxTokens): ChatReply
    {
        $started = hrtime(true);
        $response = ChatHttp::pending()
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => $this->messages($messages),
            ]);

        $latency = (int) ((hrtime(true) - $started) / 1_000_000);

        if (! $response->successful()) {
            throw new RuntimeException('Anthropic request failed ('.$response->status().')');
        }

        $text = '';
        foreach ($response->json('content', []) as $part) {
            if (is_array($part) && ($part['type'] ?? '') === 'text') {
                $text .= (string) ($part['text'] ?? '');
            }
        }

        return new ChatReply(
            $text,
            $this->intOrNull($response->json('usage.input_tokens')),
            $this->intOrNull($response->json('usage.output_tokens')),
            $latency,
        );
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return list<array{role: string, content: string}>
     */
    private function messages(array $messages): array
    {
        $out = [];

        foreach ($messages as $message) {
            $role = ($message['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = (string) ($message['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $last = array_key_last($out);
            if ($last !== null && $out[$last]['role'] === $role) {
                $out[$last]['content'] .= "\n".$content;

                continue;
            }
            $out[] = ['role' => $role, 'content' => $content];
        }

        while ($out !== [] && $out[0]['role'] === 'assistant') {
            array_shift($out);
        }

        if ($out === []) {
            $out[] = ['role' => 'user', 'content' => 'Hello'];
        }

        return $out;
    }

    private function intOrNull(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
