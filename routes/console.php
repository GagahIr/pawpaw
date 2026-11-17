<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mark confirmed bookings as completed jika 48+ jam lewat jadwal
Schedule::command('app:mark-completed-bookings')->hourly();
