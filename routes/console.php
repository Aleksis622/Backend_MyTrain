<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Unpaid tickets whose train has left are cancelled (and their open Stripe checkouts closed).
// Locally: php artisan schedule:work
Schedule::command('tickets:expire-unpaid')->everyFiveMinutes()->withoutOverlapping();
