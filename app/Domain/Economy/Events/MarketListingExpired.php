<?php

namespace App\Domain\Economy\Events;

use App\Infrastructure\Persistence\MarketListing;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketListingExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MarketListing $listing
    ) {}
}
