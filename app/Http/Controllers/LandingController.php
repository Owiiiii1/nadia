<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LandingController extends Controller
{
    public function show(): View
    {
        return view('landing', [
            'startedAt' => now()->getTimestamp(),
        ]);
    }
}
