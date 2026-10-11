<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\TrackController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('landing');
Route::post('/leads', [LeadController::class, 'store'])
    ->middleware('throttle:leads')
    ->name('leads.store');
Route::post('/chat/message', [ChatController::class, 'store'])
    ->middleware('throttle:chat')
    ->name('chat.message');
Route::post('/t', [TrackController::class, 'store'])
    ->withoutMiddleware([
        PreventRequestForgery::class,
        ValidateCsrfToken::class,
    ])
    ->middleware('throttle:track')
    ->name('track');
// Owl Admin routes
require __DIR__.'/owl-admin-pages.php';
require __DIR__.'/owl-admin-auth.php';
