<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MapSeeder', '--force' => true]);
echo Illuminate\Support\Facades\Artisan::output();

echo Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MonsterSeeder', '--force' => true]);
echo Illuminate\Support\Facades\Artisan::output();

echo Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MonsterLootSeeder', '--force' => true]);
echo Illuminate\Support\Facades\Artisan::output();

echo Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'MaterialMapTierSeeder', '--force' => true]);
echo Illuminate\Support\Facades\Artisan::output();

echo "Done.\n";
