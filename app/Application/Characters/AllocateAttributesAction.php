<?php

namespace App\Application\Characters;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\DB;

class AllocateAttributesAction
{
    public function execute(Character $character, array $addedStats): Result
    {
        $validAttributes = ['str', 'int', 'vit', 'agi'];
        $totalRequested = 0;
        $toAdd = [];

        foreach ($validAttributes as $stat) {
            $val = (int)($addedStats[$stat] ?? 0);
            if ($val < 0) {
                return Result::error('INVALID_VALUE', 'Wartość przydzielanych atrybutów nie może być ujemna.');
            }
            $toAdd[$stat] = $val;
            $totalRequested += $val;
        }

        if ($totalRequested <= 0) {
            return Result::error('NO_POINTS_REQUESTED', 'Nie podano punktów do rozdania.');
        }

        try {
            $result = DB::transaction(function () use ($character, $toAdd, $totalRequested, $validAttributes) {
                /** @var Character|null $lockedCharacter */
                $lockedCharacter = Character::where('id', $character->id)->lockForUpdate()->first();

                if (!$lockedCharacter) {
                    return Result::error('CHARACTER_NOT_FOUND', 'Postać nie istnieje.');
                }

                $availablePoints = $lockedCharacter->character_points ?? 0;

                if ($totalRequested > $availablePoints) {
                    return Result::error('INSUFFICIENT_POINTS', "Brak wystarczającej liczby punktów postaci (wymagane: {$totalRequested}, dostępne: {$availablePoints}).");
                }

                $attributes = $lockedCharacter->attributes ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0];

                foreach ($validAttributes as $stat) {
                    $attributes[$stat] = ($attributes[$stat] ?? 0) + $toAdd[$stat];
                }

                $lockedCharacter->attributes = $attributes;
                $lockedCharacter->character_points = $availablePoints - $totalRequested;
                $lockedCharacter->save();

                return Result::ok($lockedCharacter);
            });

            // Cache musi zostać wyczyszczony dopiero PO zatwierdzeniu transakcji - w
            // przeciwnym razie równoległy odczyt tuż przed commitem może odtworzyć
            // cache ze starymi (nieaktualnymi) atrybutami, które utrzymają się do
            // wygaśnięcia TTL (3600s).
            if ($result->isOk()) {
                $result->getPayload()->clearStatsCache();
            }

            return $result;
        } catch (\Throwable $e) {
            return Result::error('DATABASE_ERROR', 'Wystąpił błąd podczas zapisywania atrybutów: ' . $e->getMessage());
        }
    }
}
