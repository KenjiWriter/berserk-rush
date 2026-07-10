<?php

namespace App\Domain\Economy\Events;

use App\Infrastructure\Persistence\MarketListing;
use App\Infrastructure\Persistence\Purchase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketListingSold
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MarketListing $listing,
        public readonly Purchase $purchase
    ) {}
}
