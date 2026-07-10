<?php

namespace App\Application\Economy\Jobs;

use App\Domain\Economy\Events\MarketListingExpired;
use App\Domain\Mail\Events\MailReceived;
use App\Infrastructure\Persistence\MarketListing;
use App\Infrastructure\Persistence\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireMarketListingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredListings = MarketListing::expired()->with('item.template')->get();

        $count = 0;

        foreach ($expiredListings as $listing) {
            try {
                DB::transaction(function () use ($listing) {
                    $listing->update(['status' => 'expired']);

                    // Return item to seller via mail
                    $itemName = $listing->item?->template?->name ?? 'Nieznany przedmiot';

                    $mail = Mail::create([
                        'to_character_id' => $listing->seller_character_id,
                        'subject' => 'Oferta wygasła',
                        'body' => "Twoja oferta na {$itemName} wygasła. Przedmiot został zwrócony do Twojej skrzynki pocztowej.",
                        'attachments' => [
                            ['type' => 'item', 'id' => $listing->item_instance_id],
                        ],
                    ]);

                    event(new MailReceived($mail));
                    event(new MarketListingExpired($listing));
                });

                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to expire listing', [
                    'listing_id' => $listing->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            Log::info("Expired {$count} market listings.");
        }
    }
}
