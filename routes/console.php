<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('analytics:prune')->dailyAt('04:00')->timezone('UTC');
Schedule::command('logs:prune')->dailyAt('04:10')->timezone('UTC');
