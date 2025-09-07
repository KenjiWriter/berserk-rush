<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Infrastructure\RNG\RandomProvider;
use App\Infrastructure\RNG\DefaultRandomProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind RandomProvider interface to default implementation
        $this->app->bind(RandomProvider::class, DefaultRandomProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
