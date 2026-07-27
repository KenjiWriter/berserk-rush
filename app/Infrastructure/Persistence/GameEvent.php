<?php

namespace App\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class GameEvent extends Model
{
    use HasUlids;

    protected $table = 'game_events';

    public const EVENT_GOLD_2X = 'gold_2x';
    public const EVENT_EXP_2X = 'exp_2x';
    public const EVENT_DROP_2X = 'drop_2x';
    public const EVENT_GEMS_2X = 'gems_2x';
    public const EVENT_UPGRADE_BONUS = 'upgrade_bonus';

    public static array $availableEvents = [
        self::EVENT_GOLD_2X => [
            'key' => self::EVENT_GOLD_2X,
            'name' => 'Event Weekendowy: Złoto x2',
            'description' => 'Zarabiaj 2x więcej Złota ze wszystkich wygranych walk i dungeonów!',
            'icon' => 'fa-solid fa-coins',
            'color' => 'text-yellow-400',
            'border_color' => 'border-yellow-500/60',
            'bg_gradient' => 'from-yellow-950/80 via-amber-900/60 to-stone-900/90',
            'badge_bg' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/40',
            'multiplier' => 2.0,
        ],
        self::EVENT_EXP_2X => [
            'key' => self::EVENT_EXP_2X,
            'name' => 'Event Weekendowy: EXP x2',
            'description' => 'Zdobywaj 2x więcej punktów Doświadczenia (EXP) i awansuj szybciej!',
            'icon' => 'fa-solid fa-bolt',
            'color' => 'text-sky-400',
            'border_color' => 'border-sky-500/60',
            'bg_gradient' => 'from-sky-950/80 via-blue-900/60 to-stone-900/90',
            'badge_bg' => 'bg-sky-500/20 text-sky-300 border-sky-500/40',
            'multiplier' => 2.0,
        ],
        self::EVENT_DROP_2X => [
            'key' => self::EVENT_DROP_2X,
            'name' => 'Event Weekendowy: Drop Rate x2',
            'description' => 'Podwójna szansa na wypadnięcie przedmiotów i materiałów z potworów!',
            'icon' => 'fa-solid fa-gift',
            'color' => 'text-emerald-400',
            'border_color' => 'border-emerald-500/60',
            'bg_gradient' => 'from-emerald-950/80 via-teal-900/60 to-stone-900/90',
            'badge_bg' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
            'multiplier' => 2.0,
        ],
        self::EVENT_GEMS_2X => [
            'key' => self::EVENT_GEMS_2X,
            'name' => 'Event Weekendowy: Gemy x2',
            'description' => 'Podwójna ilość Klejnotów (Gems) wypadających ze specjalnych skrzyń i walk!',
            'icon' => 'fa-solid fa-gem',
            'color' => 'text-purple-400',
            'border_color' => 'border-purple-500/60',
            'bg_gradient' => 'from-purple-950/80 via-fuchsia-900/60 to-stone-900/90',
            'badge_bg' => 'bg-purple-500/20 text-purple-300 border-purple-500/40',
            'multiplier' => 2.0,
        ],
        self::EVENT_UPGRADE_BONUS => [
            'key' => self::EVENT_UPGRADE_BONUS,
            'name' => 'Event Weekendowy: Błogosławieństwo Kowala (+15% Szansy)',
            'description' => '+15% większe prawdopodobieństwo pomyślnego ulepszenia przedmiotu u Kowala!',
            'icon' => 'fa-solid fa-hammer',
            'color' => 'text-amber-400',
            'border_color' => 'border-amber-500/60',
            'bg_gradient' => 'from-amber-950/80 via-orange-900/60 to-stone-900/90',
            'badge_bg' => 'bg-amber-500/20 text-amber-300 border-amber-500/40',
            'multiplier' => 1.15,
        ],
    ];

    protected $fillable = [
        'event_key',
        'name',
        'description',
        'multiplier',
        'is_active',
        'mode',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'multiplier' => 'float',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
