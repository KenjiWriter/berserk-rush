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

        $this->app->singleton(\App\Application\Shared\NotificationTracker::class, function ($app) {
            return new \App\Application\Shared\NotificationTracker();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(function (\App\Domain\Characters\Events\CharacterLeveledUp $event) {
            app(\App\Application\Quests\QuestService::class)->cancelExpiredQuests($event->character);
        });

        \Illuminate\Support\Facades\Event::listen(
            \SocialiteProviders\Manager\SocialiteWasCalled::class,
            [\SocialiteProviders\Apple\AppleExtendSocialite::class, 'handle']
        );
    }
}
