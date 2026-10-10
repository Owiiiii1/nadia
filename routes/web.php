<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'show'])->name('landing');
Route::post('/leads', [LeadController::class, 'store'])
    ->middleware('throttle:leads')
    ->name('leads.store');
Route::post('/chat/message', [ChatController::class, 'store'])
    ->middleware('throttle:chat')
    ->name('chat.message');
// Owl Admin routes
require __DIR__.'/owl-admin-pages.php';
require __DIR__.'/owl-admin-auth.php';
