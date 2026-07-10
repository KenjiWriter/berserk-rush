<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Application\Economy\Jobs\ExpireMarketListingsJob;
use App\Application\Mail\Jobs\ExpireOldMailJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireMarketListingsJob())->hourly();
Schedule::job(new ExpireOldMailJob())->daily();
