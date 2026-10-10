<?php

namespace App\Ai;

interface ChatClient
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function reply(string $system, array $messages, int $maxTokens): ChatReply;
}
