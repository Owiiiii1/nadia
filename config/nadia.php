<?php

return [
    'chat' => [
        'per_minute' => 20,
        'max_message_length' => 1000,
        'session_user_messages' => 30,
        'daily_ip_limit' => 100,
        'daily_global_limit' => (int) env('NADIA_CHAT_DAILY_GLOBAL_LIMIT', 1500),
        'context_messages' => 20,
        'max_output_tokens' => 500,
    ],
];
