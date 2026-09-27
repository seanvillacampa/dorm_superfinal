<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Daily scheduler — runs every day at midnight.
 * On Windows dev: php artisan schedule:run  (or set up Task Scheduler)
 * In production:  * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
 */
Schedule::command('invoices:process-statuses')->dailyAt('00:05');
