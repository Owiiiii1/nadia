<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ChatSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\TrackingCodeController;
use App\Http\Controllers\Settings\AssistantSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Settings\AiSettingsController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\TelegramSettingsController;
use App\Http\Controllers\Settings\UserController as SettingsUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use OwlSolutions\CustomAdminKit\Support\AdminRouteMiddleware;

/*
| Admin preset pages (v0.5).
| Loaded from routes/web.php via:
| require __DIR__.'/owl-admin-pages.php';
*/

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ])
    ->name('telegram.webhook');

Route::middleware(AdminRouteMiddleware::stack())->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/export.csv', [LeadController::class, 'export'])->name('leads.export');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

    Route::get('/chats', [ChatSessionController::class, 'index'])->name('chats.index');
    Route::get('/chats/{session}', [ChatSessionController::class, 'show'])->name('chats.show');
    Route::delete('/chats/{session}', [ChatSessionController::class, 'destroy'])->name('chats.destroy');
    Route::put('/settings/assistant', [AssistantSettingsController::class, 'update'])->name('settings.assistant.update');
    Route::post('/settings/assistant/reset', [AssistantSettingsController::class, 'reset'])->name('settings.assistant.reset');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('/tracking-codes', [TrackingCodeController::class, 'index'])->name('tracking.index');
    Route::post('/tracking-codes', [TrackingCodeController::class, 'store'])->name('tracking.store');
    Route::put('/tracking-codes/pixels', [TrackingCodeController::class, 'updatePixels'])->name('tracking.pixels');
    Route::patch('/tracking-codes/{trackingCode}', [TrackingCodeController::class, 'update'])->name('tracking.update');
    Route::delete('/tracking-codes/{trackingCode}', [TrackingCodeController::class, 'destroy'])->name('tracking.destroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/notifications', [NotificationSettingsController::class, 'update'])->name('settings.notifications.update');
    Route::post('/settings/notifications/telegram/connect', [NotificationSettingsController::class, 'connect'])->name('settings.notifications.telegram.connect');
    Route::delete('/settings/notifications/telegram', [NotificationSettingsController::class, 'remove'])->name('settings.notifications.telegram.remove');
    Route::post('/settings/notifications/telegram/test', [NotificationSettingsController::class, 'testTelegram'])->name('settings.notifications.telegram.test');
    Route::post('/settings/notifications/email/test', [NotificationSettingsController::class, 'testEmail'])->name('settings.notifications.email.test');
    Route::post('/settings/language', [SettingsController::class, 'updateLanguage'])->name('settings.language.update');
    Route::post('/settings/users', [SettingsUserController::class, 'store'])->name('settings.users.store');
    Route::patch('/settings/users/{user}', [SettingsUserController::class, 'update'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [SettingsUserController::class, 'destroy'])->name('settings.users.destroy');

    Route::post('/settings/telegram/save-token', [TelegramSettingsController::class, 'saveToken'])
        ->name('settings.telegram.save-token');
    Route::post('/settings/telegram/check', [TelegramSettingsController::class, 'check'])
        ->name('settings.telegram.check');
    Route::post('/settings/telegram/set-webhook', [TelegramSettingsController::class, 'setWebhook'])
        ->name('settings.telegram.set-webhook');
    Route::post('/settings/telegram/remove-webhook', [TelegramSettingsController::class, 'removeWebhook'])
        ->name('settings.telegram.remove-webhook');

    // Kit smoke still looks up this name. The App tab is gone; the URL only redirects.
    Route::get('/app-settings', function () {
        return redirect()->route('settings.index');
    })->name('app-settings.index');

    Route::get('/ai-settings', [AiSettingsController::class, 'index'])->name('ai-settings.index');
    Route::post('/ai-settings/{provider}/key', [AiSettingsController::class, 'saveKey'])->name('ai-settings.save-key');
    Route::post('/ai-settings/{provider}/check', [AiSettingsController::class, 'check'])->name('ai-settings.check');
    Route::post('/ai-settings/{provider}/activate', [AiSettingsController::class, 'activate'])->name('ai-settings.activate');
    Route::post('/ai-settings/deactivate', [AiSettingsController::class, 'deactivate'])->name('ai-settings.deactivate');

    Route::get('/statistics/logs', function () {
        return Inertia::render('Statistics/Logs');
    })->name('statistics.logs');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
