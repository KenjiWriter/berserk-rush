<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Infrastructure\RNG\RandomProvider;
use App\Infrastructure\RNG\DefaultRandomProvider;
use App\Infrastructure\RNG\DeterministicRandomProvider;

class RngServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind RandomProvider to appropriate implementation based on environment
        $this->app->bind(RandomProvider::class, function ($app) {
            if ($app->environment('testing')) {
                // In tests, you might want deterministic behavior
                return new DeterministicRandomProvider([1, 2, 3, 4, 5]);
            }

            return new DefaultRandomProvider();
        });
    }

    public function boot(): void
    {
        //
    }
}
