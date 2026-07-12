<?php

namespace App\Domain\Quests\Enums;

enum QuestStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case REWARDED = 'rewarded';
    case CANCELLED = 'cancelled';
}
