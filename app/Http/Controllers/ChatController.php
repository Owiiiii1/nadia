<?php

namespace App\Http\Controllers;

use App\Services\Chat\ChatMessenger;
use App\Support\ChatAvailability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private ChatAvailability $availability,
        private ChatMessenger $messenger,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if (! $this->availability->available()) {
            return response()->json(['error' => 'unavailable'], 503);
        }

        $message = $request->input('message');
        $session = $request->input('session');
        $request->merge([
            'message' => is_string($message) ? trim($message) : $message,
            'session' => is_string($session) && $session !== '' ? $session : null,
        ]);

        $max = (int) config('nadia.chat.max_message_length', 1000);
        $validated = $request->validate([
            'session' => ['nullable', 'uuid'],
            'message' => ['required', 'string', 'max:'.$max],
        ]);

        $turn = $this->messenger->handle(
            $request,
            $validated['session'] ?? null,
            $validated['message'],
        );

        if ($turn->status !== 200) {
            $payload = ['error' => $turn->error];
            if ($turn->code !== null) {
                $payload['code'] = $turn->code;
            }

            return response()->json($payload, $turn->status);
        }

        return response()->json([
            'session' => $turn->session,
            'reply' => $turn->reply,
            'lead_captured' => $turn->leadCaptured,
        ]);
    }
}
