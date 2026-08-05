<?php

namespace App\Helpers;

/**
 * Centralne mapowanie efektów walki (DoT, CC, procki broni, skille magiczne) na
 * czytelną etykietę PL + kolor Tailwind + ikonę Font Awesome. Używane przez log
 * walki i pasek statusów w widokach `map-stub`, `dungeon-run`, `arena-combat`
 * (Faza 3 rebalansu, 2026-08-05) - jedno źródło prawdy, żeby te trzy widoki nie
 * rozjechały się w nazwach/kolorach (wzorzec `EnchantmentStrategy::bonusLabel()`).
 *
 * Klucz `effect` to znormalizowany typ efektu: poison|bleed|fire|stun|freeze|heal|
 * magic|magic_burst|double_strike|armor_pen. Nieznany klucz dostaje neutralny
 * fallback (nie wysypuje widoku).
 */
class CombatLogHelper
{
    private const EFFECTS = [
        'poison' => ['label' => 'Trucizna',        'color' => 'text-emerald-400', 'badge' => 'bg-emerald-950/70 text-emerald-300 border-emerald-500/40', 'icon' => 'fa-skull-crossbones'],
        'bleed' => ['label' => 'Krwawienie',        'color' => 'text-rose-400',    'badge' => 'bg-rose-950/70 text-rose-300 border-rose-500/40',       'icon' => 'fa-droplet'],
        'fire' => ['label' => 'Podpalenie',         'color' => 'text-orange-400',  'badge' => 'bg-orange-950/70 text-orange-300 border-orange-500/40', 'icon' => 'fa-fire'],
        'stun' => ['label' => 'Ogłuszenie',         'color' => 'text-yellow-300',  'badge' => 'bg-yellow-950/70 text-yellow-200 border-yellow-500/40', 'icon' => 'fa-bolt'],
        'freeze' => ['label' => 'Zamrożenie',       'color' => 'text-cyan-300',    'badge' => 'bg-cyan-950/70 text-cyan-200 border-cyan-500/40',       'icon' => 'fa-snowflake'],
        'heal' => ['label' => 'Leczenie',           'color' => 'text-emerald-300', 'badge' => 'bg-emerald-950/70 text-emerald-200 border-emerald-500/40', 'icon' => 'fa-heart'],
        'magic' => ['label' => 'Magia',             'color' => 'text-indigo-300',  'badge' => 'bg-indigo-950/70 text-indigo-200 border-indigo-500/40', 'icon' => 'fa-wand-sparkles'],
        'magic_burst' => ['label' => 'Rozbłysk Magii', 'color' => 'text-fuchsia-300', 'badge' => 'bg-fuchsia-950/70 text-fuchsia-200 border-fuchsia-500/40', 'icon' => 'fa-star'],
        'double_strike' => ['label' => 'Podwójny Cios', 'color' => 'text-amber-300', 'badge' => 'bg-amber-950/70 text-amber-200 border-amber-500/40', 'icon' => 'fa-angles-right'],
        'armor_pen' => ['label' => 'Przebicie Pancerza', 'color' => 'text-slate-300', 'badge' => 'bg-slate-800/70 text-slate-200 border-slate-500/40', 'icon' => 'fa-shield-halved'],
    ];

    private const FALLBACK = ['label' => 'Efekt', 'color' => 'text-slate-300', 'badge' => 'bg-slate-800/70 text-slate-200 border-slate-500/40', 'icon' => 'fa-sparkles'];

    /**
     * Pełna definicja efektu: ['label','color','badge','icon']. `badge` to gotowy
     * zestaw klas Tailwind na małą pigułkę (tło+tekst+ramka).
     */
    public static function effect(?string $key): array
    {
        return self::EFFECTS[$key] ?? self::FALLBACK;
    }

    public static function label(?string $key): string
    {
        return self::effect($key)['label'];
    }

    public static function color(?string $key): string
    {
        return self::effect($key)['color'];
    }

    public static function icon(?string $key): string
    {
        return self::effect($key)['icon'];
    }

    public static function badgeClasses(?string $key): string
    {
        return self::effect($key)['badge'];
    }
}
