<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weekly family insight: Monday 04:00 in the app timezone.
// withoutOverlapping guards against a long run overlapping the next week's
// slot, and onOneServer stops a multi-node deploy from generating twice.
Schedule::command('finance:insights')
    ->weeklyOn(1, '04:00')
    ->withoutOverlapping()
    ->onOneServer();
