<?php

namespace App\Http\Controllers;

use App\Support\ChatAvailability;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function show(ChatAvailability $chat): View
    {
        $available = $chat->available();

        return view('landing', [
            'startedAt' => now()->getTimestamp(),
            'chatAvailable' => $available,
            'chatGreeting' => $available ? $chat->greeting() : '',
        ]);
    }
}
