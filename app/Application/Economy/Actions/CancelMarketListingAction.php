<?php

namespace App\Application\Economy\Actions;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\MarketListing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CancelMarketListingAction
{
    public function execute(Character $character, MarketListing $listing): Result
    {
        if ($listing->seller_character_id !== $character->id) {
            return Result::error('NOT_OWNER', 'Ta oferta nie należy do Ciebie.');
        }

        if ($listing->status !== 'active') {
            return Result::error('NOT_ACTIVE', 'Oferta nie jest aktywna i nie można jej anulować.');
        }

        try {
            return DB::transaction(function () use ($character, $listing) {
                $idempotencyKey = 'market_cancel:' . $listing->id . ':' . Str::ulid();

                $listing->update(['status' => 'cancelled']);

                // Return item to character
                $item = ItemInstance::find($listing->item_instance_id);
                if ($item) {
                    $item->update([
                        'owner_character_id' => $character->id,
                        'location' => 'inventory',
                    ]);

                    ItemLedger::create([
                        'id' => Str::ulid(),
                        'character_id' => $character->id,
                        'item_instance_id' => $item->id,
                        'action' => 'returned_from_market',
                        'ref_type' => 'market_listing',
                        'ref_id' => $listing->id,
                        'quantity_change' => 1,
                        'idempotency_key' => $idempotencyKey . ':return',
                    ]);
                }

                Log::info('Market listing cancelled', [
                    'listing_id' => $listing->id,
                    'character_id' => $character->id,
                ]);

                return Result::ok(['listing' => $listing]);
            });
        } catch (\Exception $e) {
            Log::error('CancelMarketListing failed', [
                'listing_id' => $listing->id,
                'error' => $e->getMessage(),
            ]);
            return Result::error('CANCEL_FAILED', 'Wystąpił błąd podczas anulowania oferty.');
        }
    }
}
