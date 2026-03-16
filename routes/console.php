<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Define scheduled tasks for the application.
|
*/

// Update expired subscriptions and insurance policies daily at midnight
Schedule::command('subscriptions:update-expired')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/subscriptions-update.log'));

// Disable fingerprint access for expired subscriptions/insurance daily at 00:10
Schedule::command('access:disable-expired')
    ->dailyAt('00:10')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/access-disable-expired.log'));

// Alternative: Run subscription check every hour to catch expirations quickly
Schedule::command('subscriptions:update-expired')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/subscriptions-update.log'));

// Also check for expired access every hour
Schedule::command('access:disable-expired')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/access-disable-expired.log'));

// ZKTeco/ZKBio health + sync tasks (shared-hosting friendly via schedule:run)
Schedule::command('zkteco:health-check')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/zkteco-health-check.log'));

Schedule::command('zkteco:sync-events')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/zkteco-events-sync.log'));

Schedule::command('zkteco:sync-personnel')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/zkteco-personnel-sync.log'));
