<?php

namespace App\Application\Economy\Actions;

use App\Application\Shared\Result;
use App\Domain\Economy\Events\MarketListingSold;
use App\Domain\Mail\Events\MailReceived;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\MarketListing;
use App\Infrastructure\Persistence\Purchase;
use App\Infrastructure\Persistence\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BuyMarketListingAction
{
    private const COMMISSION_RATE = 0.05; // 5%

    public function execute(Character $buyer, MarketListing $listing): Result
    {
        if ($listing->status !== 'active') {
            return Result::error('NOT_ACTIVE', 'Ta oferta nie jest już aktywna.');
        }

        if ($listing->seller_character_id === $buyer->id) {
            return Result::error('SELF_BUY', 'Nie możesz kupić własnego przedmiotu.');
        }

        $currency = $listing->currency;
        $price = $listing->price;
        $buyerBalance = $currency === 'gold' ? $buyer->gold : $buyer->user->gems;

        if ($buyerBalance < $price) {
            return Result::error('INSUFFICIENT_FUNDS', "Nie masz wystarczająco {$currency} na ten zakup. Potrzebujesz: {$price}.");
        }

        try {
            return DB::transaction(function () use ($buyer, $listing, $currency, $price) {
                // Lock the listing to prevent race conditions
                $listing = MarketListing::lockForUpdate()->find($listing->id);

                if (!$listing || $listing->status !== 'active') {
                    return Result::error('ALREADY_SOLD', 'Ta oferta została już kupiona lub wycofana.');
                }

                $idempotencyKey = 'market_buy:' . $listing->id . ':' . Str::ulid();

                // Deduct from buyer
                if ($currency === 'gold') {
                    $buyer->gold -= $price;
                    $buyer->save();
                } else {
                    $buyer->user->gems -= $price;
                    $buyer->user->save();
                }

                CurrencyLedger::create([
                    'id' => Str::ulid(),
                    'idempotency_key' => $idempotencyKey . ':buyer_pay',
                    'character_id' => $buyer->id,
                    'currency_type' => $currency,
                    'amount' => -$price,
                    'balance_after' => $currency === 'gold' ? $buyer->gold : $buyer->user->gems,
                    'source_type' => 'market_purchase',
                    'source_id' => $listing->id,
                    'description' => 'Zakup na markecie',
                    'created_at' => now(),
                ]);

                // Calculate commission
                $commission = (int) floor($price * self::COMMISSION_RATE);
                $sellerProceeds = $price - $commission;

                // Update listing
                $listing->update(['status' => 'sold']);

                // Create purchase record
                $purchase = Purchase::create([
                    'listing_id' => $listing->id,
                    'buyer_character_id' => $buyer->id,
                    'price_paid' => $price,
                    'currency' => $currency,
                ]);

                // Transfer item to buyer
                $item = ItemInstance::find($listing->item_instance_id);
                if ($item) {
                    $item->update([
                        'owner_character_id' => $buyer->id,
                        'location' => 'inventory',
                    ]);

                    ItemLedger::create([
                        'id' => Str::ulid(),
                        'character_id' => $buyer->id,
                        'item_instance_id' => $item->id,
                        'action' => 'purchased_from_market',
                        'ref_type' => 'market_purchase',
                        'ref_id' => $purchase->id,
                        'quantity_change' => 1,
                        'idempotency_key' => $idempotencyKey . ':item_transfer',
                    ]);
                }

                // Send mail to seller with proceeds
                $sellerMail = Mail::create([
                    'to_character_id' => $listing->seller_character_id,
                    'subject' => 'Przedmiot sprzedany!',
                    'body' => "Twój przedmiot został sprzedany za {$price} {$currency}. Po potrąceniu 5% prowizji ({$commission} {$currency}) otrzymujesz {$sellerProceeds} {$currency}.",
                    'attachments' => [
                        ['type' => $currency, 'qty' => $sellerProceeds],
                    ],
                ]);
                event(new MailReceived($sellerMail));

                // Send confirmation mail to buyer
                $buyerMail = Mail::create([
                    'to_character_id' => $buyer->id,
                    'subject' => 'Zakup udany!',
                    'body' => "Pomyślnie zakupiono przedmiot za {$price} {$currency}. Przedmiot trafił do Twojego plecaka.",
                    'attachments' => null,
                    'claimed' => true,
                ]);
                event(new MailReceived($buyerMail));

                event(new MarketListingSold($listing, $purchase));

                Log::info('Market listing sold', [
                    'listing_id' => $listing->id,
                    'buyer_id' => $buyer->id,
                    'seller_id' => $listing->seller_character_id,
                    'price' => $price,
                    'commission' => $commission,
                ]);

                return Result::ok([
                    'purchase' => $purchase,
                    'item' => $item,
                    'commission' => $commission,
                    'seller_proceeds' => $sellerProceeds,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('BuyMarketListing failed', [
                'listing_id' => $listing->id,
                'buyer_id' => $buyer->id,
                'error' => $e->getMessage(),
            ]);
            return Result::error('BUY_FAILED', 'Wystąpił błąd podczas zakupu.');
        }
    }
}
