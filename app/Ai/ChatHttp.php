<?php

namespace App\Ai;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ChatHttp
{
    public static function pending(): PendingRequest
    {
        return Http::timeout(30)
            ->acceptJson()
            ->retry(2, 1000, function ($exception): bool {
                if ($exception instanceof RequestException && $exception->response) {
                    $status = $exception->response->status();

                    return $status === 429 || $status >= 500;
                }

                return false;
            }, throw: false);
    }
}
