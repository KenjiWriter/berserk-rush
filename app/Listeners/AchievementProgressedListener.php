<?php

namespace App\Listeners;

use App\Domain\Achievements\Events\AchievementProgressed;
use App\Application\Achievements\AchievementService;

class AchievementProgressedListener
{
    protected AchievementService $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    public function handle(AchievementProgressed $event): void
    {
        $this->achievementService->progress($event->character, $event->type, $event->amount, $event->context);
    }
}
