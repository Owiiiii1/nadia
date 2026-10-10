<?php

namespace App\Ai;

use RuntimeException;

class GeminiChatClient implements ChatClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public function reply(string $system, array $messages, int $maxTokens): ChatReply
    {
        $started = hrtime(true);
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            .rawurlencode($this->model)
            .':generateContent';

        $response = ChatHttp::pending()
            ->withQueryParameters(['key' => $this->apiKey])
            ->post($url, [
                'systemInstruction' => [
                    'parts' => [['text' => $system]],
                ],
                'contents' => $this->contents($messages),
                'generationConfig' => [
                    'maxOutputTokens' => $maxTokens,
                ],
            ]);

        $latency = (int) ((hrtime(true) - $started) / 1_000_000);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini request failed ('.$response->status().')');
        }

        $text = '';
        foreach ($response->json('candidates.0.content.parts', []) as $part) {
            if (is_array($part)) {
                $text .= (string) ($part['text'] ?? '');
            }
        }

        return new ChatReply(
            $text,
            $this->intOrNull($response->json('usageMetadata.promptTokenCount')),
            $this->intOrNull($response->json('usageMetadata.candidatesTokenCount')),
            $latency,
        );
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @return list<array{role: string, parts: list<array{text: string}>}>
     */
    private function contents(array $messages): array
    {
        $out = [];

        foreach ($messages as $message) {
            $role = ($message['role'] ?? '') === 'assistant' ? 'model' : 'user';
            $content = (string) ($message['content'] ?? '');
            if ($content === '') {
                continue;
            }
            $last = array_key_last($out);
            if ($last !== null && $out[$last]['role'] === $role) {
                $out[$last]['parts'][0]['text'] .= "\n".$content;

                continue;
            }
            $out[] = [
                'role' => $role,
                'parts' => [['text' => $content]],
            ];
        }

        while ($out !== [] && $out[0]['role'] === 'model') {
            array_shift($out);
        }

        if ($out === []) {
            $out[] = [
                'role' => 'user',
                'parts' => [['text' => 'Hello']],
            ];
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
