<?php

namespace App\Domain\Combat\Enums;

enum MonsterType: string
{
    case ANIMAL = 'animal';
    case UNDEAD = 'undead';
    case TROLL = 'troll';
    case ORC = 'orc';
    case DEMON = 'demon';
    // Rasa zbiorcza (rework ras, 2026-07-29): wcześniejsze rzadkie/niszowe typy
    // (roślina, goblin, ogr, golem, uogólniony "potwór", człowiek, smok, żywiołak)
    // zostały scalone tutaj - nie miały własnego bonusu strong_vs_/resist_ w
    // EnchantmentStrategy, więc rozdrobnienie na 13 typów nie miało praktycznego
    // znaczenia balansowego. Ork i Troll zostały jako osobne rasy na życzenie.
    case MYSTICAL = 'mystical';

    public function label(): string
    {
        return match($this) {
            self::ANIMAL => 'Zwierzę',
            self::UNDEAD => 'Nieumarły',
            self::TROLL => 'Troll',
            self::ORC => 'Ork',
            self::DEMON => 'Demon',
            self::MYSTICAL => 'Mistyczny',
        };
    }
}
