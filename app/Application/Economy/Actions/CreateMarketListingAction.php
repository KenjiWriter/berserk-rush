<?php

namespace App\Application\Economy\Actions;

use App\Application\Shared\Result;
use App\Domain\Economy\Events\MarketListingCreated;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\MarketListing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateMarketListingAction
{
    private const LISTING_FEES = [
        24 => 100,
        48 => 250,
        72 => 500,
    ];

    public function execute(Character $character, ItemInstance $item, int $price, string $currency, int $durationHours): Result
    {
        if ($item->owner_character_id !== $character->id) {
            return Result::error('NOT_OWNER', 'Ten przedmiot nie należy do Ciebie.');
        }

        if ($item->location !== 'inventory') {
            return Result::error('NOT_IN_INVENTORY', 'Przedmiot musi znajdować się w plecaku, aby go wystawić.');
        }

        if ($item->bound_to_character) {
            return Result::error('ITEM_BOUND', 'Przedmiot jest przywiązany do postaci i nie może być sprzedany.');
        }

        if ($price <= 0) {
            return Result::error('INVALID_PRICE', 'Cena musi być większa od zera.');
        }

        if (!in_array($currency, ['gold', 'gems'])) {
            return Result::error('INVALID_CURRENCY', 'Nieprawidłowa waluta. Wybierz złoto lub klejnoty.');
        }

        if (!array_key_exists($durationHours, self::LISTING_FEES)) {
            return Result::error('INVALID_DURATION', 'Nieprawidłowy czas trwania. Wybierz 24, 48 lub 72 godziny.');
        }

        $listingFee = self::LISTING_FEES[$durationHours];

        if ($character->gold < $listingFee) {
            return Result::error('INSUFFICIENT_GOLD', "Nie masz wystarczająco złota na opłatę wystawienia ({$listingFee} szt.).");
        }

        try {
            return DB::transaction(function () use ($character, $item, $price, $currency, $durationHours, $listingFee) {
                $idempotencyKey = 'market_list:' . $item->id . ':' . Str::ulid();

                // Deduct listing fee
                $character->gold -= $listingFee;
                $character->save();

                CurrencyLedger::create([
                    'id' => Str::ulid(),
                    'idempotency_key' => $idempotencyKey . ':fee',
                    'character_id' => $character->id,
                    'currency_type' => 'gold',
                    'amount' => -$listingFee,
                    'balance_after' => $character->gold,
                    'source_type' => 'market_listing_fee',
                    'source_id' => $item->id,
                    'description' => "Opłata za wystawienie na market ({$durationHours}h)",
                    'created_at' => now(),
                ]);

                // Move item to market
                $item->update([
                    'location' => 'market',
                    'owner_character_id' => null,
                ]);

                ItemLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'item_instance_id' => $item->id,
                    'action' => 'listed_on_market',
                    'ref_type' => 'market_listing',
                    'ref_id' => null,
                    'quantity_change' => -1,
                    'idempotency_key' => $idempotencyKey . ':item',
                ]);

                // Create listing
                $listing = MarketListing::create([
                    'seller_character_id' => $character->id,
                    'item_instance_id' => $item->id,
                    'price' => $price,
                    'currency' => $currency,
                    'status' => 'active',
                    'expires_at' => now()->addHours($durationHours),
                ]);

                // Update item ledger ref_id
                ItemLedger::where('idempotency_key', $idempotencyKey . ':item')
                    ->update(['ref_id' => $listing->id]);

                event(new MarketListingCreated($listing));

                Log::info('Market listing created', [
                    'listing_id' => $listing->id,
                    'character_id' => $character->id,
                    'item_id' => $item->id,
                    'price' => $price,
                    'currency' => $currency,
                    'duration' => $durationHours,
                ]);

                return Result::ok([
                    'listing' => $listing,
                    'fee' => $listingFee,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('CreateMarketListing failed', [
                'character_id' => $character->id,
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return Result::error('LISTING_FAILED', 'Wystąpił błąd podczas wystawiania przedmiotu na market.');
        }
    }

    public static function getListingFees(): array
    {
        return self::LISTING_FEES;
    }
}
