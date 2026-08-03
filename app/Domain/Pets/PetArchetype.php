<?php

namespace App\Domain\Pets;

/**
 * "Rodzaj" peta (Atakujący/Obrony/Wspomagający) - określa jaką pasywkę
 * bojową daje aktywny towarzysz (patrz Pet::getArchetypeBonusPercentFor()
 * i Character::getEquipmentStats()).
 */
class PetArchetype
{
    public const ATTACKER = 'attacker';
    public const DEFENSE = 'defense';
    public const SUPPORT = 'support';

    public static function label(?string $archetype): string
    {
        return match ($archetype) {
            self::ATTACKER => 'Atakujący',
            self::DEFENSE => 'Obrony',
            self::SUPPORT => 'Wspomagający',
            default => 'Brak',
        };
    }

    public static function passiveDescription(?string $archetype): string
    {
        return match ($archetype) {
            self::ATTACKER => 'Więcej obrażeń (atak fizyczny i magiczny)',
            self::DEFENSE => 'Więcej obrony i punktów życia',
            self::SUPPORT => 'Więcej szansy na unik i niższe koszty many',
            default => 'Brak pasywki (pet nigdy nie przeszedł fuzji)',
        };
    }

    public static function all(): array
    {
        return [self::ATTACKER, self::DEFENSE, self::SUPPORT];
    }
}
