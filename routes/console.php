<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Nightly backup of the database and uploaded files, then old backups are
 * pruned. Requires the server cron: * * * * * php artisan schedule:run
 */
Schedule::command('backup:run --disable-notifications')->dailyAt('01:30');
Schedule::command('backup:clean --disable-notifications')->dailyAt('02:30');
