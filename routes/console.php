<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Requires the scheduler to be running (`php artisan schedule:work`, or a
| cron entry invoking `schedule:run` every minute) -- see
| docs/13-deployment.md.
|
*/

// Integration logs grow with every ESB call. Retention is configured in
// config/esb.php and defaults to disabled-safe: a zero window keeps everything
// rather than deleting it (docs/11-logging-monitoring.md).
Schedule::command('integration-logs:prune')
    ->dailyAt('02:30')
    ->onOneServer()
    ->withoutOverlapping();
