<?php

namespace App\Application\Events;

use App\Infrastructure\Persistence\GameEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class WeekendEventService
{
    /**
     * Pobiera aktualnie aktywny event weekendowy lub specjalny.
     */
    public function getActiveEvent(): ?array
    {
        return Cache::remember('active_game_event', 10, function () {
            // 1. Sprawdź czy jest wpis w bazie danych z trybem 'manual' lub 'auto'
            $dbEvent = GameEvent::where('is_active', true)->first();

            if ($dbEvent) {
                // Jeśli ma datę wygaszenia i upłynęła - dezaktywuj
                if ($dbEvent->ends_at && Carbon::now()->greaterThan($dbEvent->ends_at)) {
                    $dbEvent->update(['is_active' => false]);
                } else {
                    return $this->formatEventPayload($dbEvent->event_key, $dbEvent->mode, $dbEvent->starts_at, $dbEvent->ends_at, $dbEvent->multiplier);
                }
            }

            // 2. Sprawdź ogólne ustawienie trybu w bazie (np. zablokowany vs auto)
            $globalConfig = GameEvent::where('event_key', 'GLOBAL_CONFIG')->first();
            if ($globalConfig && $globalConfig->mode === 'disabled') {
                return null;
            }

            // 3. Automatyczne losowanie w weekend (Sobota/Niedziela)
            if ($this->isWeekend() || ($globalConfig && $globalConfig->mode === 'force_auto')) {
                $weekNumber = (int) Carbon::now()->format('W');
                $year = (int) Carbon::now()->format('Y');
                $seed = $year * 100 + $weekNumber;
                
                $eventKeys = array_keys(GameEvent::$availableEvents);
                $selectedKey = $eventKeys[$seed % count($eventKeys)];

                // Zdefiniowany czas trwania weekendu
                $startsAt = Carbon::now()->startOfWeek()->addDays(5); // Sobota 00:00
                $endsAt = Carbon::now()->endOfWeek(); // Niedziela 23:59:59

                return $this->formatEventPayload($selectedKey, 'auto', $startsAt, $endsAt);
            }

            return null;
        });
    }

    /**
     * Sprawdza, czy dzisiaj jest weekend (Sobota lub Niedziela).
     */
    public function isWeekend(): bool
    {
        return Carbon::now()->isWeekend();
    }

    /**
     * Mnożnik Złota (np. 2.0 jeśli aktywny gold_2x, inaczej 1.0).
     */
    public function getGoldMultiplier(): float
    {
        $active = $this->getActiveEvent();
        if ($active && $active['key'] === GameEvent::EVENT_GOLD_2X) {
            return (float) ($active['multiplier'] ?? 2.0);
        }
        return 1.0;
    }

    /**
     * Mnożnik EXP (np. 2.0 jeśli aktywny exp_2x, inaczej 1.0).
     */
    public function getExpMultiplier(): float
    {
        $active = $this->getActiveEvent();
        if ($active && $active['key'] === GameEvent::EVENT_EXP_2X) {
            return (float) ($active['multiplier'] ?? 2.0);
        }
        return 1.0;
    }

    /**
     * Mnożnik Szansy na Drop (np. 2.0 jeśli aktywny drop_2x, inaczej 1.0).
     */
    public function getDropMultiplier(): float
    {
        $active = $this->getActiveEvent();
        if ($active && $active['key'] === GameEvent::EVENT_DROP_2X) {
            return (float) ($active['multiplier'] ?? 2.0);
        }
        return 1.0;
    }

    /**
     * Mnożnik Gemów (np. 2.0 jeśli aktywny gems_2x, inaczej 1.0).
     */
    public function getGemsMultiplier(): float
    {
        $active = $this->getActiveEvent();
        if ($active && $active['key'] === GameEvent::EVENT_GEMS_2X) {
            return (float) ($active['multiplier'] ?? 2.0);
        }
        return 1.0;
    }

    /**
     * Bonus do ulepszeń u Kowala (+0.15 do szansy jeśli aktywny upgrade_bonus, inaczej 0.0).
     */
    public function getUpgradeBonusChance(): float
    {
        $active = $this->getActiveEvent();
        if ($active && $active['key'] === GameEvent::EVENT_UPGRADE_BONUS) {
            return 0.15; // +15% szansy
        }
        return 0.0;
    }

    /**
     * Aktywacja ręczna wybranego eventu przez Administratora.
     */
    public function setManualEvent(string $eventKey, int $durationHours = 48): void
    {
        if (!isset(GameEvent::$availableEvents[$eventKey])) {
            return;
        }

        // Wyłącz dotychczasowe aktywne eventy
        GameEvent::query()->update(['is_active' => false]);

        $meta = GameEvent::$availableEvents[$eventKey];
        $startsAt = Carbon::now();
        $endsAt = Carbon::now()->addHours($durationHours);

        GameEvent::updateOrCreate(
            ['event_key' => $eventKey],
            [
                'name' => $meta['name'],
                'description' => $meta['description'],
                'multiplier' => $meta['multiplier'],
                'is_active' => true,
                'mode' => 'manual',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]
        );

        Cache::forget('active_game_event');
    }

    /**
     * Włączenie automatycznego losowania weekendowego.
     */
    public function setAutoMode(): void
    {
        GameEvent::query()->update(['is_active' => false]);
        
        GameEvent::updateOrCreate(
            ['event_key' => 'GLOBAL_CONFIG'],
            [
                'name' => 'Automatyczny Tryb Weekendowy',
                'description' => 'System automatycznie wybiera jeden z 5 eventów na każdy weekend.',
                'multiplier' => 1.0,
                'is_active' => false,
                'mode' => 'auto',
            ]
        );

        Cache::forget('active_game_event');
    }

    /**
     * Całkowite wyłączenie eventów.
     */
    public function disableEvents(): void
    {
        GameEvent::query()->update(['is_active' => false]);

        GameEvent::updateOrCreate(
            ['event_key' => 'GLOBAL_CONFIG'],
            [
                'name' => 'Eventy Wyłączone',
                'description' => 'Wszystkie eventy globalne są obecnie wyłączone.',
                'multiplier' => 1.0,
                'is_active' => false,
                'mode' => 'disabled',
            ]
        );

        Cache::forget('active_game_event');
    }

    /**
     * Wymuszenie trybu Auto (działa również poza weekendem dla testów/wymuszenia auto).
     */
    public function setForceAutoMode(): void
    {
        GameEvent::query()->update(['is_active' => false]);

        GameEvent::updateOrCreate(
            ['event_key' => 'GLOBAL_CONFIG'],
            [
                'name' => 'Wymuszony Tryb Auto',
                'description' => 'System automatycznie wylosował event weekendowy i wymusił jego działanie.',
                'multiplier' => 1.0,
                'is_active' => false,
                'mode' => 'force_auto',
            ]
        );

        Cache::forget('active_game_event');
    }

    private function formatEventPayload(string $eventKey, string $mode, ?Carbon $startsAt = null, ?Carbon $endsAt = null, ?float $customMultiplier = null): ?array
    {
        $meta = GameEvent::$availableEvents[$eventKey] ?? null;
        if (!$meta) {
            return null;
        }

        return [
            'key' => $eventKey,
            'name' => $meta['name'],
            'description' => $meta['description'],
            'icon' => $meta['icon'],
            'color' => $meta['color'],
            'border_color' => $meta['border_color'],
            'bg_gradient' => $meta['bg_gradient'],
            'badge_bg' => $meta['badge_bg'],
            'multiplier' => $customMultiplier ?? $meta['multiplier'],
            'mode' => $mode,
            'starts_at' => $startsAt ? $startsAt->toIso8601String() : null,
            'ends_at' => $endsAt ? $endsAt->toIso8601String() : null,
            'ends_at_timestamp' => $endsAt ? $endsAt->timestamp * 1000 : null,
        ];
    }
}
