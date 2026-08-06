<?php

namespace App\Application\Economy\Actions;

use App\Application\Shared\Result;
use App\Domain\Economy\Events\MarketListingCreated;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\MarketListing;
use App\Infrastructure\Persistence\Pet;
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

    public function execute(Character $character, ItemInstance $item, int $price, string $currency, int $durationHours, int $quantity = 1): Result
    {
        if ($item->owner_character_id !== $character->id) {
            return Result::error('NOT_OWNER', 'Ten przedmiot nie należy do Ciebie.');
        }

        if (!in_array($item->location, ['inventory', 'equipped', 'material_stash'])) {
            return Result::error('NOT_IN_INVENTORY', 'Przedmiot musi znajdować się w plecaku lub magazynie materiałów, aby go wystawić.');
        }

        if ($item->bound_to_character) {
            return Result::error('ITEM_BOUND', 'Musisz zdjąć ten przedmiot, zanim wystawisz go na targowisko.');
        }

        if (!($item->template->is_tradeable ?? true)) {
            return Result::error('ITEM_UNTRADEABLE', 'Tego przedmiotu nie można wystawić na targowisko.');
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

        $quantity = max(1, $quantity);
        $stackSize = (int) ($item->stack_size ?? 1);
        if ($quantity > $stackSize) {
            return Result::error('INVALID_QUANTITY', "Nie możesz wystawić więcej niż posiadasz ({$stackSize} szt.).");
        }

        $listingFee = self::LISTING_FEES[$durationHours];

        if ($character->gold < $listingFee) {
            return Result::error('INSUFFICIENT_GOLD', "Nie masz wystarczająco złota na opłatę wystawienia ({$listingFee} szt.).");
        }

        try {
            return DB::transaction(function () use ($character, $item, $price, $currency, $durationHours, $listingFee, $quantity) {
                $idempotencyKey = 'market_list:' . $item->id . ':' . Str::ulid();
                $wasEquipped = $item->location === 'equipped';
                $stackSize = (int) ($item->stack_size ?? 1);

                // Deduct listing fee
                $character->gold -= $listingFee;
                $character->save();

                CurrencyLedger::create([
                    'id'              => Str::ulid(),
                    'idempotency_key' => $idempotencyKey . ':fee',
                    'character_id'    => $character->id,
                    'currency_type'   => 'gold',
                    'amount'          => -$listingFee,
                    'balance_after'   => $character->gold,
                    'source_type'     => 'market_listing_fee',
                    'source_id'       => $item->id,
                    'description'     => "Opłata za wystawienie na market ({$durationHours}h)",
                    'created_at'      => now(),
                ]);

                // Handle stack splitting when listing part of a stack
                if ($quantity < $stackSize) {
                    // Split: create a new item instance with the sold quantity
                    $listingItem = $item->replicate();
                    $listingItem->id = Str::ulid();
                    $listingItem->stack_size = $quantity;
                    $listingItem->location = 'market';
                    $listingItem->owner_character_id = null;
                    $listingItem->save();

                    // Reduce original stack
                    $item->stack_size = $stackSize - $quantity;
                    $item->save();
                } else {
                    // Move entire item to market
                    $item->update([
                        'location'             => 'market',
                        'owner_character_id'   => null,
                    ]);
                    $listingItem = $item;
                }

                if ($wasEquipped) {
                    $character->clearStatsCache();
                }

                ItemLedger::create([
                    'id'               => Str::ulid(),
                    'character_id'     => $character->id,
                    'item_instance_id' => $listingItem->id,
                    'action'           => 'listed_on_market',
                    'ref_type'         => 'market_listing',
                    'ref_id'           => null,
                    'quantity_change'  => -$quantity,
                    'idempotency_key'  => $idempotencyKey . ':item',
                ]);

                // Create listing
                $listing = MarketListing::create([
                    'seller_character_id' => $character->id,
                    'item_instance_id'    => $listingItem->id,
                    'price'               => $price,
                    'currency'            => $currency,
                    'status'              => 'active',
                    'expires_at'          => now()->addHours($durationHours),
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

    /**
     * Wystawia peta na Rynku - analogicznie do execute(), ale bez ruchów
     * ItemInstance/ItemLedger (pet nigdy fizycznie nie zmienia "lokalizacji";
     * obecność aktywnego MarketListing.pet_id wystarcza jako stan "na rynku").
     */
    public function executeForPet(Character $character, Pet $pet, int $price, string $currency, int $durationHours): Result
    {
        if ($pet->character_id !== $character->id) {
            return Result::error('NOT_OWNER', 'Ten chowaniec nie należy do Ciebie.');
        }

        if ($pet->is_equipped) {
            return Result::error('PET_EQUIPPED', 'Musisz odwołać tego chowańca jako towarzysza, zanim wystawisz go na targowisko.');
        }

        if ($pet->collar_item_instance_id || $pet->charm_item_instance_id) {
            return Result::error('PET_GEARED', 'Musisz zdjąć cały ekwipunek chowańca (obrożę/charm), zanim wystawisz go na targowisko.');
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
            return DB::transaction(function () use ($character, $pet, $price, $currency, $durationHours, $listingFee) {
                $pet = Pet::where('id', $pet->id)->lockForUpdate()->first();

                if (!$pet || $pet->is_equipped) {
                    return Result::error('PET_EQUIPPED', 'Musisz odwołać tego chowańca jako towarzysza, zanim wystawisz go na targowisko.');
                }

                if (MarketListing::active()->where('pet_id', $pet->id)->exists()) {
                    return Result::error('ALREADY_LISTED', 'Ten chowaniec jest już wystawiony na Rynku.');
                }

                $idempotencyKey = 'market_list_pet:' . $pet->id . ':' . Str::ulid();

                $character->gold -= $listingFee;
                $character->save();

                CurrencyLedger::create([
                    'id'              => Str::ulid(),
                    'idempotency_key' => $idempotencyKey . ':fee',
                    'character_id'    => $character->id,
                    'currency_type'   => 'gold',
                    'amount'          => -$listingFee,
                    'balance_after'   => $character->gold,
                    'source_type'     => 'market_listing_fee',
                    'source_id'       => (string) $pet->id,
                    'description'     => "Opłata za wystawienie chowańca na market ({$durationHours}h)",
                    'created_at'      => now(),
                ]);

                $listing = MarketListing::create([
                    'seller_character_id' => $character->id,
                    'pet_id'              => $pet->id,
                    'price'               => $price,
                    'currency'            => $currency,
                    'status'              => 'active',
                    'expires_at'          => now()->addHours($durationHours),
                ]);

                event(new MarketListingCreated($listing));

                Log::info('Pet market listing created', [
                    'listing_id' => $listing->id,
                    'character_id' => $character->id,
                    'pet_id' => $pet->id,
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
            Log::error('CreateMarketListing (pet) failed', [
                'character_id' => $character->id,
                'pet_id' => $pet->id,
                'error' => $e->getMessage(),
            ]);
            return Result::error('LISTING_FAILED', 'Wystąpił błąd podczas wystawiania chowańca na market.');
        }
    }
}
