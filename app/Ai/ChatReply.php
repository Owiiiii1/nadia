<?php

namespace App\Ai;

class ChatReply
{
    public function __construct(
        public readonly string $text,
        public readonly ?int $tokensIn,
        public readonly ?int $tokensOut,
        public readonly int $latencyMs,
    ) {}
}
