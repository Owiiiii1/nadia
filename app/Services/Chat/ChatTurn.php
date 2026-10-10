<?php

namespace App\Services\Chat;

class ChatTurn
{
    public function __construct(
        public readonly ?string $session,
        public readonly ?string $reply,
        public readonly bool $leadCaptured,
        public readonly int $status = 200,
        public readonly ?string $error = null,
        public readonly ?string $code = null,
    ) {}
}
