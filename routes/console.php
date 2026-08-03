<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Application\Economy\Jobs\ExpireMarketListingsJob;
use App\Application\Mail\Jobs\ExpireOldMailJob;
use App\Jobs\WorldBossRewardJob;
use App\Jobs\WeeklyRankingRewardJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireMarketListingsJob())->hourly();
Schedule::command('app:world-boss-tick')->hourly();
Schedule::job(new WorldBossRewardJob())->hourly();
Schedule::job(new ExpireOldMailJob())->daily();

// Rankingi tygodniowe: rozliczenie i nagrody co poniedziałek o 00:01
Schedule::job(new WeeklyRankingRewardJob())->weeklyOn(1, '00:01');
