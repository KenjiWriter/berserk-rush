<?php

namespace App\Domain\Combat\Enums;

enum MonsterType: string
{
    case ANIMAL = 'animal';
    case PLANT = 'plant';
    case GOBLIN = 'goblin';
    case UNDEAD = 'undead';
    case TROLL = 'troll';
    case OGRE = 'ogre';
    case ORC = 'orc';
    case DEMON = 'demon';
    case GOLEM = 'golem';
    case MONSTER = 'monster';
    case HUMAN = 'human';
    case DRAGON = 'dragon';
    case ELEMENTAL = 'elemental';

    public function label(): string
    {
        return match($this) {
            self::ANIMAL => 'Zwierzę',
            self::PLANT => 'Roślina',
            self::GOBLIN => 'Goblin',
            self::UNDEAD => 'Nieumarły',
            self::TROLL => 'Troll',
            self::OGRE => 'Ogr',
            self::ORC => 'Ork',
            self::DEMON => 'Demon',
            self::GOLEM => 'Golem',
            self::MONSTER => 'Potwór',
            self::HUMAN => 'Człowiek',
            self::DRAGON => 'Smok',
            self::ELEMENTAL => 'Żywiołak',
        };
    }
}
