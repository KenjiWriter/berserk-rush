<?php

namespace App\Domain\Quests\Enums;

enum QuestType: string
{
    case GATHERING = 'gathering';
    case HUNTING = 'hunting';
    case ACTION = 'action';
}
