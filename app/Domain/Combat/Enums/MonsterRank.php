<?php

namespace App\Domain\Combat\Enums;

enum MonsterRank: string
{
    case REGULAR = 'regular';
    case ELITE = 'elite';
    case BOSS = 'boss';
    case WORLDBOSS = 'worldboss';

    public function label(): string
    {
        return match($this) {
            self::REGULAR => 'Zwykły',
            self::ELITE => 'Elita',
            self::BOSS => 'Boss',
            self::WORLDBOSS => 'World Boss',
        };
    }
}
